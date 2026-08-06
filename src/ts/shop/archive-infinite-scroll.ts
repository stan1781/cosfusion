/**
 * 归档无限滚动：IntersectionObserver 追加下一页商品。
 */

import {
	ensureArchiveNav,
	isSameOriginUrl,
	normalizeUrl,
	prefetchArchiveNav,
	preserveArchiveFilterParams,
	shouldPrefetchArchiveNav,
} from './archive-prefetch';

const ARCHIVE_SELECTOR = '[data-ts-archive]';
const GRID_SELECTOR = '.wc-product-grid';
const PAGINATION_SELECTOR = '.wc-archive__pagination';
const NEXT_LINK_SELECTOR = '.woocommerce-pagination a.next.page-numbers';
const SENTINEL_ATTR = 'data-ts-archive-infinite-sentinel';
const SENTINEL_SELECTOR = `[${SENTINEL_ATTR}]`;
const ROOT_ACTIVE_CLASS = 'is-infinite-scroll';
const ROOT_MARGIN = '240px 0px';

let mainEl: HTMLElement | null = null;
let sentinelEl: HTMLElement | null = null;
let observer: IntersectionObserver | null = null;
let nextUrl: string | null = null;
let loading = false;
let sentinelInView = false;
let generation = 0;

function getArchiveRoot(from: Element | null = null): HTMLElement | null {
	if (from) {
		return from.closest<HTMLElement>(ARCHIVE_SELECTOR);
	}

	return document.querySelector<HTMLElement>(ARCHIVE_SELECTOR);
}

function readNextUrl(root: ParentNode): string | null {
	const link = root.querySelector<HTMLAnchorElement>(NEXT_LINK_SELECTOR);
	const href = link?.getAttribute('href');

	if (!href || !isSameOriginUrl(href)) {
		return null;
	}

	return preserveArchiveFilterParams(normalizeUrl(href));
}

function setInfiniteScrollActive(root: HTMLElement | null, active: boolean): void {
	root?.classList.toggle(ROOT_ACTIVE_CLASS, active);
}

function setSentinelLoading(isLoading: boolean): void {
	if (!sentinelEl) {
		return;
	}

	sentinelEl.classList.toggle('is-loading', isLoading);
	sentinelEl.setAttribute('aria-busy', isLoading ? 'true' : 'false');
}

function disconnectObserver(): void {
	observer?.disconnect();
	observer = null;
	sentinelInView = false;
}

function removeSentinel(): void {
	sentinelEl?.remove();
	sentinelEl = null;
}

function ensureSentinel(main: HTMLElement): HTMLElement {
	const existing = main.querySelector<HTMLElement>(SENTINEL_SELECTOR);

	if (existing) {
		sentinelEl = existing;
		return existing;
	}

	const sentinel = document.createElement('div');
	sentinel.setAttribute(SENTINEL_ATTR, '');
	sentinel.className = 'wc-archive__infinite-sentinel';
	sentinel.setAttribute('aria-hidden', 'true');
	sentinel.innerHTML = '<span class="spinner" aria-hidden="true"></span>';

	const pagination = main.querySelector(PAGINATION_SELECTOR);

	if (pagination?.parentElement) {
		pagination.parentElement.insertBefore(sentinel, pagination);
	} else {
		main.appendChild(sentinel);
	}

	sentinelEl = sentinel;
	return sentinel;
}

function replacePagination(main: HTMLElement, sourceMainHtml: string): void {
	const source = document.createElement('div');
	source.innerHTML = sourceMainHtml;
	const nextPagination = source.querySelector(PAGINATION_SELECTOR);
	const currentPagination = main.querySelector(PAGINATION_SELECTOR);

	if (!currentPagination) {
		return;
	}

	if (nextPagination) {
		currentPagination.innerHTML = nextPagination.innerHTML;
	} else {
		currentPagination.innerHTML = '';
	}
}

function appendProducts(main: HTMLElement, sourceMainHtml: string): number {
	const grid = main.querySelector<HTMLElement>(GRID_SELECTOR);

	if (!grid) {
		return 0;
	}

	const source = document.createElement('div');
	source.innerHTML = sourceMainHtml;
	const items = source.querySelectorAll(`${GRID_SELECTOR} > li`);

	if (items.length === 0) {
		return 0;
	}

	const fragment = document.createDocumentFragment();
	items.forEach((item) => {
		fragment.appendChild(item);
	});
	grid.appendChild(fragment);

	return items.length;
}

async function loadNext(): Promise<void> {
	if (!mainEl || !nextUrl || loading) {
		return;
	}

	const currentGeneration = generation;
	const targetUrl = nextUrl;
	loading = true;
	setSentinelLoading(true);

	try {
		const payload = await ensureArchiveNav(targetUrl, { preserveFilters: false });

		if (currentGeneration !== generation || mainEl === null) {
			return;
		}

		const appended = appendProducts(mainEl, payload.mainHtml);

		if (appended === 0) {
			nextUrl = null;
			teardownInfiniteScroll(getArchiveRoot(mainEl));
			return;
		}

		replacePagination(mainEl, payload.mainHtml);
		nextUrl = readNextUrl(mainEl);
		document.dispatchEvent(new CustomEvent('tailwindscore:archive-updated'));

		if (nextUrl) {
			prefetchArchiveNav(nextUrl, { preserveFilters: false });
		} else {
			teardownInfiniteScroll(getArchiveRoot(mainEl));
			return;
		}
	} catch {
		if (currentGeneration !== generation) {
			return;
		}

		teardownInfiniteScroll(getArchiveRoot(mainEl));
		return;
	} finally {
		if (currentGeneration === generation) {
			loading = false;
			setSentinelLoading(false);
		}
	}

	if (currentGeneration === generation && nextUrl && sentinelInView) {
		void loadNext();
	}
}

function bindObserver(sentinel: HTMLElement): void {
	disconnectObserver();

	if (typeof IntersectionObserver !== 'function') {
		teardownInfiniteScroll(getArchiveRoot(mainEl));
		return;
	}

	observer = new IntersectionObserver(
		(entries) => {
			const entry = entries[0];

			if (!entry) {
				return;
			}

			sentinelInView = entry.isIntersecting;

			if (entry.isIntersecting) {
				void loadNext();
			}
		},
		{ root: null, rootMargin: ROOT_MARGIN, threshold: 0 },
	);

	observer.observe(sentinel);
}

function teardownInfiniteScroll(root: HTMLElement | null): void {
	disconnectObserver();
	removeSentinel();
	nextUrl = null;
	loading = false;
	setInfiniteScrollActive(root, false);
}

export function isArchiveInfiniteScrollActive(): boolean {
	const root = getArchiveRoot(mainEl);
	return Boolean(root?.classList.contains(ROOT_ACTIVE_CLASS));
}

export function resetArchiveInfiniteScroll(main: HTMLElement): void {
	generation += 1;
	loading = false;
	disconnectObserver();
	removeSentinel();
	mainEl = main;

	const root = getArchiveRoot(main);

	if (!root) {
		return;
	}

	if (!shouldPrefetchArchiveNav()) {
		setInfiniteScrollActive(root, false);
		nextUrl = null;
		return;
	}

	nextUrl = readNextUrl(main);

	if (!nextUrl) {
		setInfiniteScrollActive(root, false);
		return;
	}

	setInfiniteScrollActive(root, true);
	const sentinel = ensureSentinel(main);
	setSentinelLoading(false);
	bindObserver(sentinel);
	prefetchArchiveNav(nextUrl, { preserveFilters: false });
}
