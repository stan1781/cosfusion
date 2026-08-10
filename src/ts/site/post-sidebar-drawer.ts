/**
 * 文章页侧栏：&lt; lg 时以右侧 Drawer 开合（桌面为内联列）。
 */

const LAYOUT_SELECTOR = '[data-ts-post-layout]';
const PANEL_SELECTOR = '[data-ts-post-sidebar-panel]';
const TOGGLE_SELECTOR = '[data-ts-post-sidebar-toggle]';
const CLOSE_SELECTOR = '[data-ts-post-sidebar-close]';
const BACKDROP_SELECTOR = '[data-ts-post-sidebar-backdrop]';
const OPEN_CLASS = 'is-post-sidebar-open';
const BODY_OPEN_CLASS = 'post-sidebar-drawer-open';

let returnFocusEl: HTMLElement | null = null;

function getLayout(): HTMLElement | null {
	return document.querySelector<HTMLElement>(LAYOUT_SELECTOR);
}

function getPanel(): HTMLElement | null {
	return document.querySelector<HTMLElement>(PANEL_SELECTOR);
}

function getBackdrop(): HTMLElement | null {
	return document.querySelector<HTMLElement>(BACKDROP_SELECTOR);
}

function isDesktopInline(): boolean {
	return window.matchMedia('(width >= 64rem)').matches;
}

function isOpen(): boolean {
	return document.documentElement.classList.contains(OPEN_CLASS);
}

function syncToggleExpanded(open: boolean): void {
	document.querySelectorAll<HTMLButtonElement>(TOGGLE_SELECTOR).forEach((toggle) => {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
}

function setPostSidebarOpen(open: boolean): void {
	const layout = getLayout();
	const panel = getPanel();
	const backdrop = getBackdrop();

	if (!layout || !panel) {
		return;
	}

	if (isDesktopInline()) {
		open = false;
	}

	const currentlyOpen = isOpen();

	if (open === currentlyOpen) {
		if (open) {
			const focusTarget =
				panel.querySelector<HTMLElement>(CLOSE_SELECTOR) ?? panel;
			focusTarget.focus();
		}
		return;
	}

	document.documentElement.classList.toggle(OPEN_CLASS, open);
	document.body.classList.toggle(BODY_OPEN_CLASS, open);
	layout.classList.toggle(OPEN_CLASS, open);
	panel.classList.toggle('is-open', open);
	panel.setAttribute('data-open', open ? 'true' : 'false');

	if (backdrop) {
		backdrop.hidden = !open;
	}

	if (open) {
		panel.removeAttribute('inert');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('role', 'dialog');
	} else {
		panel.setAttribute('inert', '');
		panel.removeAttribute('aria-modal');
		panel.removeAttribute('role');
	}

	syncToggleExpanded(open);

	if (open) {
		if (!returnFocusEl) {
			returnFocusEl =
				document.activeElement instanceof HTMLElement ? document.activeElement : null;
		}

		window.requestAnimationFrame(() => {
			const focusTarget =
				panel.querySelector<HTMLElement>(CLOSE_SELECTOR) ?? panel;
			focusTarget.focus();
		});
	} else if (returnFocusEl) {
		returnFocusEl.focus();
		returnFocusEl = null;
	}
}

function closePostSidebar(): void {
	setPostSidebarOpen(false);
}

function onDocumentClick(event: MouseEvent): void {
	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	if (target.closest(CLOSE_SELECTOR)) {
		event.preventDefault();
		closePostSidebar();
		return;
	}

	const toggle = target.closest<HTMLButtonElement>(TOGGLE_SELECTOR);

	if (!toggle) {
		return;
	}

	event.preventDefault();

	if (isDesktopInline()) {
		return;
	}

	if (isOpen()) {
		closePostSidebar();
		return;
	}

	returnFocusEl = toggle;
	setPostSidebarOpen(true);
}

function onDocumentKeydown(event: KeyboardEvent): void {
	if (event.key !== 'Escape' || !isOpen()) {
		return;
	}

	event.preventDefault();
	closePostSidebar();
}

function onViewportChange(): void {
	if (isDesktopInline() && isOpen()) {
		closePostSidebar();
	}

	const panel = getPanel();

	if (!panel) {
		return;
	}

	// 桌面内联：面板始终可交互；移动默认 inert，直至打开。
	if (isDesktopInline()) {
		panel.removeAttribute('inert');
		panel.removeAttribute('aria-modal');
		panel.removeAttribute('role');
		panel.removeAttribute('data-open');
		panel.classList.remove('is-open');
	} else if (!isOpen()) {
		panel.setAttribute('inert', '');
	}
}

function initPostSidebarDrawer(): void {
	if (!getLayout() || !getPanel()) {
		return;
	}

	onViewportChange();
	document.addEventListener('click', onDocumentClick);
	document.addEventListener('keydown', onDocumentKeydown);
	window.matchMedia('(width >= 64rem)').addEventListener('change', onViewportChange);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initPostSidebarDrawer, { once: true });
} else {
	initPostSidebarDrawer();
}
