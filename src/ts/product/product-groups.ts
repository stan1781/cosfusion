/**
 * 产品组缩略图条：prefetch + 悬停预览 Style 文案。
 *
 * 注意：勿用 pointerenter 绑在祖先上——该事件不冒泡、也不向祖先派发，
 * 悬停子链接时监听器不会触发。
 */

const ROOT_SELECTOR = '[data-ts-product-group]';
const LINK_SELECTOR = 'a[data-ts-product-group-prefetch]';

const prefetched = new Set<string>();

function prefetchUrl(url: string): void {
	if (!url || prefetched.has(url)) {
		return;
	}

	try {
		const resolved = new URL(url, window.location.href);
		if (resolved.origin !== window.location.origin) {
			return;
		}
		if (resolved.pathname === window.location.pathname && resolved.search === window.location.search) {
			return;
		}
		url = resolved.href;
	} catch {
		return;
	}

	prefetched.add(url);

	const link = document.createElement('link');
	link.rel = 'prefetch';
	link.href = url;
	document.head.append(link);
}

function onIntent(event: Event): void {
	const target = event.target;
	if (!(target instanceof Element)) {
		return;
	}
	const anchor = target.closest<HTMLAnchorElement>(LINK_SELECTOR);
	if (!anchor?.href) {
		return;
	}
	prefetchUrl(anchor.href);

	const root = anchor.closest(ROOT_SELECTOR);
	const labelEl = root?.querySelector<HTMLElement>('[data-ts-product-group-current]');
	const preview = anchor.getAttribute('data-ts-product-group-label')?.trim() ?? '';
	if (labelEl && preview) {
		if (!labelEl.dataset.tsPgDefaultLabel) {
			labelEl.dataset.tsPgDefaultLabel = labelEl.textContent?.trim() ?? '';
		}
		labelEl.textContent = preview;
	}
}

function onLeave(event: PointerEvent): void {
	const target = event.currentTarget;
	if (!(target instanceof Element)) {
		return;
	}
	const related = event.relatedTarget;
	if (related instanceof Node && target.contains(related)) {
		return;
	}
	const labelEl = target.querySelector<HTMLElement>('[data-ts-product-group-current]');
	const fallback = labelEl?.dataset.tsPgDefaultLabel;
	if (labelEl && typeof fallback === 'string') {
		labelEl.textContent = fallback;
	}
}

export function initProductGroups(): void {
	const root = document.querySelector(ROOT_SELECTOR);
	if (!root) {
		return;
	}

	// pointerover / focusin 会冒泡，祖先委托才能收到子链接 hover。
	root.addEventListener('pointerover', onIntent);
	root.addEventListener('focusin', onIntent);
	root.addEventListener('pointerleave', onLeave);
}
