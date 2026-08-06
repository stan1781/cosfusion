import { clearGuestWishlist, readGuestWishlist } from './storage';
import { getWishlistConfig, mergeWishlist } from './api';
import type { WishlistEnrichedItem } from './types';
import { itemKey } from './types';

let mergeInFlight: Promise<WishlistEnrichedItem[] | null> | null = null;
let cachedServerKeys: Set<string> | null = null;

export function getCachedServerKeys(): Set<string> | null {
	return cachedServerKeys;
}

export function setCachedServerKeysFromItems(items: Array<{ productId: number }>): void {
	cachedServerKeys = new Set(items.map((item) => itemKey(item.productId)));
}

/** 登录用户若本地仍有访客列表则合并。 */
export async function mergeGuestWishlistIfNeeded(): Promise<WishlistEnrichedItem[] | null> {
	const config = getWishlistConfig();

	if (!config?.isLoggedIn) {
		return null;
	}

	const guestItems = readGuestWishlist();

	if (guestItems.length < 1) {
		return null;
	}

	if (mergeInFlight) {
		return mergeInFlight;
	}

	mergeInFlight = (async () => {
		try {
			const response = await mergeWishlist(guestItems);
			clearGuestWishlist();
			setCachedServerKeysFromItems(response.items);
			return response.items;
		} finally {
			mergeInFlight = null;
		}
	})();

	return mergeInFlight;
}
