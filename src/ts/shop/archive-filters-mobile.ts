/**
 * 移动端归档筛选：工具栏图标打开左侧 Drawer。
 */

const ARCHIVE_ROOT_SELECTOR = '[data-ts-archive]';
const MOBILE_ROOT_SELECTOR = '[data-ts-archive-filters-mobile]';
const TOGGLE_SELECTOR = '[data-ts-archive-filters-toggle]';
const PANEL_SELECTOR = '[data-ts-archive-filters-panel]';
const CLOSE_SELECTOR = '[data-ts-archive-filters-close]';
const COUNT_SELECTOR = '[data-ts-archive-filters-count]';
const OPEN_CLASS = 'is-open';
const BODY_OPEN_CLASS = 'archive-filters-drawer-open';

let returnFocusEl: HTMLElement | null = null;

function getArchiveRoot(): HTMLElement | null {
	return document.querySelector<HTMLElement>(ARCHIVE_ROOT_SELECTOR);
}

function getMobileRoot(): HTMLElement | null {
	return document.querySelector<HTMLElement>(MOBILE_ROOT_SELECTOR);
}

function isOpen(root: HTMLElement): boolean {
	return root.classList.contains(OPEN_CLASS) || root.getAttribute('data-open') === 'true';
}

function syncToggleExpanded(open: boolean): void {
	getArchiveRoot()
		?.querySelectorAll<HTMLButtonElement>(TOGGLE_SELECTOR)
		.forEach((toggle) => {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
}

export function setArchiveFiltersMobileOpen(open: boolean): void {
	const root = getMobileRoot();

	if (!root) {
		return;
	}

	const panel = root.querySelector<HTMLElement>(PANEL_SELECTOR);
	const currentlyOpen = isOpen(root);

	if (open === currentlyOpen) {
		if (open && panel) {
			const focusTarget =
				panel.querySelector<HTMLElement>(CLOSE_SELECTOR) ?? panel;
			focusTarget.focus();
		}
		return;
	}

	root.classList.toggle(OPEN_CLASS, open);
	root.setAttribute('data-open', open ? 'true' : 'false');
	root.setAttribute('aria-hidden', open ? 'false' : 'true');

	if (open) {
		root.removeAttribute('inert');
	} else {
		root.setAttribute('inert', '');
	}

	document.body.classList.toggle(BODY_OPEN_CLASS, open);
	syncToggleExpanded(open);

	if (open) {
		if (!returnFocusEl) {
			returnFocusEl =
				document.activeElement instanceof HTMLElement ? document.activeElement : null;
		}

		window.requestAnimationFrame(() => {
			const focusTarget =
				panel?.querySelector<HTMLElement>(CLOSE_SELECTOR) ?? panel ?? null;
			focusTarget?.focus();
		});
	} else if (returnFocusEl) {
		returnFocusEl.focus();
		returnFocusEl = null;
	}
}

export function closeArchiveFiltersMobile(): void {
	setArchiveFiltersMobileOpen(false);
}

/** 移动端筛选 Drawer 是否打开。 */
export function isArchiveFiltersMobileOpen(): boolean {
	const root = getMobileRoot();
	return Boolean(root && isOpen(root));
}

/**
 * 导航加载时的 loading-host：优先 body（避开 header），否则 panel。
 */
export function getArchiveFiltersMobileLoadingHost(): HTMLElement | null {
	const root = getMobileRoot();

	if (!root || !isOpen(root)) {
		return null;
	}

	const panel = root.querySelector<HTMLElement>(PANEL_SELECTOR);

	if (!panel) {
		return null;
	}

	return (
		panel.querySelector<HTMLElement>('.wc-archive-filters-mobile__body') ?? panel
	);
}

export function syncArchiveFiltersMobileCount(count: number): void {
	const badge = getArchiveRoot()?.querySelector<HTMLElement>(COUNT_SELECTOR);

	if (!badge) {
		return;
	}

	if (count > 0) {
		badge.textContent = String(count);
		badge.hidden = false;
		badge.classList.remove('is-empty');
	} else {
		badge.textContent = '';
		badge.hidden = true;
		badge.classList.add('is-empty');
	}
}

function onDocumentClick(event: MouseEvent): void {
	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	const closeControl = target.closest<HTMLElement>(CLOSE_SELECTOR);

	if (closeControl) {
		event.preventDefault();
		closeArchiveFiltersMobile();
		return;
	}

	const toggle = target.closest<HTMLButtonElement>(TOGGLE_SELECTOR);

	if (!toggle) {
		return;
	}

	const root = getMobileRoot();

	if (!root) {
		return;
	}

	event.preventDefault();

	if (isOpen(root)) {
		closeArchiveFiltersMobile();
		return;
	}

	returnFocusEl = toggle;
	setArchiveFiltersMobileOpen(true);
}

function onDocumentKeydown(event: KeyboardEvent): void {
	if (event.key !== 'Escape') {
		return;
	}

	const root = getMobileRoot();

	if (!root || !isOpen(root)) {
		return;
	}

	event.preventDefault();
	closeArchiveFiltersMobile();
}

function initArchiveFiltersMobile(): void {
	if (!getMobileRoot()) {
		return;
	}

	document.addEventListener('click', onDocumentClick);
	document.addEventListener('keydown', onDocumentKeydown);
	document.addEventListener('tailwindscore:archive-filters-close', () => {
		closeArchiveFiltersMobile();
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initArchiveFiltersMobile, { once: true });
} else {
	initArchiveFiltersMobile();
}
