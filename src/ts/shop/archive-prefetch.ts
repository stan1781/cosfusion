/**
 * 归档 AJAX：按 URL 拉取整页 HTML，缓存并抽出主区片段。
 */

const MAIN_SELECTOR = '.wc-archive-main';
const FILTERS_SELECTOR = '[data-ts-archive-filters]';
const FILTER_GROUPS_SELECTOR = '.wc-archive-sidebar__groups';
const NAV_LINK_SELECTOR = '[data-ts-archive-nav]';
const ARCHIVE_SELECTOR = '[data-ts-archive]';
const IDLE_PREFETCH_LIMIT = 5;

/** 导航时需在分类切换间保留的属性筛选 query 键前缀。 */
const FILTER_QUERY_PREFIXES = ['filter_', 'query_type_'] as const;

export type ArchiveNavPayload = {
	mainHtml: string;
	title: string;
	activeNavHrefs: ReadonlySet<string>;
	/** 服务端按当前列表裁剪后的筛选 chip 组 HTML。 */
	filtersGroupsHtml: string | null;
};

const navCache = new Map<string, Promise<ArchiveNavPayload>>();

export function isSameOriginUrl(url: string): boolean {
	try {
		const parsed = new URL(url, window.location.href);
		return parsed.origin === window.location.origin;
	} catch {
		return false;
	}
}

export function normalizeUrl(url: string): string {
	return new URL(url, window.location.href).href;
}

/** 忽略 query 参数顺序，便于 active href 比对。 */
export function canonicalizeUrl(url: string): string {
	const parsed = new URL(url, window.location.href);
	const entries = [...parsed.searchParams.entries()].sort(([a], [b]) => a.localeCompare(b));
	parsed.search = '';
	entries.forEach(([key, value]) => {
		parsed.searchParams.append(key, value);
	});
	return parsed.href;
}

function urlsMatch(a: string, b: string): boolean {
	return canonicalizeUrl(a) === canonicalizeUrl(b);
}

function isAttributeFilterNavLink(el: Element): boolean {
	const type = el.closest<HTMLElement>('[data-ts-archive-nav]')?.dataset.tsArchiveNavType
		?? el.getAttribute('data-ts-archive-nav-type');

	if (type === 'attribute') {
		return true;
	}

	if (type === 'taxonomy') {
		return false;
	}

	return Boolean(el.closest('.wc-archive-sidebar__group[class*="attr-"]'));
}

/** 分类 / Series chip：切换分类时不应保留属性筛选。 */
export function isTaxonomyNavLink(el: Element): boolean {
	const type = el.closest<HTMLElement>('[data-ts-archive-nav]')?.dataset.tsArchiveNavType
		?? el.getAttribute('data-ts-archive-nav-type');

	if (type === 'taxonomy') {
		return true;
	}

	if (type === 'attribute') {
		return false;
	}

	const group = el.closest('.wc-archive-sidebar__group');
	if (!group) {
		return false;
	}

	return (
		group.classList.contains('wc-archive-sidebar__group--categories') ||
		group.classList.contains('wc-archive-sidebar__group--series')
	);
}

/**
 * 分类 / 排序 / 分页导航时保留当前 filter_*；属性 chip 自身 URL 已是终态，勿回填。
 */
export function preserveArchiveFilterParams(
	url: string,
	options: { preserveFilters?: boolean } = {},
): string {
	const preserveFilters = options.preserveFilters !== false;
	const current = new URL(window.location.href);
	const target = new URL(url, window.location.href);

	if (!preserveFilters) {
		return target.href;
	}

	current.searchParams.forEach((value, key) => {
		const isFilterKey = FILTER_QUERY_PREFIXES.some((prefix) => key.startsWith(prefix));

		if (!isFilterKey) {
			return;
		}

		if (!target.searchParams.has(key)) {
			target.searchParams.set(key, value);
		}
	});

	return target.href;
}

function resolveArchiveNavUrl(
	url: string,
	options: { preserveFilters?: boolean } = {},
): string {
	return preserveArchiveFilterParams(normalizeUrl(url), options);
}

function extractActiveNavHrefs(doc: Document): ReadonlySet<string> {
	const hrefs = new Set<string>();

	doc.querySelectorAll<HTMLAnchorElement>(`${FILTERS_SELECTOR} ${NAV_LINK_SELECTOR}.is-active`).forEach(
		(link) => {
			const href = link.getAttribute('href');

			if (href) {
				hrefs.add(canonicalizeUrl(href));
			}
		},
	);

	return hrefs;
}

async function fetchArchiveNavPayload(url: string): Promise<ArchiveNavPayload> {
	const response = await fetch(url, {
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
	});

	if (!response.ok) {
		throw new Error(`Archive navigation failed: ${response.status}`);
	}

	const html = await response.text();
	const doc = new DOMParser().parseFromString(html, 'text/html');
	const nextMain = doc.querySelector<HTMLElement>(MAIN_SELECTOR);

	if (!nextMain) {
		throw new Error('Archive main fragment missing');
	}

	const groups = doc.querySelector(FILTER_GROUPS_SELECTOR);

	return {
		mainHtml: nextMain.innerHTML,
		title: doc.querySelector('title')?.textContent?.trim() ?? '',
		activeNavHrefs: extractActiveNavHrefs(doc),
		filtersGroupsHtml: groups ? groups.innerHTML : null,
	};
}

export function ensureArchiveNav(
	url: string,
	options: { preserveFilters?: boolean } = {},
): Promise<ArchiveNavPayload> {
	const targetUrl = resolveArchiveNavUrl(url, options);
	let cached = navCache.get(targetUrl);

	if (!cached) {
		cached = fetchArchiveNavPayload(targetUrl).catch((error: unknown) => {
			navCache.delete(targetUrl);
			throw error;
		});
		navCache.set(targetUrl, cached);
	}

	return cached;
}

export function shouldPrefetchArchiveNav(): boolean {
	const connection = (
		navigator as Navigator & {
			connection?: {
				saveData?: boolean;
				effectiveType?: string;
			};
		}
	).connection;

	if (!connection) {
		return true;
	}

	if (connection.saveData) {
		return false;
	}

	const effectiveType = connection.effectiveType;

	if (effectiveType === 'slow-2g' || effectiveType === '2g') {
		return false;
	}

	return true;
}

export function prefetchArchiveNav(
	url: string,
	options: { preserveFilters?: boolean } = {},
): void {
	if (!shouldPrefetchArchiveNav()) {
		return;
	}

	const targetUrl = resolveArchiveNavUrl(url, options);

	if (urlsMatch(targetUrl, window.location.href)) {
		return;
	}

	void ensureArchiveNav(url, options).catch(() => {
		// 预读失败静默；正式导航时会重试。
	});
}

export function prefetchArchiveNavFromTarget(target: EventTarget | null): void {
	if (!(target instanceof Element)) {
		return;
	}

	const navLink = target.closest<HTMLAnchorElement>(`${ARCHIVE_SELECTOR} ${NAV_LINK_SELECTOR}`);

	if (!navLink || navLink.classList.contains('is-active')) {
		return;
	}

	const href = navLink.getAttribute('href');

	if (!href || !isSameOriginUrl(href)) {
		return;
	}

	prefetchArchiveNav(href, {
		preserveFilters: !isAttributeFilterNavLink(navLink) && !isTaxonomyNavLink(navLink),
	});
}

function scheduleIdleTask(callback: () => void): void {
	if (typeof requestIdleCallback === 'function') {
		requestIdleCallback(() => callback());
		return;
	}

	window.setTimeout(callback, 0);
}

export function prefetchArchiveNavLinks(
	root: ParentNode | null,
	options: { limit?: number } = {},
): void {
	if (!root || !shouldPrefetchArchiveNav()) {
		return;
	}

	const limit = options.limit ?? IDLE_PREFETCH_LIMIT;
	const hrefs: string[] = [];
	const queue: Array<{ url: string; preserveFilters: boolean }> = [];

	root.querySelectorAll<HTMLAnchorElement>(NAV_LINK_SELECTOR).forEach((link) => {
		if (link.classList.contains('is-active')) {
			return;
		}

		const href = link.getAttribute('href');

		if (!href || !isSameOriginUrl(href)) {
			return;
		}

		const preserveFilters = !isAttributeFilterNavLink(link) && !isTaxonomyNavLink(link);
		const normalized = resolveArchiveNavUrl(href, { preserveFilters });

		if (urlsMatch(normalized, window.location.href) || hrefs.includes(normalized)) {
			return;
		}

		hrefs.push(normalized);
		queue.push({ url: href, preserveFilters });
	});

	const limited = queue.slice(0, limit);
	let index = 0;

	const runNext = (): void => {
		const item = limited[index];

		if (!item) {
			return;
		}

		index += 1;
		prefetchArchiveNav(item.url, { preserveFilters: item.preserveFilters });
		scheduleIdleTask(runNext);
	};

	if (limited.length > 0) {
		scheduleIdleTask(runNext);
	}
}

export { isAttributeFilterNavLink };
