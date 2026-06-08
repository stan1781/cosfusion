import type { TailwindscoreAccountAuthConfig } from './account-auth/types';
import type { TailwindscoreHomeConfig } from './home/types';
import type { TailwindscoreProductPageConfig } from './product/types';

export {};

declare global {
	interface Window {
		tailwindscoreProduct?: TailwindscoreProductPageConfig;
		tailwindscoreAccountAuth?: TailwindscoreAccountAuthConfig;
		tailwindscoreHome?: TailwindscoreHomeConfig;
	}
}
