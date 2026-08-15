import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
	// WordPress serves built files from the theme path, not site root (/assets/...).
	base: './',
	build: {
		manifest: 'manifest.json',
		outDir: path.resolve(__dirname, 'assets/dist'),
		// Keep false: emptyOutDir hits EBUSY on Windows when dist is locked. Orphans are pruned after build.
		emptyOutDir: false,
		modulePreload: {
			polyfill: false,
		},
		rollupOptions: {
			input: {
				app: path.resolve(__dirname, 'src/ts/app.ts'),
				styles: path.resolve(__dirname, 'src/scss/app.scss'),
				woo: path.resolve(__dirname, 'src/scss/woo.scss'),
				editor: path.resolve(__dirname, 'src/scss/editor.scss'),
				sw: path.resolve(__dirname, 'src/ts/pwa/sw.ts'),
			},
			output: {
				entryFileNames: (chunk) =>
					chunk.name === 'sw' ? 'sw.js' : 'assets/[name]-[hash].js',
				chunkFileNames: 'assets/[name]-[hash].js',
				assetFileNames: 'assets/[name]-[hash][extname]',
				manualChunks( id ) {
					if ( id.includes( 'node_modules/swiper' ) ) {
						return 'swiper';
					}
				},
			},
		},
	},
});
