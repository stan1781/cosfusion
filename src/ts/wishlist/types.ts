/** Wishlist runtime config from PHP. */
export type TailwindscoreWishlistConfig = {
	restUrl: string;
	nonce: string;
	isLoggedIn: boolean;
	maxItems: number;
	i18n: {
		add: string;
		remove: string;
		added: string;
		removed: string;
		limit: string;
		error: string;
		addedToCart: string;
		itemCount: string;
		itemCountOne: string;
		empty: string;
	};
};

export type WishlistItem = {
	productId: number;
	addedAt: number;
};

export type WishlistEnrichedItem = WishlistItem & {
	name?: string;
	priceHtml?: string;
	image?: string;
	permalink?: string;
	isVariable?: boolean;
	canAddDirect?: boolean;
};

export type WishlistListResponse = {
	items: WishlistEnrichedItem[];
	count: number;
	added?: boolean;
};

export function itemKey(productId: number): string {
	return String(productId);
}
