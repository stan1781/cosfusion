/**
 * Package the theme as a WordPress-uploadable zip (tailwindscore/ at archive root).
 * Run after `npm run build` so assets/dist/manifest.json is present.
 */
import archiver from 'archiver';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const THEME_SLUG = 'tailwindscore';
const manifestPath = path.join(root, 'assets', 'dist', 'manifest.json');

if (!fs.existsSync(manifestPath)) {
	console.error('[package-theme] assets/dist/manifest.json missing. Run `npm run build` first.');
	process.exit(1);
}

const styleCss = fs.readFileSync(path.join(root, 'style.css'), 'utf8');
const versionMatch = styleCss.match(/^Version:\s*(.+)$/m);
const version = versionMatch?.[1]?.trim() ?? '0.0.0';

const releaseDir = path.join(root, 'release');
const zipName = `${THEME_SLUG}-${version}.zip`;
const zipPath = path.join(releaseDir, zipName);

const EXCLUDE_DIRS = new Set([
	'node_modules',
	'src',
	'docs',
	'scripts',
	'release',
	'.git',
	'.cursor',
	'.vscode',
	'.codegraph',
	'.worktrees',
	'.kiro',
	'vendor',
]);

const EXCLUDE_FILES = new Set([
	'vite.config.ts',
	'tsconfig.json',
	'package.json',
	'package-lock.json',
	'.gitignore',
	'.gitattributes',
	'README.md',
]);

function shouldExclude(relativePath) {
	const normalized = relativePath.replace(/\\/g, '/');
	const segments = normalized.split('/').filter(Boolean);

	if (segments.some((segment) => EXCLUDE_DIRS.has(segment))) {
		return true;
	}

	const basename = segments.at(-1) ?? '';

	if (EXCLUDE_FILES.has(basename)) {
		return true;
	}

	if (basename.endsWith('.map') || basename.endsWith('.zip') || basename.endsWith('.code-workspace')) {
		return true;
	}

	if (basename.startsWith('.env')) {
		return true;
	}

	return false;
}

fs.mkdirSync(releaseDir, { recursive: true });

if (fs.existsSync(zipPath)) {
	fs.unlinkSync(zipPath);
}

const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
	const sizeKb = (archive.pointer() / 1024).toFixed(1);
	console.log(`[package-theme] wrote ${zipPath} (${sizeKb} KiB)`);
});

archive.on('warning', (error) => {
	if (error.code === 'ENOENT') {
		console.warn('[package-theme]', error.message);
		return;
	}
	throw error;
});

archive.on('error', (error) => {
	throw error;
});

archive.pipe(output);

archive.directory(root, THEME_SLUG, (entry) => (shouldExclude(entry.name) ? false : entry));

await archive.finalize();
