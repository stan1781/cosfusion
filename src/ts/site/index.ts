document.documentElement.classList.add('js');

declare global {
	interface Window {
		Alpine?: {
			$data: (element: Element) => unknown;
		};
	}
}

type SiteShellState = {
	menuOpen: boolean;
	searchOpen: boolean;
	cartOpen: boolean;
	init: () => void;
	syncDocumentState: () => void;
	toggleMenu: () => void;
	toggleSearch: () => void;
	toggleCart: () => void;
	closeMenu: () => void;
	closeSearch: () => void;
	closeCart: () => void;
	closeAll: () => void;
};

document.addEventListener('alpine:init', () => {
	window.Alpine.data('siteShell', (): SiteShellState => ({
		menuOpen: false,
		searchOpen: false,
		cartOpen: false,
		init(this: SiteShellState) {
			this.syncDocumentState();
		},
		syncDocumentState(this: SiteShellState) {
			document.documentElement.classList.toggle('ts-has-overlay', this.menuOpen || this.searchOpen || this.cartOpen);
		},
		toggleMenu(this: SiteShellState) {
			this.menuOpen = !this.menuOpen;

			if (this.menuOpen) {
				this.searchOpen = false;
				this.cartOpen = false;
			}

			this.syncDocumentState();
		},
		toggleSearch(this: SiteShellState) {
			this.searchOpen = !this.searchOpen;

			if (this.searchOpen) {
				this.menuOpen = false;
				this.cartOpen = false;
				window.requestAnimationFrame(() => {
					const field = document.getElementById('ts-search-field');

					if (field instanceof HTMLInputElement) {
						field.focus();
					}
				});
			}

			this.syncDocumentState();
		},
		toggleCart(this: SiteShellState) {
			this.cartOpen = !this.cartOpen;

			if (this.cartOpen) {
				this.menuOpen = false;
				this.searchOpen = false;
			}

			this.syncDocumentState();
		},
		closeMenu(this: SiteShellState) {
			this.menuOpen = false;
			this.syncDocumentState();
		},
		closeSearch(this: SiteShellState) {
			this.searchOpen = false;
			this.syncDocumentState();
		},
		closeCart(this: SiteShellState) {
			this.cartOpen = false;
			this.syncDocumentState();
		},
		closeAll(this: SiteShellState) {
			this.menuOpen = false;
			this.searchOpen = false;
			this.cartOpen = false;
			this.syncDocumentState();
		},
	}));
});

document.addEventListener('tailwindscore:open-cart', () => {
	const drawer = document.querySelector<HTMLElement>('.ts-site-cart-drawer');

	if (drawer?.dataset.open === 'true') {
		return;
	}

	const header = document.querySelector<HTMLElement>('.ts-site-header');
	const alpine = window.Alpine;

	if (header && alpine && typeof alpine.$data === 'function') {
		const shell = alpine.$data(header) as SiteShellState | undefined;

		if (shell && !shell.cartOpen) {
			shell.cartOpen = true;
			shell.menuOpen = false;
			shell.searchOpen = false;
			shell.syncDocumentState();
			return;
		}
	}

	const trigger = document.querySelector<HTMLButtonElement>('.ts-site-header__action--cart');
	trigger?.click();
});
