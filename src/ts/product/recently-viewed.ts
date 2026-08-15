/**
 * Recently viewed：PDP 写入 localStorage + cookie；挂载点 REST 水合。
 */

import { initProductCarousels } from '../shop/product-carousel';

type RecentlyViewedConfig = {
	restUrl: string;
	storageKey: string;
	cookieName: string;
	maxIds: number;
	showOnCart: boolean;
	showOnProduct: boolean;
	productId: number;
};

declare global {
	interface Window {
		tailwindscoreRecentlyViewed?: RecentlyViewedConfig;
	}
}

function getConfig(): RecentlyViewedConfig | null {
	return window.tailwindscoreRecentlyViewed ?? null;
}

function readIds(storageKey: string): number[] {
	try {
		const raw = window.localStorage.getItem(storageKey);
		if (!raw) {
			return [];
		}
		const parsed = JSON.parse(raw) as unknown;
		if (!Array.isArray(parsed)) {
			return [];
		}
		return parsed.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0);
	} catch {
		return [];
	}
}

function writeIds(storageKey: string, cookieName: string, maxIds: number, ids: number[]): void {
	const unique = Array.from(new Set(ids)).slice(0, maxIds);
	window.localStorage.setItem(storageKey, JSON.stringify(unique));

	const maxAge = 60 * 60 * 24 * 30;
	const secure = window.location.protocol === 'https:' ? '; Secure' : '';
	document.cookie = `${encodeURIComponent(cookieName)}=${encodeURIComponent(unique.join(','))}; Path=/; Max-Age=${maxAge}; SameSite=Lax${secure}`;
}

function readRenderedProductIds(root: ParentNode): number[] {
	return Array.from(root.querySelectorAll<HTMLElement>('[data-product-card][data-product-id]'))
		.map((el) => Number(el.dataset.productId ?? 0))
		.filter((id) => id > 0);
}

function productIdsEqual(a: number[], b: number[]): boolean {
	return a.length === b.length && a.every((id, index) => id === b[index]);
}

function keepExistingHost(host: HTMLElement): void {
	if (readRenderedProductIds(host).length < 1) {
		host.hidden = true;
		host.innerHTML = '';
		return;
	}

	host.hidden = false;
	initProductCarousels(host);
}

export function trackRecentlyViewedProduct(productId: number): void {
	const config = getConfig();
	if (!config || productId < 1) {
		return;
	}

	const next = [productId, ...readIds(config.storageKey).filter((id) => id !== productId)];
	writeIds(config.storageKey, config.cookieName, config.maxIds, next);
}

async function hydrateHost(host: HTMLElement): Promise<void> {
	const config = getConfig();
	if (!config) {
		return;
	}

	const existingIds = readRenderedProductIds(host);
	const ids = readIds(config.storageKey);

	// 无 localStorage：保留 cookie SSR，勿清空（否则首图会先亮再消失）。
	if (ids.length < 1) {
		keepExistingHost(host);
		return;
	}

	const exclude = Number(host.dataset.tsRecentlyViewedExclude ?? '0') || 0;
	const limit = Number(host.dataset.tsRecentlyViewedLimit ?? '8') || 8;
	const url = new URL(config.restUrl, window.location.origin);
	url.searchParams.set('ids', ids.join(','));
	url.searchParams.set('exclude', String(exclude));
	url.searchParams.set('limit', String(limit));

	try {
		const response = await fetch(url.toString(), {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		});

		if (!response.ok) {
			keepExistingHost(host);
			return;
		}

		const data = (await response.json()) as { html?: string };
		const html = typeof data.html === 'string' ? data.html.trim() : '';

		if ('' === html) {
			keepExistingHost(host);
			return;
		}

		const staging = document.createElement('div');
		staging.innerHTML = html;
		const nextIds = readRenderedProductIds(staging);

		// 与 SSR 同序同品：跳过 innerHTML，避免首图拆掉重载闪烁。
		if (productIdsEqual(existingIds, nextIds)) {
			host.hidden = false;
			initProductCarousels(host);
			return;
		}

		host.innerHTML = html;
		host.hidden = false;
		initProductCarousels(host);
	} catch {
		// 水合失败时保留 SSR 内容（若有）。
		keepExistingHost(host);
	}
}

export function initRecentlyViewed(): void {
	const config = getConfig();
	if (!config) {
		return;
	}

	const hosts = Array.from(document.querySelectorAll<HTMLElement>('[data-ts-recently-viewed-host]'));

	// 先挂 SSR 轮播，再水合；写 cookie 放到水合后，避免本页列表被「当前品前置」打乱后整段替换。
	hosts.forEach((host) => {
		if (host.querySelector('[data-ts-product-carousel]')) {
			initProductCarousels(host);
		}
	});

	void Promise.all(hosts.map((host) => hydrateHost(host))).then(() => {
		if (config.productId > 0) {
			trackRecentlyViewedProduct(config.productId);
		}
	});
}

/** 购物车页空态重绘后重新挂载并水合。 */
export function mountRecentlyViewedHosts(root: ParentNode = document): void {
	const config = getConfig();
	if (!config?.showOnCart) {
		return;
	}

	root.querySelectorAll<HTMLElement>('[data-ts-recently-viewed-host]').forEach((host) => {
		void hydrateHost(host);
	});
}
