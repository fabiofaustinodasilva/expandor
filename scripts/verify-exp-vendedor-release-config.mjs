/**
 * Verify baked EXP Vendedor runtime-config for QA/release APKs.
 *
 * Usage:
 *   node scripts/verify-exp-vendedor-release-config.mjs
 *   node scripts/verify-exp-vendedor-release-config.mjs --runtime path/to/runtime-config.js
 *   node scripts/verify-exp-vendedor-release-config.mjs --apk path/to/app-debug.apk
 */
import { existsSync, mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import {
    assertDistributionPair,
    parseBakedRuntime,
} from './exp-vendedor-release-config.mjs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

function argValue(flag) {
    const index = process.argv.indexOf(flag);
    if (index < 0) {
        return '';
    }

    return String(process.argv[index + 1] || '').trim();
}

function scanTextForBlockedHosts(label, text) {
    const lower = String(text || '').toLowerCase();
    const needles = ['127.0.0.1', 'localhost', '0.0.0.0', '10.0.2.2'];
    for (const needle of needles) {
        if (lower.includes(needle)) {
            throw new Error(`[EXP Release] BLOCKED: ${label} contains ${needle}`);
        }
    }
}

function verifyRuntimeFile(label, path) {
    if (!existsSync(path)) {
        throw new Error(`[EXP Release] BLOCKED: runtime-config ausente (${label})`);
    }
    const source = readFileSync(path, 'utf8');
    const baked = parseBakedRuntime(source);
    const urls = assertDistributionPair(baked.api, baked.web, { requireHttps: true });
    scanTextForBlockedHosts(label, source);
    console.log(`[EXP Release] ${label} API: ${urls.api}`);
    console.log(`[EXP Release] ${label} Web: ${urls.web}`);

    return urls;
}

function verifyApk(apkPath) {
    if (!existsSync(apkPath)) {
        throw new Error(`[EXP Release] BLOCKED: APK ausente (${apkPath})`);
    }
    const dir = mkdtempSync(join(tmpdir(), 'exp-apk-'));
    try {
        execFileSync('tar', ['-xf', apkPath, 'assets/public/runtime-config.js'], {
            cwd: dir,
            stdio: 'pipe',
        });
        const extracted = join(dir, 'assets/public/runtime-config.js');
        verifyRuntimeFile('APK', extracted);
    } catch (error) {
        if (String(error.message || '').startsWith('[EXP Release]')) {
            throw error;
        }
        throw new Error(
            `[EXP Release] BLOCKED: could not inspect APK runtime-config (${error.message || error})`,
        );
    } finally {
        rmSync(dir, { recursive: true, force: true });
    }
}

function main() {
    const runtimeArg = argValue('--runtime');
    const apkArg = argValue('--apk');

    if (runtimeArg) {
        verifyRuntimeFile('runtime', runtimeArg);
        console.log('[EXP Release] Config OK');

        return;
    }

    if (apkArg) {
        verifyApk(apkArg);
        console.log('[EXP Release] Config OK');

        return;
    }

    const shellRuntime = join(root, 'public/capacitor-shell/runtime-config.js');
    const androidRuntime = join(root, 'android/app/src/main/assets/public/runtime-config.js');
    const capConfig = join(root, 'capacitor.config.json');

    verifyRuntimeFile('shell', shellRuntime);
    verifyRuntimeFile('android assets', androidRuntime);
    if (existsSync(capConfig)) {
        scanTextForBlockedHosts('capacitor.config.json', readFileSync(capConfig, 'utf8'));
    }
    console.log('[EXP Release] Config OK');
}

try {
    main();
} catch (error) {
    console.error(error.message || error);
    process.exit(1);
}
