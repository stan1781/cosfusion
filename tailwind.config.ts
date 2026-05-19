import type { Config } from 'tailwindcss';
import { tailwindTheme } from './config/theme-tokens';

export default {
	content: [
		'./*.php',
		'./inc/**/*.php',
		'./template-parts/**/*.php',
		'./woocommerce/**/*.php',
		'./src/ts/**/*.ts',
	],
	theme: {
		extend: tailwindTheme,
	},
} satisfies Config;
