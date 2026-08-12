/**
 * Gera ícones launcher, foreground adaptive e splash Android a partir dos PNG oficiais.
 * Fonte: public/images/exp-vendedor/exp-vendedor-icon.png
 */
import { existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const iconSrc = join(root, 'public/images/exp-vendedor/exp-vendedor-icon.png');
const androidRes = join(root, 'android/app/src/main/res');

const BRAND_BLUE = '#1E4A8C';

const LAUNCHER = {
    'mipmap-mdpi': 48,
    'mipmap-hdpi': 72,
    'mipmap-xhdpi': 96,
    'mipmap-xxhdpi': 144,
    'mipmap-xxxhdpi': 192,
};

const FOREGROUND = {
    'mipmap-mdpi': 108,
    'mipmap-hdpi': 162,
    'mipmap-xhdpi': 216,
    'mipmap-xxhdpi': 324,
    'mipmap-xxxhdpi': 432,
};

const SPLASH_PORT = {
    'drawable-port-mdpi': { w: 320, h: 480 },
    'drawable-port-hdpi': { w: 480, h: 800 },
    'drawable-port-xhdpi': { w: 720, h: 1280 },
    'drawable-port-xxhdpi': { w: 960, h: 1600 },
    'drawable-port-xxxhdpi': { w: 1280, h: 1920 },
};

const SPLASH_LAND = {
    'drawable-land-mdpi': { w: 480, h: 320 },
    'drawable-land-hdpi': { w: 800, h: 480 },
    'drawable-land-xhdpi': { w: 1280, h: 720 },
    'drawable-land-xxhdpi': { w: 1600, h: 960 },
    'drawable-land-xxxhdpi': { w: 1920, h: 1280 },
};

async function writeSquareIcon(size, outPath) {
    await sharp(iconSrc)
        .resize(size, size, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
        .png()
        .toFile(outPath);
}

async function writeForeground(size, outPath) {
    const inset = Math.round(size * 0.14);
    const inner = size - inset * 2;
    const icon = await sharp(iconSrc)
        .resize(inner, inner, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
        .png()
        .toBuffer();

    await sharp({
        create: {
            width: size,
            height: size,
            channels: 4,
            background: { r: 0, g: 0, b: 0, alpha: 0 },
        },
    })
        .composite([{ input: icon, left: inset, top: inset }])
        .png()
        .toFile(outPath);
}

async function writeSplash(width, height, outPath) {
    const logoSize = Math.round(Math.min(width, height) * 0.42);
    const logo = await sharp(iconSrc)
        .resize(logoSize, logoSize, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
        .png()
        .toBuffer();

    await sharp({
        create: {
            width,
            height,
            channels: 3,
            background: BRAND_BLUE,
        },
    })
        .composite([{ input: logo, gravity: 'center' }])
        .png()
        .toFile(outPath);
}

async function main() {
    if (!existsSync(iconSrc)) {
        throw new Error(`Missing official icon PNG: ${iconSrc}`);
    }

    for (const [folder, size] of Object.entries(LAUNCHER)) {
        const dir = join(androidRes, folder);
        mkdirSync(dir, { recursive: true });
        await writeSquareIcon(size, join(dir, 'ic_launcher.png'));
        await writeSquareIcon(size, join(dir, 'ic_launcher_round.png'));
    }

    for (const [folder, size] of Object.entries(FOREGROUND)) {
        const dir = join(androidRes, folder);
        mkdirSync(dir, { recursive: true });
        await writeForeground(size, join(dir, 'ic_launcher_foreground.png'));
    }

    mkdirSync(join(androidRes, 'drawable'), { recursive: true });
    await writeSplash(1080, 1920, join(androidRes, 'drawable/splash.png'));

    for (const [folder, { w, h }] of Object.entries({ ...SPLASH_PORT, ...SPLASH_LAND })) {
        const dir = join(androidRes, folder);
        mkdirSync(dir, { recursive: true });
        await writeSplash(w, h, join(dir, 'splash.png'));
    }

    console.log('EXP Vendedor Android assets generated from', iconSrc);
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
