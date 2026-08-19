/**
 * Derives web-optimized product shots from the commercial PNG pack.
 * Originals stay in docs/expandor-commercial-presentation/screenshots/.
 */
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const srcDir = join(root, 'docs/expandor-commercial-presentation/screenshots');
const outDir = join(root, 'public/images/marketplace/product');

mkdirSync(outDir, { recursive: true });

const jobs = [
    { file: '01-hero-mapa-operacional.png', name: 'hero-mapa', width: 1600 },
    { file: '02-dashboard-gestor.png', name: 'dashboard-gestor', width: 1280 },
    { file: '03-inteligencia-ponto.png', name: 'inteligencia-ponto', width: 1280 },
    { file: '04-exp-vendedor-mapa.png', name: 'exp-mapa', width: 780 },
    { file: '05-exp-vendedor-agenda.png', name: 'exp-agenda', width: 780 },
    { file: '06-exp-vendedor-produtos.png', name: 'exp-produtos', width: 780 },
    { file: '08-exp-vendedor-venda.png', name: 'exp-venda', width: 780 },
    { file: '09-exp-vendedor-venda-realizada.png', name: 'exp-venda-realizada', width: 780 },
    { file: '10-exp-vendedor-resultado.png', name: 'exp-resultado', width: 780 },
    { file: '11-exp-vendedor-comissao.png', name: 'exp-comissao', width: 780 },
    { file: '14-equipe-gestor.png', name: 'equipe-gestor', width: 1280 },
    { file: '15-financeiro-gestor.png', name: 'financeiro-gestor', width: 1280 },
];

for (const job of jobs) {
    const input = join(srcDir, job.file);
    const pipeline = () => sharp(input).resize({ width: job.width, withoutEnlargement: true });
    await pipeline().webp({ quality: 78 }).toFile(join(outDir, `${job.name}.webp`));
    await pipeline().png({ compressionLevel: 8 }).toFile(join(outDir, `${job.name}.png`));
    console.log('wrote', job.name);
}

console.log('marketplace product shots ready:', outDir);
