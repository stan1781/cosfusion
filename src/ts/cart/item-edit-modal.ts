import { collectVariationFromUi, initQuickViewVariations } from '../shop/quick-view-variations';
import { withControlLoading } from '../ui/button-loading';
import { bindEscapeToClose, hideHiddenModal, isHiddenModalOpen, restoreFocus, showHiddenModal } from '../ui/dialog';
import { withGlobalLoading } from '../ui/global-loading';
import { notify } from './notifications';
import { refreshCart } from './index';

type CartItemEditI18n = {
	title: string;
	edit: string;
	save: string;
	cancel: string;
	loading: string;
	loadFailed: string;
	saveFailed: string;
	saved: string;
	noMatchingVariation: string;
	chooseOptions: string;
	close: string;
};

type CartItemEditConfig = {
	getFormUrl: string;
	saveUrl: string;
	nonce: string;
	i18n: CartItemEditI18n;
};

type AjaxSuccessPayload = {
	success?: boolean;
	data?: {
		html?: string;
		message?: string;
		key?: string;
	};
};

let returnFocusEl: HTMLElement | null = null;
let pendingAbort: AbortController | null = null;
let activeCartItemKey = '';

function getEditConfig(): CartItemEditConfig | undefined {
	return window.tailwindscoreCart?.cartItemEdit;
}

function getModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-cart-item-edit-modal]');
}

function getContentEl(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-cart-item-edit-content]');
}

function getLoadingEl(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-cart-item-edit-loading]');
}

function isOpen(): boolean {
	return isHiddenModalOpen(getModal());
}

function setLoading(isLoading: boolean): void {
	const loading = getLoadingEl();
	const content = getContentEl();

	if (loading) {
		loading.hidden = !isLoading;
	}

	if (content && isLoading) {
		content.innerHTML = '';
	}
}

function openModal(): void {
	const modal = getModal();
	if (!modal || isOpen()) {
		return;
	}

	showHiddenModal(modal, 'cart-item-edit-modal-open');

	const focusTarget =
		modal.querySelector<HTMLElement>('[data-ts-cart-item-edit-close]') ??
		modal.querySelector<HTMLElement>('.cart-item-edit-modal__dialog');
	focusTarget?.focus();
}

function closeModal(): void {
	const modal = getModal();
	if (!modal || !isOpen()) {
		return;
	}

	if (pendingAbort) {
		pendingAbort.abort();
		pendingAbort = null;
	}

	hideHiddenModal(modal, 'cart-item-edit-modal-open');

	const content = getContentEl();
	if (content) {
		content.innerHTML = '';
	}

	setLoading(false);
	activeCartItemKey = '';

	restoreFocus(returnFocusEl);
	returnFocusEl = null;
}

async function fetchEditForm(cartItemKey: string, signal?: AbortSignal): Promise<string | null> {
	const config = getEditConfig();
	if (!config?.getFormUrl || !cartItemKey) {
		return null;
	}

	const response = await fetch(config.getFormUrl, {
		method: 'POST',
		credentials: 'same-origin',
		signal,
		headers: {
			Accept: 'application/json',
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			cart_item_key: cartItemKey,
			nonce: config.nonce,
		}),
	});

	const rawText = await response.text();
	let payload: AjaxSuccessPayload | null = null;

	try {
		const parsed: unknown = rawText ? JSON.parse(rawText) : null;
		if (parsed && typeof parsed === 'object') {
			payload = parsed as AjaxSuccessPayload;
		}
	} catch {
		payload = null;
	}

	if (!payload?.success || typeof payload.data?.html !== 'string' || '' === payload.data.html.trim()) {
		const message =
			typeof payload?.data?.message === 'string' && payload.data.message !== ''
				? payload.data.message
				: config.i18n.loadFailed;
		throw new Error(message);
	}

	return payload.data.html;
}

async function saveEdit(cartItemKey: string): Promise<void> {
	const config = getEditConfig();
	const content = getContentEl();
	const form = content?.querySelector<HTMLFormElement>('form.variations_form');

	if (!config?.saveUrl || !form || !cartItemKey) {
		throw new Error(config?.i18n.saveFailed ?? 'Failed to save item options.');
	}

	const variation = collectVariationFromUi(form);

	if (Object.keys(variation).length < 1) {
		throw new Error(config.i18n.chooseOptions);
	}

	const response = await fetch(config.saveUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			Accept: 'application/json',
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			key: cartItemKey,
			variation,
			nonce: config.nonce,
		}),
	});

	const payload = (await response.json()) as AjaxSuccessPayload;

	if (!payload.success) {
		const message =
			typeof payload.data?.message === 'string' && payload.data.message !== ''
				? payload.data.message
				: config.i18n.saveFailed;
		throw new Error(message);
	}

	await refreshCart();
	notify({
		type: 'success',
		message: config.i18n.saved,
		surface: 'toast',
	});
}

export async function openCartItemEditModal(trigger: HTMLElement, cartItemKey: string): Promise<void> {
	const config = getEditConfig();
	if (!config || !cartItemKey) {
		return;
	}

	returnFocusEl = trigger;
	activeCartItemKey = cartItemKey;

	openModal();
	setLoading(true);

	if (pendingAbort) {
		pendingAbort.abort();
	}

	pendingAbort = new AbortController();
	const abort = pendingAbort;

	try {
		const html = await withGlobalLoading(() => fetchEditForm(cartItemKey, abort.signal));
		const content = getContentEl();

		if (!html || !content) {
			throw new Error(config.i18n.loadFailed);
		}

		content.innerHTML = html;
		setLoading(false);
		await initQuickViewVariations(content);

		const saveButton = document.querySelector<HTMLButtonElement>('[data-ts-cart-item-edit-save]');
		saveButton?.focus();
	} catch (error) {
		if (error instanceof DOMException && error.name === 'AbortError') {
			return;
		}

		notify({
			type: 'error',
			message: error instanceof Error ? error.message : config.i18n.loadFailed,
			surface: 'toast',
		});
		closeModal();
	} finally {
		pendingAbort = null;
	}
}

function bindCartItemEditControls(): void {
	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const editButton = target.closest<HTMLButtonElement>('[data-ts-cart-item-edit]');
		if (editButton) {
			event.preventDefault();
			const cartItemKey = editButton.dataset.cartItemKey ?? '';
			if ('' === cartItemKey) {
				return;
			}

			void openCartItemEditModal(editButton, cartItemKey);
			return;
		}

		if (target.closest('[data-ts-cart-item-edit-close], [data-ts-cart-item-edit-cancel]')) {
			event.preventDefault();
			closeModal();
			return;
		}

		const saveButton = target.closest<HTMLButtonElement>('[data-ts-cart-item-edit-save]');
		if (saveButton) {
			event.preventDefault();

			if ('' === activeCartItemKey) {
				return;
			}

			void withControlLoading(saveButton, async () => {
				try {
					await withGlobalLoading(() => saveEdit(activeCartItemKey));
					closeModal();
				} catch (error) {
					notify({
						type: 'error',
						message:
							error instanceof Error
								? error.message
								: getEditConfig()?.i18n.saveFailed ?? 'Failed to save item options.',
						surface: 'toast',
					});
				}
			});
		}
	});

	bindEscapeToClose(isOpen, closeModal);
}

bindCartItemEditControls();
