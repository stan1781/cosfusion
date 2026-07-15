/**
 * Bump theme version in style.css and sync package.json.
 * Default: patch. Usage: node scripts/bump-version.mjs [patch|minor|major]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const styleCssPath = path.join(root, 'style.css');
const packageJsonPath = path.join(root, 'package.json');

const bumpType = process.argv[2] ?? 'patch';

if (!['patch', 'minor', 'major'].includes(bumpType)) {
	console.error(`[bump-version] Invalid bump type: ${bumpType}. Use patch, minor, or major.`);
	process.exit(1);
}

function parseVersion(version) {
	const match = version.match(/^(\d+)\.(\d+)\.(\d+)/);
	if (!match) {
		return null;
	}

	return {
		major: Number(match[1]),
		minor: Number(match[2]),
		patch: Number(match[3]),
	};
}

function bumpVersion(version, type) {
	const parsed = parseVersion(version);
	if (!parsed) {
		throw new Error(`Invalid semver: ${version}`);
	}

	switch (type) {
		case 'major':
			return `${parsed.major + 1}.0.0`;
		case 'minor':
			return `${parsed.major}.${parsed.minor + 1}.0`;
		case 'patch':
		default:
			return `${parsed.major}.${parsed.minor}.${parsed.patch + 1}`;
	}
}

const styleCss = fs.readFileSync(styleCssPath, 'utf8');
const versionMatch = styleCss.match(/^Version:\s*(.+)$/m);
const currentVersion = versionMatch?.[1]?.trim();

if (!currentVersion) {
	console.error('[bump-version] Could not read Version from style.css');
	process.exit(1);
}

const newVersion = bumpVersion(currentVersion, bumpType);
const newStyleCss = styleCss.replace(/^Version:\s*.+$/m, `Version: ${newVersion}`);

fs.writeFileSync(styleCssPath, newStyleCss);

const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
packageJson.version = newVersion;
fs.writeFileSync(packageJsonPath, `${JSON.stringify(packageJson, null, '\t')}\n`);

console.log(`[bump-version] ${currentVersion} → ${newVersion} (${bumpType})`);
