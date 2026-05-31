export const MENU_DRAWER_PANEL_ID = 'ts-site-menu-panel';

export const MENU_DRAWER_TRANSITION_MS = 380;

export function getMenuDrawerPanel(): HTMLElement | null {
	return document.getElementById(MENU_DRAWER_PANEL_ID);
}

export function activateMenuDrawerPanel(): void {
	const panel = getMenuDrawerPanel();

	if (!panel || panel.classList.contains('is-active')) {
		return;
	}

	void panel.getBoundingClientRect();

	window.requestAnimationFrame(() => {
		panel.classList.add('is-active');
	});
}

export function deactivateMenuDrawerPanel(): void {
	const panel = getMenuDrawerPanel();

	if (!panel?.classList.contains('is-active')) {
		return;
	}

	panel.classList.remove('is-active');
}

export function isMenuDrawerPanelActive(): boolean {
	return getMenuDrawerPanel()?.classList.contains('is-active') ?? false;
}
