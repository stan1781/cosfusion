import type { WishlistItem } from './types';
import { itemKey } from './types';

const STORAGE_KEY = 'ts_wishlist';

function readRaw(): unknown {
	try {
		const raw = window.localStorage.getItem(STORAGE_KEY);
		if (!raw) {
			return [];
		}
		return JSON.parse(raw) as unknown;
	} catch {
		return [];
	}
}

function normalizeItem(raw: unknown): WishlistItem | null {
	if (!raw || typeof raw !== 'object') {
		return null;
	}

	const record = raw as Record<string, unknown>;
	let productId = Math.max(0, Number(record.productId ?? 0));

	// 兼容旧数据：仅有 variationId 时当作商品 ID（服务端会再归一到父商品）。
	if (productId < 1 && record.variationId) {
		productId = Math.max(0, Number(record.variationId));
	}

	const addedAt = Math.max(0, Number(record.addedAt ?? Date.now() / 1000));

	if (productId < 1) {
		return null;
	}

	return {
		productId,
		addedAt: addedAt > 0 ? Math.floor(addedAt) : Math.floor(Date.now() / 1000),
	};
}

export function readGuestWishlist(): WishlistItem[] {
	const raw = readRaw();
	if (!Array.isArray(raw)) {
		return [];
	}

	const seen = new Set<string>();
	const items: WishlistItem[] = [];

	for (const entry of raw) {
		const item = normalizeItem(entry);
		if (!item) {
			continue;
		}
		const key = itemKey(item.productId);
		if (seen.has(key)) {
			continue;
		}
		seen.add(key);
		items.push(item);
	}

	return items;
}

export function writeGuestWishlist(items: WishlistItem[]): void {
	try {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
	} catch {
		// Quota or private mode — ignore.
	}
}

export function clearGuestWishlist(): void {
	try {
		window.localStorage.removeItem(STORAGE_KEY);
	} catch {
		// Ignore.
	}
}

export function guestHasItem(productId: number): boolean {
	const key = itemKey(productId);
	return readGuestWishlist().some((item) => itemKey(item.productId) === key);
}

export function toggleGuestItem(
	productId: number,
	maxItems: number,
): { items: WishlistItem[]; added: boolean; error?: 'limit' } {
	const items = readGuestWishlist();
	const key = itemKey(productId);
	const index = items.findIndex((item) => itemKey(item.productId) === key);

	if (index >= 0) {
		items.splice(index, 1);
		writeGuestWishlist(items);
		return { items, added: false };
	}

	if (items.length >= maxItems) {
		return { items, added: false, error: 'limit' };
	}

	items.unshift({
		productId,
		addedAt: Math.floor(Date.now() / 1000),
	});
	writeGuestWishlist(items);
	return { items, added: true };
}
