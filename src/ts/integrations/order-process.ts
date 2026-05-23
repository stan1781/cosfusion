import { notify } from '../cart/notifications';
import { setControlLoading } from '../ui/button-loading';

type OrderProcessConfig = {
	ajaxUrl: string;
	nonce: string;
	i18n: {
		invalidOrder: string;
		confirmTitle: string;
		confirmReceiptMessage: string;
		confirm: string;
		cancel: string;
		processing: string;
		success: string;
		error: string;
		errorOccurred: string;
	};
};

declare global {
	interface Window {
		wcOrderProcessFrontend?: OrderProcessConfig;
	}
}

let pendingOrderId: number | null = null;
let pendingButton: HTMLButtonElement | null = null;

function getConfig(): OrderProcessConfig | null {
	return window.wcOrderProcessFrontend ?? null;
}

function getModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-confirm-receipt-modal]');
}

function openModal(): void {
	const modal = getModal();
	if (!modal) {
		return;
	}

	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('ts-confirm-receipt-open');
}

function closeModal(): void {
	const modal = getModal();
	if (!modal) {
		return;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('ts-confirm-receipt-open');
	pendingOrderId = null;
	pendingButton = null;
}

async function confirmReceipt(orderId: number, button: HTMLButtonElement): Promise<void> {
	const config = getConfig();
	if (!config) {
		return;
	}

	setControlLoading(button, true);

	try {
		const body = new URLSearchParams({
			action: 'wc_order_process_confirm_receipt',
			order_id: String(orderId),
			nonce: config.nonce,
		});

		const response = await fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
			credentials: 'same-origin',
		});

		const payload = (await response.json()) as {
			success?: boolean;
			data?: { message?: string };
		};

		if (payload.success) {
			notify({
				type: 'success',
				message: payload.data?.message ?? config.i18n.success,
				surface: 'toast',
			});
			window.setTimeout(() => {
				window.location.reload();
			}, 800);
			return;
		}

		notify({
			type: 'error',
			message: payload.data?.message ?? config.i18n.errorOccurred,
			surface: 'toast',
		});
	} catch {
		notify({
			type: 'error',
			message: config.i18n.errorOccurred,
			surface: 'toast',
		});
	} finally {
		setControlLoading(button, false);
	}
}

function bindConfirmReceipt(): void {
	const config = getConfig();
	if (!config) {
		return;
	}

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		const closeTrigger = target.closest('[data-ts-confirm-receipt-close]');
		if (closeTrigger) {
			event.preventDefault();
			closeModal();
			return;
		}

		const confirmTrigger = target.closest('[data-ts-confirm-receipt-confirm]');
		if (confirmTrigger) {
			event.preventDefault();
			if (pendingOrderId && pendingButton) {
				void confirmReceipt(pendingOrderId, pendingButton);
			}
			closeModal();
			return;
		}

		const button = target.closest<HTMLButtonElement>('.wc-confirm-receipt-btn');
		if (!button) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const orderId = Number.parseInt(button.dataset.orderId ?? '', 10);
		if (!Number.isFinite(orderId) || orderId < 1) {
			notify({ type: 'error', message: config.i18n.invalidOrder, surface: 'toast' });
			return;
		}

		pendingOrderId = orderId;
		pendingButton = button;

		const messageEl = document.querySelector('[data-ts-confirm-receipt-message]');
		if (messageEl) {
			messageEl.textContent = config.i18n.confirmReceiptMessage;
		}

		openModal();
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeModal();
		}
	});
}

export function initOrderProcess(): void {
	if (!document.body.classList.contains('woocommerce-account')) {
		return;
	}

	bindConfirmReceipt();
}

initOrderProcess();
