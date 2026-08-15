/**
 * 归档排序页内下拉：开关菜单（选项导航交给 archive-nav）。
 */

export {};

const ORDERING_ROOT_SELECTOR = '[data-ts-archive-ordering]';
const OPEN_CLASS = 'is-open';

function getOpenOrderingRoots(): HTMLElement[] {
	return Array.from(document.querySelectorAll<HTMLElement>(`${ORDERING_ROOT_SELECTOR}.${OPEN_CLASS}`));
}

function setOrderingOpen(root: HTMLElement, open: boolean): void {
	const toggle = root.querySelector<HTMLButtonElement>('.wc-archive-ordering__toggle');
	const menu = root.querySelector<HTMLElement>('.wc-archive-ordering__menu');

	if (!toggle || !menu) {
		return;
	}

	root.classList.toggle(OPEN_CLASS, open);
	toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

	if (open) {
		menu.removeAttribute('hidden');
	} else {
		menu.setAttribute('hidden', '');
	}
}

function closeAllOrderingMenus(except?: HTMLElement | null): void {
	getOpenOrderingRoots().forEach((root) => {
		if (except && root === except) {
			return;
		}

		setOrderingOpen(root, false);
	});
}

function onDocumentClick(event: MouseEvent): void {
	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	const toggle = target.closest<HTMLButtonElement>('.wc-archive-ordering__toggle');

	if (toggle) {
		const root = toggle.closest<HTMLElement>(ORDERING_ROOT_SELECTOR);

		if (!root) {
			return;
		}

		event.preventDefault();
		const nextOpen = !root.classList.contains(OPEN_CLASS);
		closeAllOrderingMenus(nextOpen ? root : null);
		setOrderingOpen(root, nextOpen);
		return;
	}

	if (target.closest(ORDERING_ROOT_SELECTOR)) {
		// 点菜单内选项：留给 archive-nav；导航成功后会关菜单。
		return;
	}

	closeAllOrderingMenus();
}

function onDocumentKeydown(event: KeyboardEvent): void {
	if (event.key !== 'Escape') {
		return;
	}

	if (getOpenOrderingRoots().length === 0) {
		return;
	}

	event.preventDefault();
	closeAllOrderingMenus();
}

function initArchiveOrdering(): void {
	document.addEventListener('click', onDocumentClick);
	document.addEventListener('keydown', onDocumentKeydown);
	document.addEventListener('tailwindscore:archive-updated', () => {
		closeAllOrderingMenus();
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initArchiveOrdering, { once: true });
} else {
	initArchiveOrdering();
}
