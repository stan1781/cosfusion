import type { Config } from 'tailwindcss';

export default {
	content: [
		'./*.php',
		'./inc/**/*.php',
		'./template-parts/**/*.php',
		'./woocommerce/**/*.php',
		'./src/ts/**/*.ts',
	],
} satisfies Config;

