/**
 * Build assets/dist/pwa-precache.json from Vite manifest (js/css/font only).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const distDir = path.join(root, 'assets', 'dist');
const manifestPath = path.join(distDir, 'manifest.json');
const outPath = path.join(distDir, 'pwa-precache.json');

const ALLOWED_EXT = new Set(['.js', '.css', '.woff2', '.woff', '.ttf']);

if (!fs.existsSync(manifestPath)) {
	console.warn('[pwa-precache] manifest.json missing, skip.');
	process.exit(0);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const files = new Set();

for (const [key, entry] of Object.entries(manifest)) {
	if (!entry || typeof entry !== 'object') {
		continue;
	}

	// SW 自身由 /ts-sw.js 下发，不必 precache dist 副本。
	if (key === 'src/ts/pwa/sw.ts') {
		continue;
	}

	if (typeof entry.file === 'string') {
		const rel = entry.file.replace(/\\/g, '/');
		const ext = path.extname(rel).toLowerCase();
		if (ALLOWED_EXT.has(ext)) {
			files.add(rel);
		}
	}

	if (Array.isArray(entry.css)) {
		for (const cssFile of entry.css) {
			const rel = String(cssFile).replace(/\\/g, '/');
			if (ALLOWED_EXT.has(path.extname(rel).toLowerCase())) {
				files.add(rel);
			}
		}
	}
}

const list = Array.from(files).sort();
fs.mkdirSync(distDir, { recursive: true });
fs.writeFileSync(outPath, `${JSON.stringify(list, null, '\t')}\n`, 'utf8');
console.log(`[pwa-precache] wrote ${list.length} url(s) → ${path.relative(root, outPath)}`);
