/**
 * 产品组缩略图条：hover/focus 一次性 prefetch 目标 PDP。
 */

const ROOT_SELECTOR = '[data-ts-product-group]';
const LINK_SELECTOR = 'a[data-ts-product-group-prefetch]';

const prefetched = new Set<string>();

function prefetchUrl(url: string): void {
	if (!url || prefetched.has(url)) {
		return;
	}
	prefetched.add(url);

	const link = document.createElement('link');
	link.rel = 'prefetch';
	link.href = url;
	link.as = 'document';
	document.head.append(link);
}

function onIntent(event: Event): void {
	const target = event.target;
	if (!(target instanceof Element)) {
		return;
	}
	const anchor = target.closest<HTMLAnchorElement>(LINK_SELECTOR);
	if (!anchor || !anchor.href) {
		return;
	}
	prefetchUrl(anchor.href);
}

export function initProductGroups(): void {
	const root = document.querySelector(ROOT_SELECTOR);
	if (!root) {
		return;
	}

	root.addEventListener('pointerenter', onIntent, true);
	root.addEventListener('focusin', onIntent, true);
}
