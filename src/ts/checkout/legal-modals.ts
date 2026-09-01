import { bindBackdropClickToClose } from '../ui/dialog';

/**
 * 结账 Terms / Privacy：拦截链接，打开 SSR 的原生 dialog。
 * 捕获阶段阻止 WooCommerce checkout.js 对条款的 slideToggle。
 */
function openCheckoutLegalModal(dialog: HTMLDialogElement): void {
	if (typeof dialog.showModal === 'function' && !dialog.open) {
		dialog.showModal();
	}
}

function closeCheckoutLegalModal(dialog: HTMLDialogElement): void {
	if (dialog.open) {
		dialog.close();
	}
}

function bindCheckoutLegalDialogChrome(dialog: HTMLDialogElement): void {
	if (dialog.dataset.tsLegalModalBound === 'true') {
		return;
	}

	dialog.dataset.tsLegalModalBound = 'true';
	bindBackdropClickToClose(dialog, () => closeCheckoutLegalModal(dialog));
}

export function initCheckoutLegalModals(): void {
	document.querySelectorAll<HTMLDialogElement>('dialog.checkout-legal-modal').forEach(bindCheckoutLegalDialogChrome);

	document.addEventListener(
		'click',
		(event) => {
			const target = event.target;

			if (!(target instanceof Element)) {
				return;
			}

			const closeControl = target.closest<HTMLElement>('[data-ts-modal-close]');

			if (closeControl) {
				const dialog = closeControl.closest('dialog.checkout-legal-modal');

				if (dialog instanceof HTMLDialogElement) {
					event.preventDefault();
					closeCheckoutLegalModal(dialog);
				}

				return;
			}

			const link = target.closest<HTMLAnchorElement>('a[data-ts-modal-open]');

			if (!link) {
				return;
			}

			const dialogId = link.dataset.tsModalOpen;

			if (!dialogId) {
				return;
			}

			const dialog = document.getElementById(dialogId);

			if (!(dialog instanceof HTMLDialogElement)) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			bindCheckoutLegalDialogChrome(dialog);
			openCheckoutLegalModal(dialog);
		},
		true,
	);
}
