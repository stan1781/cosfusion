/**
 * Remove hashed build artifacts under assets/dist that are no longer in manifest.json.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const distDir = path.join(root, 'assets', 'dist');
const manifestPath = path.join(distDir, 'manifest.json');

if (!fs.existsSync(manifestPath)) {
	console.warn('[prune-dist] manifest.json missing, skip.');
	process.exit(0);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const keep = new Set(['manifest.json']);

for (const entry of Object.values(manifest)) {
	if (!entry || typeof entry !== 'object') {
		continue;
	}
	if (typeof entry.file === 'string') {
		keep.add(entry.file.replace(/\\/g, '/'));
	}
	if (Array.isArray(entry.css)) {
		for (const file of entry.css) {
			keep.add(String(file).replace(/\\/g, '/'));
		}
	}
}

let removed = 0;

function walk(dir) {
	for (const name of fs.readdirSync(dir)) {
		const full = path.join(dir, name);
		const rel = path.relative(distDir, full).replace(/\\/g, '/');

		if (fs.statSync(full).isDirectory()) {
			walk(full);
			if (fs.readdirSync(full).length === 0) {
				fs.rmdirSync(full);
			}
			continue;
		}

		if (keep.has(rel)) {
			continue;
		}

		fs.unlinkSync(full);
		removed += 1;
		console.log(`[prune-dist] removed ${rel}`);
	}
}

if (fs.existsSync(distDir)) {
	walk(distDir);
}

console.log(`[prune-dist] kept ${keep.size} manifest paths, removed ${removed} file(s).`);
