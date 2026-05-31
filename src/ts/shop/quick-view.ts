import { addCartItem, notify } from '../cart/index';
import { buildAddToCartRequest } from '../product/add-to-cart-request';
import { resolveSubmitButton, setButtonsLoading } from '../ui/button-loading';
import { initQuickViewVariations } from './quick-view-variations';

type QuickViewConfig = {
	ajaxUrl: string;
	i18n: {
		title: string;
		loading: string;
		error: string;
		networkError: string;
		chooseOptions: string;
		addedToCart: string;
		close: string;
	};
};

declare global {
	interface Window {
		tailwindscoreQuickView?: QuickViewConfig;
	}
}

const PREFETCH_DELAY_MS = 180;
const sessionHtmlCache = new Map<number, string>();

let pendingAbort: AbortController | null = null;
let prefetchAbort: AbortController | null = null;
/** Browser timer id (`window.setTimeout`); not NodeJS.Timeout from @types/node. */
let prefetchTimer: number | null = null;
/** Product id for the active prefetch timer or in-flight request (same card, different elements). */
let prefetchProductId: number | null = null;
let returnFocusEl: HTMLElement | null = null;

function getConfig(): QuickViewConfig | null {
	return window.tailwindscoreQuickView ?? null;
}

function getModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-quick-view-modal]');
}

function getContentEl(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-quick-view-content]');
}

function getLoadingEl(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-quick-view-loading]');
}

function isOpen(): boolean {
	const modal = getModal();
	return modal !== null && !modal.hasAttribute('hidden');
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

	returnFocusEl = document.activeElement instanceof HTMLElement ? document.activeElement : null;
	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('ts-quick-view-modal-open');

	const focusTarget =
		modal.querySelector<HTMLElement>('[data-ts-quick-view-close]') ??
		modal.querySelector<HTMLElement>('.ts-quick-view-modal__dialog');
	focusTarget?.focus();
}

function closeModal(): void {
	const modal = getModal();
	if (!modal || modal.hasAttribute('hidden')) {
		return;
	}

	if (pendingAbort) {
		pendingAbort.abort();
		pendingAbort = null;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('ts-quick-view-modal-open');

	const content = getContentEl();
	if (content) {
		content.innerHTML = '';
	}

	setLoading(false);

	if (returnFocusEl) {
		returnFocusEl.focus();
		returnFocusEl = null;
	}
}

function readProductId(trigger: HTMLElement): number {
	const fromTrigger = Number(trigger.getAttribute('data-product-id') ?? trigger.dataset.productId ?? '0');
	if (fromTrigger > 0) {
		return fromTrigger;
	}

	const card = trigger.closest<HTMLElement>('[data-product-id]');
	return Number(card?.getAttribute('data-product-id') ?? card?.dataset.productId ?? '0');
}

type QuickViewAjaxPayload = {
	success?: boolean;
	data?: { html?: string; message?: string };
};

async function fetchProductHtml(productId: number, signal?: AbortSignal): Promise<string | null> {
	const config = getConfig();
	if (!config?.ajaxUrl || productId < 1) {
		return null;
	}

	const body = new FormData();
	body.set('product_id', String(productId));

	const response = await fetch(config.ajaxUrl, {
		method: 'POST',
		body,
		signal,
		credentials: 'same-origin',
		headers: {
			Accept: 'application/json',
		},
	});

	const rawText = await response.text();
	let payload: QuickViewAjaxPayload | null = null;

	try {
		const parsed: unknown = rawText ? JSON.parse(rawText) : null;
		if (parsed && typeof parsed === 'object') {
			payload = parsed as QuickViewAjaxPayload;
		}
	} catch {
		payload = null;
	}

	if (!payload?.success || typeof payload.data?.html !== 'string' || '' === payload.data.html.trim()) {
		const message =
			typeof payload?.data?.message === 'string' && payload.data.message !== ''
				? payload.data.message
				: config.i18n.error;
		throw new Error(message);
	}

	return payload.data.html;
}

function cancelPrefetch(): void {
	if (prefetchTimer !== null) {
		window.clearTimeout(prefetchTimer);
		prefetchTimer = null;
	}

	if (prefetchAbort) {
		prefetchAbort.abort();
		prefetchAbort = null;
	}

	prefetchProductId = null;
}

function getProductCard(target: Element): HTMLElement | null {
	return target.closest<HTMLElement>('[data-product-card]');
}

function cardSupportsQuickView(card: HTMLElement): boolean {
	return card.querySelector('[data-ts-quick-view]') !== null;
}

function schedulePrefetchForCard(card: HTMLElement): void {
	if (!cardSupportsQuickView(card)) {
		return;
	}

	const productId = readProductId(card);
	if (productId < 1 || sessionHtmlCache.has(productId)) {
		return;
	}

	// capture mouseenter fires per entered element; image → QV button must not restart prefetch.
	if (prefetchProductId === productId && (prefetchTimer !== null || prefetchAbort !== null)) {
		return;
	}

	cancelPrefetch();
	prefetchProductId = productId;

	prefetchTimer = window.setTimeout(() => {
		prefetchTimer = null;
		prefetchAbort = new AbortController();

		void fetchProductHtml(productId, prefetchAbort.signal)
			.then((html) => {
				if (html) {
					sessionHtmlCache.set(productId, html);
				}
			})
			.catch((error) => {
				if (error instanceof DOMException && error.name === 'AbortError') {
					return;
				}
			})
			.finally(() => {
				prefetchAbort = null;
				if (prefetchProductId === productId) {
					prefetchProductId = null;
				}
			});
	}, PREFETCH_DELAY_MS);
}

async function applyProductHtml(html: string): Promise<void> {
	const content = getContentEl();
	if (!content) {
		return;
	}

	content.innerHTML = html;
	await initQuickViewVariations(content);
	bindCartForms(content);
}

async function loadProduct(productId: number): Promise<void> {
	const config = getConfig();
	const content = getContentEl();

	if (!config || !content || productId < 1) {
		return;
	}

	const cached = sessionHtmlCache.get(productId);
	if (cached) {
		setLoading(true);
		try {
			await applyProductHtml(cached);
		} catch (error) {
			console.error(error);
			sessionHtmlCache.delete(productId);
			content.innerHTML = `<p class="ts-quick-view-modal__error">${config.i18n.error}</p>`;
		} finally {
			setLoading(false);
		}
		return;
	}

	if (pendingAbort) {
		pendingAbort.abort();
	}

	const controller = new AbortController();
	pendingAbort = controller;

	setLoading(true);

	try {
		const html = await fetchProductHtml(productId, controller.signal);
		if (!html) {
			content.innerHTML = `<p class="ts-quick-view-modal__error">${config.i18n.error}</p>`;
			return;
		}

		sessionHtmlCache.set(productId, html);
		await applyProductHtml(html);
	} catch (error) {
		if (error instanceof DOMException && error.name === 'AbortError') {
			return;
		}

		console.error(error);
		const message = error instanceof Error ? error.message : config.i18n.networkError;
		content.innerHTML = `<p class="ts-quick-view-modal__error">${message}</p>`;
	} finally {
		if (pendingAbort === controller) {
			pendingAbort = null;
		}
		setLoading(false);
	}
}

function bindCartForms(root: HTMLElement): void {
	root.querySelectorAll<HTMLFormElement>('form.cart').forEach((form) => {
		if (form.dataset.tsQuickViewBound === 'true') {
			return;
		}

		form.dataset.tsQuickViewBound = 'true';

		form.addEventListener('submit', async (event) => {
			const submitter =
				event instanceof SubmitEvent && event.submitter instanceof HTMLButtonElement ? event.submitter : null;
			const request = buildAddToCartRequest(form, submitter);

			if (!request) {
				if (form.classList.contains('variations_form')) {
					event.preventDefault();
					notify({
						type: 'error',
						message: getConfig()?.i18n.chooseOptions ?? 'Please choose product options.',
						surface: 'toast',
					});
				}
				return;
			}

			event.preventDefault();

			const button = resolveSubmitButton(form, submitter, 'button.single_add_to_cart_button');
			const buttons = button ? [button] : form.querySelectorAll<HTMLButtonElement>('button[type="submit"]');

			setButtonsLoading(buttons, true);

			try {
				await addCartItem(request);
				const message =
					getConfig()?.i18n.addedToCart ??
					window.tailwindscoreCart?.i18n.addedToCart ??
					'Added to your cart.';
				notify({
					type: 'success',
					message,
					surface: 'toast',
				});
				document.dispatchEvent(new CustomEvent('tailwindscore:open-cart'));
				closeModal();
			} catch (error) {
				console.error(error);
				notify({
					type: 'error',
					message: error instanceof Error ? error.message : getConfig()?.i18n.error ?? 'Unable to add to cart.',
					surface: 'toast',
				});
			} finally {
				setButtonsLoading(buttons, false);
			}
		});
	});
}

function openQuickView(trigger: HTMLElement): void {
	const productId = readProductId(trigger);
	const config = getConfig();

	if (!config || productId < 1) {
		return;
	}

	cancelPrefetch();
	openModal();
	void loadProduct(productId);
}

function initQuickView(): void {
	if (!getConfig()) {
		return;
	}

	document.addEventListener(
		'click',
		(event) => {
			const target = event.target;
			if (!(target instanceof Element)) {
				return;
			}

			const trigger = target.closest<HTMLElement>('[data-ts-quick-view]');
			if (!trigger) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			openQuickView(trigger);
		},
		true,
	);

	document.addEventListener(
		'mouseenter',
		(event) => {
			const target = event.target;
			if (!(target instanceof Element)) {
				return;
			}

			const card = getProductCard(target);
			if (card) {
				schedulePrefetchForCard(card);
			}
		},
		true,
	);

	document.addEventListener(
		'mouseleave',
		(event) => {
			const target = event.target;
			if (!(target instanceof Element)) {
				return;
			}

			const card = getProductCard(target);
			if (!card) {
				return;
			}

			const related = event.relatedTarget;
			if (related instanceof Node && card.contains(related)) {
				return;
			}

			// Mouse often leaves the card when interacting with the open modal; prefetch is already done or cancelled on open.
			if (isOpen()) {
				return;
			}

			cancelPrefetch();
		},
		true,
	);

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		if (target.closest('[data-ts-quick-view-close]')) {
			closeModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen()) {
			closeModal();
		}
	});
}

initQuickView();
