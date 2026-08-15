/**
 * One-shot QA APK: bake production URLs, sync Capacitor, assembleDebug, verify APK.
 */
import { copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync, spawnSync } from 'node:child_process';
import {
    DEFAULT_APP_VERSION,
    QA_API_ORIGIN,
    QA_WEB_ORIGIN,
} from './exp-vendedor-release-config.mjs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const win = process.platform === 'win32';
const studioJbr = 'C:\\Program Files\\Android\\Android Studio\\jbr';
const microsoftJdks = 'C:\\Program Files\\Microsoft';

function javaMajorFromHome(home) {
    const release = join(home, 'release');
    if (!existsSync(release)) {
        return 0;
    }
    const text = readFileSync(release, 'utf8');
    const match = text.match(/JAVA_VERSION="(\d+)/);
    return match ? Number(match[1]) : 0;
}

function detectJavaHome() {
    if (process.env.EXP_JAVA_HOME && existsSync(join(process.env.EXP_JAVA_HOME, 'bin', 'java.exe'))) {
        return process.env.EXP_JAVA_HOME;
    }
    const candidates = [];
    if (existsSync(microsoftJdks)) {
        for (const name of readdirSync(microsoftJdks)) {
            if (/^jdk-1[17]/i.test(name) || /^jdk-21/i.test(name)) {
                candidates.push(join(microsoftJdks, name));
            }
        }
    }
    candidates.push(studioJbr);
    const compatible = candidates
        .filter((home) => existsSync(join(home, 'bin', 'java.exe')))
        .map((home) => ({ home, major: javaMajorFromHome(home) }))
        .filter((row) => row.major >= 17 && row.major <= 21);
    compatible.sort((a, b) => b.major - a.major);
    return compatible[0]?.home || '';
}

function applyWindowsJdk() {
    if (!win) {
        return;
    }
    const home = detectJavaHome();
    if (!home) {
        console.error('[EXP Release] BLOCKED: need JDK 21 for Capacitor/Gradle 8.11 (not Java 8, not Android Studio JBR 25).');
        process.exit(1);
    }
    process.env.JAVA_HOME = home;
    process.env.PATH = `${join(home, 'bin')};${process.env.PATH || ''}`;
    console.log(`[EXP Release] JAVA_HOME=${home}`);
}

function run(command, args, cwd = root) {
    const useCmd = win && ['npm', 'npx', 'gradlew.bat'].includes(command);
    const result = useCmd
        ? spawnSync('cmd.exe', ['/d', '/s', '/c', [command, ...args].join(' ')], {
            cwd,
            env: process.env,
            stdio: 'inherit',
            shell: false,
            windowsHide: true,
        })
        : spawnSync(command, args, {
            cwd,
            env: process.env,
            stdio: 'inherit',
            shell: false,
            windowsHide: true,
        });
    if (result.error) {
        console.error(`[EXP Release] BLOCKED: ${result.error.message}`);
        process.exit(1);
    }
    if (result.status !== 0) {
        process.exit(result.status || 1);
    }
}

function javaVersion() {
    try {
        execFileSync('java', ['-version'], { stdio: 'inherit', env: process.env });
    } catch {
        console.error('[EXP Release] BLOCKED: java not found. Use Android Studio JBR.');
        process.exit(1);
    }
}

applyWindowsJdk();
javaVersion();

process.env.EXP_BUILD_MODE = 'qa';
process.env.CAP_API_URL = QA_API_ORIGIN;
process.env.CAP_WEB_ORIGIN = QA_WEB_ORIGIN;
process.env.EXPANDOR_SHELL_VERSION = process.env.EXPANDOR_SHELL_VERSION || DEFAULT_APP_VERSION;

console.log(`[EXP Release] API: ${process.env.CAP_API_URL}`);
console.log(`[EXP Release] Web: ${process.env.CAP_WEB_ORIGIN}`);

const verifier = join(root, 'scripts/verify-exp-vendedor-release-config.mjs');

run('npm', ['run', 'build']);
run('npx', ['cap', 'sync', 'android']);
run(process.execPath, [verifier]);

const gradlew = win ? 'gradlew.bat' : './gradlew';
run(gradlew, ['assembleDebug'], join(root, 'android'));

const apk = join(root, 'android/app/build/outputs/apk/debug/app-debug.apk');
if (!existsSync(apk)) {
    console.error(`[EXP Release] BLOCKED: APK not found at ${apk}`);
    process.exit(1);
}

run(process.execPath, [verifier, '--apk', apk]);

const version = process.env.EXPANDOR_SHELL_VERSION || DEFAULT_APP_VERSION;
const distDir = join(root, 'dist');
mkdirSync(distDir, { recursive: true });
const distApk = join(distDir, `exp-vendedor-${version}-qa.apk`);
copyFileSync(apk, distApk);
console.log(`[EXP Release] APK: ${apk}`);
console.log(`[EXP Release] Copy: ${distApk}`);
console.log('[EXP Release] BUILD SUCCESSFUL');
