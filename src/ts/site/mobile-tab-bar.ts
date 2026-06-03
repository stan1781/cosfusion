export {};

type SiteShellState = {
	toggleSearch: () => void;
	toggleCart: () => void;
	closeMenu: () => void;
	closeAccount: () => void;
	menuOpen: boolean;
	cartOpen: boolean;
	searchOpen: boolean;
	accountOpen: boolean;
};

function getSiteShell(): SiteShellState | undefined {
	const header = document.querySelector<HTMLElement>('.ts-site-header');
	const alpine = window.Alpine;

	if (!header || !alpine || typeof alpine.$data !== 'function') {
		return undefined;
	}

	return alpine.$data(header) as SiteShellState | undefined;
}

function initMobileTabBar(): void {
	const bar = document.querySelector<HTMLElement>('.ts-mobile-tab-bar');

	if (!bar) {
		return;
	}

	bar.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const searchTrigger = target.closest<HTMLButtonElement>('[data-ts-mobile-tab-search]');
		const cartTrigger = target.closest<HTMLButtonElement>('[data-ts-mobile-tab-cart]');

		if (searchTrigger) {
			event.preventDefault();
			const shell = getSiteShell();

			if (shell) {
				if (shell.menuOpen) {
					shell.closeMenu();
				}
				if (shell.accountOpen) {
					shell.closeAccount();
				}
				if (!shell.searchOpen) {
					shell.toggleSearch();
				}
			}
			return;
		}

		if (cartTrigger) {
			event.preventDefault();
			const shell = getSiteShell();

			if (shell) {
				if (shell.menuOpen) {
					shell.closeMenu();
				}
				if (shell.accountOpen) {
					shell.closeAccount();
				}
				shell.toggleCart();
				return;
			}

			document.dispatchEvent(new CustomEvent('tailwindscore:open-cart'));
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initMobileTabBar);
} else {
	initMobileTabBar();
}
