/**
 * Generate minimal solid-color PNG icons for PWA install (brand primary).
 * No external deps — raw PNG + zlib.
 */
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outDir = path.join(root, 'assets', 'pwa');

/** Brand primary #004D38 */
const R = 0x00;
const G = 0x4d;
const B = 0x38;

function crc32(buf) {
	let c = ~0;
	for (let i = 0; i < buf.length; i += 1) {
		c ^= buf[i];
		for (let k = 0; k < 8; k += 1) {
			c = c & 1 ? (0xedb88320 ^ (c >>> 1)) : c >>> 1;
		}
	}
	return ~c >>> 0;
}

function chunk(type, data) {
	const typeBuf = Buffer.from(type, 'ascii');
	const len = Buffer.alloc(4);
	len.writeUInt32BE(data.length, 0);
	const crcBuf = Buffer.alloc(4);
	crcBuf.writeUInt32BE(crc32(Buffer.concat([typeBuf, data])), 0);
	return Buffer.concat([len, typeBuf, data, crcBuf]);
}

/**
 * @param {number} size
 * @param {{ safeInset?: number }} [opts] maskable: leave transparent-ish margin via lighter edge (solid with inset square)
 */
function createPng(size, opts = {}) {
	const safeInset = opts.safeInset ?? 0;
	const rows = [];

	for (let y = 0; y < size; y += 1) {
		const row = Buffer.alloc(1 + size * 3);
		row[0] = 0; // filter none
		for (let x = 0; x < size; x += 1) {
			const inSafe =
				safeInset === 0 ||
				(x >= safeInset && x < size - safeInset && y >= safeInset && y < size - safeInset);
			const i = 1 + x * 3;
			if (inSafe) {
				row[i] = R;
				row[i + 1] = G;
				row[i + 2] = B;
			} else {
				// maskable 安全区外用略浅底，避免裁切后「满出血」
				row[i] = 0xe8;
				row[i + 1] = 0xf2;
				row[i + 2] = 0xee;
			}
		}
		rows.push(row);
	}

	const raw = Buffer.concat(rows);
	const compressed = zlib.deflateSync(raw, { level: 9 });

	const ihdr = Buffer.alloc(13);
	ihdr.writeUInt32BE(size, 0);
	ihdr.writeUInt32BE(size, 4);
	ihdr[8] = 8; // bit depth
	ihdr[9] = 2; // RGB
	ihdr[10] = 0;
	ihdr[11] = 0;
	ihdr[12] = 0;

	return Buffer.concat([
		Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
		chunk('IHDR', ihdr),
		chunk('IDAT', compressed),
		chunk('IEND', Buffer.alloc(0)),
	]);
}

fs.mkdirSync(outDir, { recursive: true });

const files = [
	{ name: 'icon-192.png', size: 192, safeInset: 0 },
	{ name: 'icon-512.png', size: 512, safeInset: 0 },
	{ name: 'icon-maskable-512.png', size: 512, safeInset: Math.round(512 * 0.1) },
];

for (const file of files) {
	const out = path.join(outDir, file.name);
	fs.writeFileSync(out, createPng(file.size, { safeInset: file.safeInset }));
	console.log(`[pwa-icons] wrote ${path.relative(root, out)}`);
}
