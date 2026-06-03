import { resolveSubmitButton, setControlLoading } from './button-loading';

let loginModalReturnFocus: HTMLElement | null = null;

export function getLoginModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-login-modal]');
}

export function isLoginModalOpen(): boolean {
	const modal = getLoginModal();

	return modal !== null && !modal.hasAttribute('hidden');
}

function closeSiteShellPanels(): void {
	const header = document.querySelector<HTMLElement>('.ts-site-header');
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

	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('ts-login-modal-open');

	window.requestAnimationFrame(() => {
		const focusTarget =
			modal.querySelector<HTMLElement>('#username, input:not([type="hidden"])') ??
			modal.querySelector<HTMLElement>('button, a, input, select, textarea');

		focusTarget?.focus();
	});
}

export function closeLoginModal(): void {
	const modal = getLoginModal();

	if (!modal || modal.hasAttribute('hidden')) {
		return;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('ts-login-modal-open');

	if (loginModalReturnFocus?.isConnected) {
		loginModalReturnFocus.focus();
	}

	loginModalReturnFocus = null;
}

function initLoginModal(): void {
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

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isLoginModalOpen()) {
			event.preventDefault();
			closeLoginModal();
		}
	});

	document.addEventListener(
		'submit',
		(event) => {
			if (!(event.target instanceof HTMLFormElement)) {
				return;
			}

			if (!event.target.closest('.ts-login-modal')) {
				return;
			}

			setControlLoading(resolveSubmitButton(event.target, event.submitter), true, { disable: false });
		},
		true,
	);
}

initLoginModal();
