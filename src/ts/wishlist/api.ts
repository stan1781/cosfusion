import type { TailwindscoreWishlistConfig, WishlistItem, WishlistListResponse } from './types';

export function getWishlistConfig(): TailwindscoreWishlistConfig | null {
	const config = window.tailwindscoreWishlist;

	if (!config?.restUrl || !config.nonce || !config.i18n) {
		return null;
	}

	return config;
}

async function requestWishlist(path: string, init: RequestInit = {}): Promise<WishlistListResponse> {
	const config = getWishlistConfig();

	if (!config) {
		throw new Error('Wishlist config missing');
	}

	const url = path.startsWith('http') ? path : `${config.restUrl.replace(/\/$/, '')}${path}`;

	const response = await fetch(url, {
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
			...(init.headers ?? {}),
		},
		...init,
	});

	const payload = (await response.json().catch(() => null)) as
		| WishlistListResponse
		| { code?: string; message?: string }
		| null;

	if (!response.ok) {
		const message =
			payload && typeof payload === 'object' && 'message' in payload && typeof payload.message === 'string'
				? payload.message
				: config.i18n.error;
		throw new Error(message);
	}

	return payload as WishlistListResponse;
}

export async function fetchWishlist(): Promise<WishlistListResponse> {
	return requestWishlist('');
}

export async function toggleWishlistItem(productId: number): Promise<WishlistListResponse> {
	return requestWishlist('/items', {
		method: 'POST',
		body: JSON.stringify({ productId }),
	});
}

export async function deleteWishlistItem(productId: number): Promise<WishlistListResponse> {
	return requestWishlist(`/items?productId=${productId}`, {
		method: 'DELETE',
	});
}

export async function mergeWishlist(items: WishlistItem[]): Promise<WishlistListResponse> {
	return requestWishlist('/merge', {
		method: 'POST',
		body: JSON.stringify({ items }),
	});
}
