import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
	build: {
		manifest: 'manifest.json',
		outDir: path.resolve(__dirname, 'assets/dist'),
		emptyOutDir: true,
		rollupOptions: {
			input: {
				app: path.resolve(__dirname, 'src/ts/app.ts'),
				styles: path.resolve(__dirname, 'src/scss/app.scss'),
				woo: path.resolve(__dirname, 'src/scss/woo.scss'),
				editor: path.resolve(__dirname, 'src/scss/editor.scss'),
			},
		},
	},
});
