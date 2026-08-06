import type { TailwindscoreAccountAuthConfig } from './account-auth/types';
import type { TailwindscoreProductPageConfig } from './product/types';
import type { TailwindscoreWishlistConfig } from './wishlist/types';

export {};

declare global {
	interface Window {
		tailwindscoreProduct?: TailwindscoreProductPageConfig;
		tailwindscoreAccountAuth?: TailwindscoreAccountAuthConfig;
		tailwindscoreWishlist?: TailwindscoreWishlistConfig;
	}
}
