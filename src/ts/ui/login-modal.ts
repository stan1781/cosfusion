import { resolveSubmitButton, setControlLoading } from './button-loading';
import { bindEscapeToClose, hideHiddenModal, isHiddenModalOpen, restoreFocus, showHiddenModal } from './dialog';
import { initPasswordVisibility } from './password-visibility';

let loginModalReturnFocus: HTMLElement | null = null;

export function getLoginModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-login-modal]');
}

export function isLoginModalOpen(): boolean {
	return isHiddenModalOpen(getLoginModal());
}

function closeSiteShellPanels(): void {
	const header = document.querySelector<HTMLElement>('.site-header');
	const alpine = window.Alpine;

	if (!header || !alpine || typeof alpine.$data !== 'function') {
		return;
	}

	const shell = alpine.$data(header) as {
		searchOpen?: boolean;
		cartOpen?: boolean;
		menuOpen?: boolean;
		accountOpen?: boolean;
		closeMenu?: () => void;
		closeAccount?: () => void;
		syncDocumentState?: () => void;
	} | undefined;

	if (!shell) {
		return;
	}

	shell.searchOpen = false;
	shell.cartOpen = false;

	if (shell.menuOpen && typeof shell.closeMenu === 'function') {
		shell.closeMenu();
	}

	if (shell.accountOpen && typeof shell.closeAccount === 'function') {
		shell.closeAccount();
	}

	shell.syncDocumentState?.();
}

export function openLoginModal(trigger?: HTMLElement): void {
	const modal = getLoginModal();

	if (!modal || isLoginModalOpen()) {
		return;
	}

	closeSiteShellPanels();

	loginModalReturnFocus =
		trigger ?? (document.activeElement instanceof HTMLElement ? document.activeElement : null);

	showHiddenModal(modal, 'login-modal-open');

	window.requestAnimationFrame(() => {
		const focusTarget =
			modal.querySelector<HTMLElement>('#username, input:not([type="hidden"])') ??
			modal.querySelector<HTMLElement>('button, a, input, select, textarea');

		focusTarget?.focus();
	});
}

export function closeLoginModal(): void {
	const modal = getLoginModal();

	if (!modal || !isHiddenModalOpen(modal)) {
		return;
	}

	hideHiddenModal(modal, 'login-modal-open');
	restoreFocus(loginModalReturnFocus);
	loginModalReturnFocus = null;
}

function initLoginModal(): void {
	const modal = getLoginModal();

	if (modal) {
		initPasswordVisibility(modal);
	}

	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const openTrigger = target.closest<HTMLElement>('[data-ts-login-modal-open]');

		if (openTrigger) {
			event.preventDefault();
			openLoginModal(openTrigger);
			return;
		}

		if (target.closest('[data-ts-login-modal-close]')) {
			event.preventDefault();
			closeLoginModal();
		}
	});

	bindEscapeToClose(isLoginModalOpen, closeLoginModal);

	document.addEventListener(
		'submit',
		(event) => {
			if (!(event.target instanceof HTMLFormElement)) {
				return;
			}

			if (!event.target.closest('.login-modal')) {
				return;
			}

			setControlLoading(resolveSubmitButton(event.target, event.submitter), true, { disable: false });
		},
		true,
	);
}

initLoginModal();
