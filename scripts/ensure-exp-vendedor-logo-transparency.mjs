/**
 * Garante fundo transparente no PNG da logo de login (remove matte branco/cinza).
 * Não altera exp-vendedor-icon.png (header/launcher aprovado).
 */
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const logoPath = join(root, 'public/images/exp-vendedor/exp-vendedor-logo.png');

const BACKGROUND_THRESHOLD = 232;

function isBackgroundPixel(r, g, b) {
    const avg = (r + g + b) / 3;
    const spread = Math.max(r, g, b) - Math.min(r, g, b);

    return avg >= BACKGROUND_THRESHOLD && spread <= 18;
}

function floodBackgroundToAlpha(data, width, height) {
    const visited = new Uint8Array(width * height);
    const queue = [];

    for (let x = 0; x < width; x += 1) {
        queue.push(x, 0, x, height - 1);
    }
    for (let y = 0; y < height; y += 1) {
        queue.push(0, y, width - 1, y);
    }

    while (queue.length > 0) {
        const y = queue.pop();
        const x = queue.pop();
        if (x < 0 || y < 0 || x >= width || y >= height) {
            continue;
        }

        const index = y * width + x;
        if (visited[index]) {
            continue;
        }
        visited[index] = 1;

        const offset = index * 4;
        const r = data[offset];
        const g = data[offset + 1];
        const b = data[offset + 2];

        if (!isBackgroundPixel(r, g, b)) {
            continue;
        }

        data[offset + 3] = 0;

        queue.push(x + 1, y, x - 1, y, x, y + 1, x, y - 1);
    }
}

async function main() {
    if (!existsSync(logoPath)) {
        throw new Error(`Missing login logo: ${logoPath}`);
    }

    const image = sharp(logoPath).ensureAlpha();
    const { data, info } = await image.raw().toBuffer({ resolveWithObject: true });
    const pixels = Buffer.from(data);

    floodBackgroundToAlpha(pixels, info.width, info.height);

    await sharp(pixels, {
        raw: {
            width: info.width,
            height: info.height,
            channels: 4,
        },
    })
        .png()
        .toFile(logoPath);

    const stats = await sharp(logoPath).stats();
    if (stats.isOpaque) {
        throw new Error('Login logo still opaque after transparency pass');
    }

    console.log('[EXP Vendedor] login logo transparency ensured');
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
