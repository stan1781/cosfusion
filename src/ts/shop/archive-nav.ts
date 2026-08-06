/**
 * 商店 / 分类归档 AJAX 导航：筛选 chip、排序、编号分页、history。
 */

import { markLoadingHost, setLoadingHost } from '../ui/loading-host';
import {
	closeArchiveFiltersMobile,
	getArchiveFiltersMobileLoadingHost,
	isArchiveFiltersMobileOpen,
	syncArchiveFiltersMobileCount,
} from './archive-filters-mobile';
import {
	isArchiveInfiniteScrollActive,
	resetArchiveInfiniteScroll,
} from './archive-infinite-scroll';
import {
	canonicalizeUrl,
	ensureArchiveNav,
	isAttributeFilterNavLink,
	isSameOriginUrl,
	isTaxonomyNavLink,
	normalizeUrl,
	prefetchArchiveNavFromTarget,
	preserveArchiveFilterParams,
} from './archive-prefetch';

const ARCHIVE_SELECTOR = '[data-ts-archive]';
const MAIN_SELECTOR = '.wc-archive-main';
const FILTER_GROUPS_SELECTOR = '.wc-archive-sidebar__groups';
const NAV_LINK_SELECTOR = '[data-ts-archive-nav]';
const PAGINATION_LINK_SELECTOR = '.woocommerce-pagination a.page-numbers';
const ORDERING_SELECT_SELECTOR = '.woocommerce-ordering select';
const LIVE_REGION_ID = 'ts-archive-live';
const FILTER_QUERY_PREFIXES = ['filter_', 'query_type_'] as const;

interface NavigateOptions {
	pushState?: boolean;
	pendingLink?: HTMLAnchorElement | null;
	preserveFilters?: boolean;
}

let archiveRoot: HTMLElement | null = null;
let mainEl: HTMLElement | null = null;
let liveRegion: HTMLElement | null = null;
let navigationGeneration = 0;
let isPopstateNavigation = false;

function getArchiveRoot(): HTMLElement | null {
	return document.querySelector<HTMLElement>(ARCHIVE_SELECTOR);
}

function isModifiedClick(event: MouseEvent): boolean {
	return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
}

function urlsMatch(a: string, b: string): boolean {
	return normalizeUrl(a) === normalizeUrl(b);
}

function fallbackNavigate(url: string): void {
	window.location.assign(url);
}

function ensureLiveRegion(root: HTMLElement): HTMLElement {
	const existing = root.querySelector<HTMLElement>(`#${LIVE_REGION_ID}`);

	if (existing) {
		return existing;
	}

	const region = document.createElement('div');
	region.id = LIVE_REGION_ID;
	region.className = 'screen-reader-text';
	region.setAttribute('aria-live', 'polite');
	region.setAttribute('aria-atomic', 'true');
	root.appendChild(region);
	return region;
}

function collectFilterParams(from: URL): Map<string, string> {
	const map = new Map<string, string>();

	from.searchParams.forEach((value, key) => {
		if (FILTER_QUERY_PREFIXES.some((prefix) => key.startsWith(prefix))) {
			map.set(key, value);
		}
	});

	return map;
}

function applyFilterParams(url: URL, filters: Map<string, string>): void {
	[...url.searchParams.keys()].forEach((key) => {
		if (FILTER_QUERY_PREFIXES.some((prefix) => key.startsWith(prefix))) {
			url.searchParams.delete(key);
		}
	});

	filters.forEach((value, key) => {
		url.searchParams.set(key, value);
	});
}

/**
 * AJAX 只换主区。按目标 URL 重写筛选链接的 path / filter query，避免分类与属性错位。
 */
function syncFilterNavHrefs(targetUrl: string): void {
	if (!archiveRoot) {
		return;
	}

	const target = new URL(targetUrl, window.location.href);
	const targetFilters = collectFilterParams(target);
	const archiveBase = new URL(target.href);

	[...archiveBase.searchParams.keys()].forEach((key) => {
		if (
			FILTER_QUERY_PREFIXES.some((prefix) => key.startsWith(prefix)) ||
			key === 'orderby' ||
			key === 'paged'
		) {
			archiveBase.searchParams.delete(key);
		}
	});

	archiveRoot.querySelectorAll<HTMLAnchorElement>(NAV_LINK_SELECTOR).forEach((link) => {
		const href = link.getAttribute('href');

		if (!href) {
			return;
		}

		try {
			const current = new URL(href, window.location.href);

			if (isAttributeFilterNavLink(link)) {
				const group = link.closest<HTMLElement>('.wc-archive-sidebar__group');
				const groupClass = group
					? Array.from(group.classList).find((name) =>
							name.startsWith('wc-archive-sidebar__group--attr-'),
						)
					: undefined;
				const attrSlug = groupClass?.replace('wc-archive-sidebar__group--attr-', '') ?? '';
				const next = new URL(archiveBase.href);
				const nextFilters = new Map(targetFilters);

				if (attrSlug) {
					nextFilters.delete(`filter_${attrSlug}`);
					nextFilters.delete(`query_type_${attrSlug}`);
				}

				const linkFilter = current.searchParams.get(`filter_${attrSlug}`);
				const linkType = current.searchParams.get(`query_type_${attrSlug}`);

				if (linkFilter) {
					nextFilters.set(`filter_${attrSlug}`, linkFilter);
					nextFilters.set(`query_type_${attrSlug}`, linkType || 'or');
				}

				applyFilterParams(next, nextFilters);
				link.setAttribute('href', next.href);
				return;
			}

			// 分类 chip：始终保持干净 URL，不附带属性筛选。
			const next = new URL(current.href);
			applyFilterParams(next, new Map());
			next.searchParams.delete('orderby');
			next.searchParams.delete('paged');
			link.setAttribute('href', next.href);
		} catch {
			// 非法 href 跳过
		}
	});
}

function syncFilterActiveFromHrefs(activeNavHrefs: ReadonlySet<string>): void {
	if (!archiveRoot) {
		return;
	}

	archiveRoot.querySelectorAll<HTMLAnchorElement>(NAV_LINK_SELECTOR).forEach((link) => {
		const href = link.getAttribute('href');
		const isActive = href ? activeNavHrefs.has(canonicalizeUrl(href)) : false;

		link.classList.toggle('is-active', isActive);

		if (isActive) {
			link.setAttribute('aria-current', 'page');
		} else {
			link.removeAttribute('aria-current');
		}
	});
}

function countActiveAttributeFilters(url: string): number {
	const parsed = new URL(url, window.location.href);
	let count = 0;

	parsed.searchParams.forEach((_value, key) => {
		if (key.startsWith('filter_')) {
			count += 1;
		}
	});

	return count;
}

function announceResultCount(main: HTMLElement): void {
	if (!liveRegion) {
		return;
	}

	const resultCount = main.querySelector('.woocommerce-result-count')?.textContent?.trim();

	if (resultCount) {
		liveRegion.textContent = resultCount;
	}
}

function moveFocusAfterNavigation(main: HTMLElement): void {
	const focusTarget =
		main.querySelector<HTMLElement>('.wc-archive__toolbar') ??
		main.querySelector<HTMLElement>('.wc-product-grid') ??
		main;

	if (!focusTarget.hasAttribute('tabindex')) {
		focusTarget.setAttribute('tabindex', '-1');
	}

	focusTarget.focus({ preventScroll: true });
}

function scrollArchiveToTop(): void {
	window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
}

function setPendingLink(link: HTMLAnchorElement | null | undefined, isPending: boolean): void {
	if (!archiveRoot || !link) {
		return;
	}

	archiveRoot.querySelectorAll<HTMLAnchorElement>(`${NAV_LINK_SELECTOR}.is-pending`).forEach((node) => {
		if (node !== link) {
			node.classList.remove('is-pending');
		}
	});

	link.classList.toggle('is-pending', isPending);
}

async function navigateArchive(url: string, options: NavigateOptions = {}): Promise<void> {
	const { pushState = true, pendingLink = null, preserveFilters = true } = options;

	if (!archiveRoot || !mainEl) {
		fallbackNavigate(url);
		return;
	}

	const targetUrl = preserveArchiveFilterParams(normalizeUrl(url), { preserveFilters });

	if (pushState && urlsMatch(targetUrl, window.location.href)) {
		return;
	}

	const generation = ++navigationGeneration;
	const filtersLoadingHost = isArchiveFiltersMobileOpen()
		? getArchiveFiltersMobileLoadingHost()
		: null;

	setPendingLink(pendingLink, true);
	setLoadingHost(mainEl, true);
	setLoadingHost(filtersLoadingHost, true);

	try {
		const payload = await ensureArchiveNav(targetUrl, { preserveFilters: false });

		if (generation !== navigationGeneration) {
			return;
		}

		mainEl.innerHTML = payload.mainHtml;

		// 用目标页的筛选组替换（属性 chip 随当前列表裁剪）。
		if (payload.filtersGroupsHtml !== null) {
			archiveRoot.querySelectorAll(FILTER_GROUPS_SELECTOR).forEach((groups) => {
				groups.innerHTML = payload.filtersGroupsHtml as string;
			});
		}

		syncFilterNavHrefs(targetUrl);
		syncFilterActiveFromHrefs(payload.activeNavHrefs);
		resetArchiveInfiniteScroll(mainEl);

		if (payload.title) {
			document.title = payload.title;
		}

		if (pushState) {
			history.pushState({ tsArchive: true }, '', targetUrl);
		}

		announceResultCount(mainEl);
		syncArchiveFiltersMobileCount(countActiveAttributeFilters(targetUrl));
		scrollArchiveToTop();
		moveFocusAfterNavigation(mainEl);
		closeArchiveFiltersMobile();
		document.dispatchEvent(new CustomEvent('tailwindscore:archive-filters-close'));
		document.dispatchEvent(new CustomEvent('tailwindscore:archive-updated'));
	} catch {
		if (generation !== navigationGeneration) {
			return;
		}

		fallbackNavigate(targetUrl);
	} finally {
		if (generation === navigationGeneration) {
			setPendingLink(pendingLink, false);
			setLoadingHost(mainEl, false);
			setLoadingHost(filtersLoadingHost, false);
		}
	}
}

function buildOrderByUrl(select: HTMLSelectElement): string {
	const form = select.closest('form');
	const url = new URL(form?.getAttribute('action') || window.location.href, window.location.href);
	const formData = new FormData(form ?? undefined);

	formData.forEach((value, key) => {
		if (typeof value === 'string') {
			url.searchParams.set(key, value);
		}
	});

	url.searchParams.set(select.name || 'orderby', select.value);

	return preserveArchiveFilterParams(url.href);
}

function handleDocumentClick(event: MouseEvent): void {
	if (!archiveRoot || !mainEl || isModifiedClick(event)) {
		return;
	}

	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	const navLink = target.closest<HTMLAnchorElement>(`${ARCHIVE_SELECTOR} ${NAV_LINK_SELECTOR}`);

	if (navLink) {
		const href = navLink.getAttribute('href');

		if (!href || !isSameOriginUrl(href)) {
			return;
		}

		event.preventDefault();

		// 属性 term 再点一次 → 清除该维（导航到同组 All，即首项）。
		let targetHref = href;
		if (
			navLink.classList.contains('is-active') &&
			isAttributeFilterNavLink(navLink) &&
			href.includes('filter_')
		) {
			const group = navLink.closest('.wc-archive-sidebar__group');
			const allLink = group?.querySelector<HTMLAnchorElement>(NAV_LINK_SELECTOR);
			targetHref = allLink?.getAttribute('href') || href;
		}

		void navigateArchive(targetHref, {
			pushState: true,
			pendingLink: navLink,
			// 分类切换清除属性；属性 chip URL 已是终态；仅排序/分页保留。
			preserveFilters: !isAttributeFilterNavLink(navLink) && !isTaxonomyNavLink(navLink),
		});
		return;
	}

	if (isArchiveInfiniteScrollActive()) {
		return;
	}

	const paginationLink = target.closest<HTMLAnchorElement>(
		`${ARCHIVE_SELECTOR} ${MAIN_SELECTOR} ${PAGINATION_LINK_SELECTOR}`,
	);

	if (paginationLink) {
		const href = paginationLink.getAttribute('href');

		if (!href || !isSameOriginUrl(href) || paginationLink.classList.contains('current')) {
			return;
		}

		event.preventDefault();
		void navigateArchive(href, { pushState: true });
	}
}

function handleOrderingSubmit(event: SubmitEvent): void {
	if (!archiveRoot || !mainEl) {
		return;
	}

	const form = event.target;

	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	if (!form.closest(`${ARCHIVE_SELECTOR} ${MAIN_SELECTOR} .woocommerce-ordering`)) {
		return;
	}

	event.preventDefault();
	event.stopPropagation();

	const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
	const formData = new FormData(form);

	formData.forEach((value, key) => {
		if (typeof value === 'string') {
			url.searchParams.set(key, value);
		}
	});

	void navigateArchive(preserveArchiveFilterParams(url.href), { pushState: true });
}

function handleDocumentChange(event: Event): void {
	if (!archiveRoot || !mainEl) {
		return;
	}

	const target = event.target;

	if (!(target instanceof HTMLSelectElement)) {
		return;
	}

	if (!target.matches(`${ARCHIVE_SELECTOR} ${MAIN_SELECTOR} ${ORDERING_SELECT_SELECTOR}`)) {
		return;
	}

	event.preventDefault();
	event.stopImmediatePropagation();
	void navigateArchive(buildOrderByUrl(target), { pushState: true });
}

function handlePopState(): void {
	if (!archiveRoot || !mainEl || isPopstateNavigation) {
		return;
	}

	isPopstateNavigation = true;
	void navigateArchive(window.location.href, {
		pushState: false,
		preserveFilters: false,
	}).finally(() => {
		isPopstateNavigation = false;
	});
}

function bindArchiveNavPrefetch(): void {
	document.addEventListener('mouseover', (event) => {
		prefetchArchiveNavFromTarget(event.target);
	});

	document.addEventListener(
		'pointerdown',
		(event) => {
			if (event.pointerType === 'mouse' && event.button !== 0) {
				return;
			}

			prefetchArchiveNavFromTarget(event.target);
		},
		{ passive: true },
	);
}

function initArchiveNav(): void {
	archiveRoot = getArchiveRoot();

	if (!archiveRoot) {
		return;
	}

	mainEl = archiveRoot.querySelector<HTMLElement>(MAIN_SELECTOR);

	if (!mainEl) {
		return;
	}

	markLoadingHost(mainEl);
	liveRegion = ensureLiveRegion(archiveRoot);
	bindArchiveNavPrefetch();
	resetArchiveInfiniteScroll(mainEl);

	document.addEventListener('click', handleDocumentClick);
	document.addEventListener('change', handleDocumentChange, true);
	document.addEventListener('submit', handleOrderingSubmit, true);
	window.addEventListener('popstate', handlePopState);

	if (!history.state || !(history.state as { tsArchive?: boolean }).tsArchive) {
		history.replaceState({ tsArchive: true }, '', window.location.href);
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initArchiveNav);
} else {
	initArchiveNav();
}
