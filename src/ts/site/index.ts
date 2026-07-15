export {};

import type { AlpineMagics } from '../types/alpine';
import {
	MENU_DRAWER_TRANSITION_MS,
	activateMenuDrawerPanel,
	deactivateMenuDrawerPanel,
	getMenuDrawerPanel,
	isMenuDrawerPanelActive,
} from './menu-drawer-panel';
import { closeLoginModal, isLoginModalOpen } from '../ui/login-modal';

document.documentElement.classList.add('js');

let mobileMenuResetTimer: number | undefined;
let menuDrawerClosing = false;

function cancelMobileMenuReset(): void {
	if (mobileMenuResetTimer !== undefined) {
		window.clearTimeout(mobileMenuResetTimer);
		mobileMenuResetTimer = undefined;
	}
}

function dispatchMobileMenuReset(): void {
	document.dispatchEvent(new CustomEvent('tailwindscore:mobile-menu-reset'));
}

function scheduleMenuDrawerCloseFallback(shell: SiteShellState): void {
	cancelMobileMenuReset();
	mobileMenuResetTimer = window.setTimeout(() => {
		mobileMenuResetTimer = undefined;
		menuDrawerClosing = false;
		finishMenuDrawerClose(shell);
	}, MENU_DRAWER_TRANSITION_MS);
}

function finishMenuDrawerClose(shell: SiteShellState): void {
	if (!shell.menuOpen) {
		return;
	}

	menuDrawerClosing = false;
	shell.menuOpen = false;
	shell.syncDocumentState();
	cancelMobileMenuReset();

	window.requestAnimationFrame(() => {
		dispatchMobileMenuReset();
	});
}

type SiteShellData = {
	menuOpen: boolean;
	searchOpen: boolean;
	cartOpen: boolean;
	accountOpen: boolean;
	init: () => void;
	syncDocumentState: () => void;
	toggleMenu: () => void;
	toggleSearch: () => void;
	toggleCart: () => void;
	toggleAccount: () => void;
	closeMenu: () => void;
	closeSearch: () => void;
	closeCart: () => void;
	closeAccount: () => void;
	closeAll: () => void;
	onMenuDrawerTransitionEnd: (event: TransitionEvent) => void;
};

type SiteShellState = AlpineMagics & SiteShellData;

function focusSearchInput(scheduleTick: (callback: () => void) => void): void {
	const focus = (): void => {
		const field = document.getElementById('ts-search-field');

		if (field instanceof HTMLInputElement) {
			field.focus({ preventScroll: true });
		}
	};

	focus();
	scheduleTick(focus);
}

function getSiteShell(): SiteShellState | undefined {
	const header = document.querySelector<HTMLElement>('.ts-site-header');
	const alpine = window.Alpine;

	if (!header || !alpine || typeof alpine.$data !== 'function') {
		return undefined;
	}

	return alpine.$data(header) as SiteShellState | undefined;
}

document.addEventListener('alpine:init', () => {
	window.Alpine.data('siteShell', (): SiteShellData => ({
		menuOpen: false,
		searchOpen: false,
		cartOpen: false,
		accountOpen: false,
		init(this: SiteShellState) {
			menuDrawerClosing = false;
			deactivateMenuDrawerPanel();
			this.syncDocumentState();
		},
		syncDocumentState(this: SiteShellState) {
			document.documentElement.classList.toggle('ts-has-overlay', this.menuOpen || this.searchOpen || this.cartOpen);
		},
		toggleMenu(this: SiteShellState) {
			if (this.menuOpen) {
				this.closeMenu();
				return;
			}

			menuDrawerClosing = false;
			cancelMobileMenuReset();
			this.searchOpen = false;
			this.cartOpen = false;
			this.accountOpen = false;
			this.menuOpen = true;
			this.syncDocumentState();

			this.$nextTick(() => {
				activateMenuDrawerPanel();
			});
		},
		closeMenu(this: SiteShellState) {
			if (!this.menuOpen) {
				return;
			}

			if (!isMenuDrawerPanelActive()) {
				finishMenuDrawerClose(this);
				return;
			}

			menuDrawerClosing = true;
			deactivateMenuDrawerPanel();
			scheduleMenuDrawerCloseFallback(this);
		},
		toggleSearch(this: SiteShellState) {
			this.searchOpen = !this.searchOpen;

			if (this.searchOpen) {
				if (this.menuOpen) {
					this.closeMenu();
				}
				this.cartOpen = false;
				this.accountOpen = false;
				closeLoginModal();
				focusSearchInput((callback) => this.$nextTick(callback));
			}

			this.syncDocumentState();
		},
		toggleCart(this: SiteShellState) {
			this.cartOpen = !this.cartOpen;

			if (this.cartOpen) {
				if (this.menuOpen) {
					this.closeMenu();
				}
				this.searchOpen = false;
				this.accountOpen = false;
				closeLoginModal();
				document.dispatchEvent(new CustomEvent('tailwindscore:cart-drawer-opened'));
			}

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
		toggleAccount(this: SiteShellState) {
			this.accountOpen = !this.accountOpen;

			if (this.accountOpen) {
				if (this.menuOpen) {
					this.closeMenu();
				}
				this.searchOpen = false;
				this.cartOpen = false;
				closeLoginModal();
			}

			this.syncDocumentState();
		},
		closeAccount(this: SiteShellState) {
			this.accountOpen = false;
			this.syncDocumentState();
		},
		closeAll(this: SiteShellState) {
			this.searchOpen = false;
			this.cartOpen = false;
			this.accountOpen = false;

			if (isLoginModalOpen()) {
				closeLoginModal();
			}

			if (this.menuOpen) {
				this.closeMenu();
				return;
			}

			this.syncDocumentState();
		},
		onMenuDrawerTransitionEnd(this: SiteShellState, event: TransitionEvent) {
			const panel = getMenuDrawerPanel();

			if (!panel || event.target !== panel || event.propertyName !== 'transform') {
				return;
			}

			if (!menuDrawerClosing || isMenuDrawerPanelActive()) {
				return;
			}

			cancelMobileMenuReset();
			finishMenuDrawerClose(this);
		},
	}));
});

document.addEventListener('click', (event) => {
	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	if (target.closest('.ts-site-header__account')) {
		return;
	}

	const shell = getSiteShell();

	if (shell?.accountOpen) {
		shell.closeAccount();
	}
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
			if (shell.menuOpen) {
				shell.closeMenu();
			}
			shell.cartOpen = true;
			shell.searchOpen = false;
			shell.syncDocumentState();
			document.dispatchEvent(new CustomEvent('tailwindscore:cart-drawer-opened'));
			return;
		}
	}

	const trigger = document.querySelector<HTMLButtonElement>('.ts-site-header__action--cart');
	trigger?.click();
});
