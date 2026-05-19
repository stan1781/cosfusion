import fs from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import { buildThemeTokenCss } from './config/theme-tokens';

const generatedThemeCssPath = path.resolve(__dirname, 'src/css/generated/theme-tokens.css');

fs.mkdirSync(path.dirname(generatedThemeCssPath), { recursive: true });
fs.writeFileSync(generatedThemeCssPath, buildThemeTokenCss(), 'utf8');

export default defineConfig({
	build: {
		manifest: 'manifest.json',
		outDir: path.resolve(__dirname, 'assets/dist'),
		emptyOutDir: true,
		rollupOptions: {
			input: {
				app: path.resolve(__dirname, 'src/ts/app.ts'),
				styles: path.resolve(__dirname, 'src/css/app.css'),
				woo: path.resolve(__dirname, 'src/css/woo.css'),
			},
		},
	},
});
