import { clearFeedback, notify } from './notifications';
import { resolveCouponApplyButton, withControlLoading } from '../ui/button-loading';

export { notify } from './notifications';

type CartI18nConfig = {
	cartScreenReaderSingular: string;
	cartScreenReaderPlural: string;
	cartSummarySingular: string;
	cartSummaryPlural: string;
	emptyCartMessage: string;
	browseProducts: string;
	subtotal: string;
	couponDiscount: string;
	checkout: string;
	viewCart: string;
	quantityLabel: string;
	decreaseQuantity: string;
	increaseQuantity: string;
	removeItem: string;
	couponLabel: string;
	couponPlaceholder: string;
	applyCoupon: string;
	couponApplied: string;
	couponRemoved: string;
	couponInvalid: string;
	couponRequired: string;
	itemRemoved: string;
	addedToCart: string;
};

type CartIconConfig = {
	plus: string;
	minus: string;
	trash: string;
};

type NotificationSelectorConfig = {
	toast?: string;
	drawer?: string;
	inline?: string;
	page?: string;
};

type TailwindscoreCartConfig = {
	storeApiUrl: string;
	nonce: string;
	cartUrl: string;
	checkoutUrl: string;
	shopUrl: string;
	notifications?: {
		selectors?: NotificationSelectorConfig;
	};
	i18n: CartI18nConfig;
	icons: CartIconConfig;
};

type StoreApiCurrency = {
	currency_code: string;
	currency_symbol: string;
	currency_minor_unit: number;
	currency_decimal_separator: string;
	currency_thousand_separator: string;
	currency_prefix: string;
	currency_suffix: string;
};

type StoreApiImage = {
	src: string;
	thumbnail: string;
	alt: string;
};

type StoreApiCartItem = {
	key: string;
	id: number;
	quantity: number;
	name: string;
	permalink: string;
	images: StoreApiImage[];
	prices: StoreApiCurrency & {
		price: string;
	};
	totals: StoreApiCurrency & {
		line_subtotal: string;
		line_total: string;
	};
	quantity_limits?: {
		maximum?: number;
		minimum?: number;
	};
};

type StoreApiCart = {
	items: StoreApiCartItem[];
	items_count: number;
	totals: StoreApiCurrency & {
		total_items: string;
		total_discount: string;
	};
};

type StoreApiErrorResponse = {
	code?: string;
	message?: string;
	data?: {
		status?: number;
		cart?: StoreApiCart;
	};
};

type AddCartItemRequest = {
	productId: number;
	quantity: number;
	variationId?: number;
	variation?: Array<{
		attribute: string;
		value: string;
	}>;
};

type WooCommerceJQuery = {
	(selector: string | Document | HTMLElement): {
		replaceWith: (html: string) => void;
		trigger: (event: string, data?: unknown) => void;
	};
	ajax: (settings: {
		type: string;
		url: string;
		data: Record<string, unknown>;
		success?: (response: { fragments?: Record<string, string> }) => void;
		error?: () => void;
	}) => void;
};

declare global {
	interface Window {
		tailwindscoreCart?: TailwindscoreCartConfig;
		wc_checkout_params?: {
			wc_ajax_url: string;
			update_order_review_nonce: string;
		};
	}
}

const cartDrawerSelector = '[data-ts-cart-drawer-inner]';
const pendingCartItemKeys = new Set<string>();
let currentCart: StoreApiCart | null = null;
let storeApiNonce: string | undefined;
let cartToken: string | undefined;

function escapeHtml(value: string) {
	return value
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function getCartConfig() {
	if (window.tailwindscoreCart) {
		return window.tailwindscoreCart;
	}

	const configNode = document.querySelector<HTMLElement>('[data-ts-cart-config]');
	const storeApiUrl = configNode?.dataset.storeApiUrl;
	const nonce = configNode?.dataset.storeApiNonce;
	const cartUrl = configNode?.dataset.cartUrl;
	const checkoutUrl = configNode?.dataset.checkoutUrl;
	const shopUrl = configNode?.dataset.shopUrl;

	if (!storeApiUrl || !nonce) {
		return undefined;
	}

	return {
		storeApiUrl,
		nonce,
		cartUrl: cartUrl || '/cart/',
		checkoutUrl: checkoutUrl || '/checkout/',
		shopUrl: shopUrl || '/shop/',
		i18n: {
			cartScreenReaderSingular: 'Cart, %d item',
			cartScreenReaderPlural: 'Cart, %d items',
			cartSummarySingular: '%d item ready for checkout',
			cartSummaryPlural: '%d items ready for checkout',
			emptyCartMessage: 'Your cart is empty.',
			browseProducts: 'Browse products',
			subtotal: 'Subtotal',
			couponDiscount: 'Coupon discount',
			checkout: 'Checkout',
			viewCart: 'View cart',
			quantityLabel: 'Quantity',
			decreaseQuantity: 'Decrease quantity',
			increaseQuantity: 'Increase quantity',
			removeItem: 'Remove item from your cart',
			couponLabel: 'Coupon code',
			couponPlaceholder: 'Enter code',
			applyCoupon: 'Apply',
			couponApplied: 'Coupon applied.',
			couponRemoved: 'Coupon removed.',
			couponInvalid: 'That coupon code is not valid.',
			couponRequired: 'Please enter a coupon code.',
			itemRemoved: 'Item removed from your cart.',
			addedToCart: 'Added to your cart.',
		},
		icons: {
			plus: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
			minus: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"></path></svg>',
			trash: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12h10l1-12"></path><path d="M9 7V5h6v2"></path></svg>',
		},
	};
}

function getConfigOrThrow() {
	const config = getCartConfig();

	if (!config) {
		throw new Error('Store API config unavailable.');
	}

	if (!storeApiNonce) {
		storeApiNonce = config.nonce;
	}

	return config;
}

function replaceCount(template: string, count: number) {
	return template.replace('%d', String(count));
}

function formatMoney(amount: string, currency: StoreApiCurrency) {
	const minorUnit = Math.max(0, Number(currency.currency_minor_unit || 0));
	const normalized = amount.replace(/[^0-9-]/g, '');
	const isNegative = normalized.startsWith('-');
	const digits = normalized.replace('-', '');
	const padded = digits.padStart(minorUnit + 1, '0');
	const whole = minorUnit > 0 ? padded.slice(0, -minorUnit) : padded;
	const decimal = minorUnit > 0 ? padded.slice(-minorUnit) : '';
	const groupedWhole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, currency.currency_thousand_separator || ',');
	const numeric = decimal ? `${groupedWhole}${currency.currency_decimal_separator || '.'}${decimal}` : groupedWhole;
	const signed = isNegative ? `-${numeric}` : numeric;

	return `${currency.currency_prefix || ''}${signed}${currency.currency_suffix || ''}`;
}

function renderCartBadge(count: number) {
	if (count < 1) {
		return '';
	}

	return `<span class="ts-site-header__badge" aria-hidden="true">${count}</span>`;
}

function renderCartScreenReader(count: number, config: TailwindscoreCartConfig) {
	const template = count === 1 ? config.i18n.cartScreenReaderSingular : config.i18n.cartScreenReaderPlural;

	return `<span class="screen-reader-text">${escapeHtml(replaceCount(template, count))}</span>`;
}

function renderCartSummary(count: number, config: TailwindscoreCartConfig) {
	const template = count === 1 ? config.i18n.cartSummarySingular : config.i18n.cartSummaryPlural;
	return replaceCount(template, count);
}

function renderCartItem(item: StoreApiCartItem, config: TailwindscoreCartConfig) {
	const image = item.images[0];
	const imageMarkup = image
		? `<img width="300" height="300" src="${escapeHtml(image.thumbnail || image.src)}" alt="${escapeHtml(image.alt || item.name)}" loading="lazy" />`
		: '';
	const unitPrice = formatMoney(item.prices.price, item.prices);
	const lineSubtotal = item.totals.line_subtotal ?? item.totals.line_total;
	const subtotal = formatMoney(lineSubtotal, item.totals);
	const maxQuantity = item.quantity_limits?.maximum;
	const maxAttribute = typeof maxQuantity === 'number' && Number.isFinite(maxQuantity) ? ` max="${maxQuantity}"` : '';
	const productUrl = item.permalink ? escapeHtml(item.permalink) : '';
	const productName = escapeHtml(item.name);

	return `
		<li class="ts-cart-drawer-cart__item" data-ts-cart-item data-cart-item-key="${escapeHtml(item.key)}">
			<div class="ts-cart-drawer-cart__media">
				${productUrl ? `<a href="${productUrl}">${imageMarkup}</a>` : imageMarkup}
			</div>
			<div class="ts-cart-drawer-cart__content ts-stack">
				<div class="ts-cart-drawer-cart__row">
					<div class="ts-cart-drawer-cart__meta ts-stack">
						${productUrl ? `<a class="ts-cart-drawer-cart__name" href="${productUrl}">${productName}</a>` : `<span class="ts-cart-drawer-cart__name">${productName}</span>`}
						<span class="ts-cart-drawer-cart__quantity">${item.quantity} x ${escapeHtml(unitPrice)}</span>
					</div>
					<button
						type="button"
						class="ts-cart-drawer-cart__remove"
						data-ts-cart-remove
						data-cart-item-key="${escapeHtml(item.key)}"
						aria-label="${escapeHtml(config.i18n.removeItem)}"
					>
						<span class="ts-icon" aria-hidden="true">${config.icons.trash}</span>
					</button>
				</div>
				<div class="ts-cart-drawer-cart__controls" data-ts-cart-quantity-controls>
					<button
						type="button"
						class="ts-cart-drawer-cart__quantity-button"
						data-ts-cart-quantity-step="-1"
						data-cart-item-key="${escapeHtml(item.key)}"
						aria-label="${escapeHtml(config.i18n.decreaseQuantity)}"
					>
						<span class="ts-icon" aria-hidden="true">${config.icons.minus}</span>
					</button>
					<label class="screen-reader-text" for="ts-cart-qty-${escapeHtml(item.key)}">${escapeHtml(config.i18n.quantityLabel)}</label>
					<input
						id="ts-cart-qty-${escapeHtml(item.key)}"
						class="ts-cart-drawer-cart__quantity-input"
						type="number"
						inputmode="numeric"
						min="0"${maxAttribute}
						step="1"
						value="${item.quantity}"
						data-ts-cart-quantity-input
						data-cart-item-key="${escapeHtml(item.key)}"
					>
					<button
						type="button"
						class="ts-cart-drawer-cart__quantity-button"
						data-ts-cart-quantity-step="1"
						data-cart-item-key="${escapeHtml(item.key)}"
						aria-label="${escapeHtml(config.i18n.increaseQuantity)}"
					>
						<span class="ts-icon" aria-hidden="true">${config.icons.plus}</span>
					</button>
				</div>
				<div class="ts-cart-drawer-cart__subtotal">${escapeHtml(subtotal)}</div>
			</div>
		</li>
	`;
}

function renderCartFeedbackMarkup() {
	return `<div class="ts-cart-feedback" data-ts-cart-feedback data-ts-cart-feedback-surface="drawer" role="status" aria-live="polite" hidden></div>`;
}

function formatDiscountAmount(amount: string, currency: StoreApiCurrency) {
	const formatted = formatMoney(amount, currency);

	return formatted.startsWith('-') ? formatted : `-${formatted}`;
}

function getCartSubtotalAfterDiscount(cart: StoreApiCart) {
	const itemsTotal = Number(cart.totals.total_items || 0);
	const discount = Number(cart.totals.total_discount || 0);

	return Math.max(0, itemsTotal - discount);
}

function renderCartFooterTotals(cart: StoreApiCart, config: TailwindscoreCartConfig) {
	const discount = Number(cart.totals.total_discount || 0);
	const subtotalAfterDiscount = getCartSubtotalAfterDiscount(cart);
	const discountRow =
		discount > 0
			? `
				<div class="ts-cart-drawer-cart__totals ts-cart-drawer-cart__totals--discount">
					<span>${escapeHtml(config.i18n.couponDiscount)}</span>
					<strong class="ts-cart-drawer-cart__discount-amount">${escapeHtml(formatDiscountAmount(String(discount), cart.totals))}</strong>
				</div>
			`
			: '';

	return `
		<div class="ts-cart-drawer-cart__totals-group ts-stack">
			${discountRow}
			<div class="ts-cart-drawer-cart__totals ts-cart-drawer-cart__totals--subtotal">
				<span>${escapeHtml(config.i18n.subtotal)}</span>
				<strong data-ts-cart-subtotal>${escapeHtml(formatMoney(String(subtotalAfterDiscount), cart.totals))}</strong>
			</div>
		</div>
	`;
}

function renderCartCouponMarkup(config: TailwindscoreCartConfig) {
	return `
		<div class="ts-cart-drawer-cart__coupon" data-ts-cart-coupon>
			<form class="ts-cart-drawer-cart__coupon-form ts-coupon-form ts-stack" data-ts-cart-coupon-form action="${escapeHtml(config.cartUrl)}" method="post">
				<label for="ts-cart-coupon-code">${escapeHtml(config.i18n.couponLabel)}</label>
				<div class="ts-input-group">
					<input
						id="ts-cart-coupon-code"
						class="ts-input"
						type="text"
						name="coupon_code"
						autocomplete="off"
						data-ts-cart-coupon-input
						placeholder="${escapeHtml(config.i18n.couponPlaceholder)}"
					>
					<button class="ts-button ts-button--secondary" type="submit" data-ts-button-loading data-ts-cart-coupon-submit>${escapeHtml(config.i18n.applyCoupon)}</button>
				</div>
			</form>
		</div>
	`;
}

function renderCartDrawer(cart: StoreApiCart, config: TailwindscoreCartConfig) {
	if (cart.items.length < 1) {
		return `
			<div class="ts-cart-drawer-cart">
				${renderCartFeedbackMarkup()}
				<div class="ts-cart-drawer-cart__empty ts-stack">
					<p>${escapeHtml(config.i18n.emptyCartMessage)}</p>
					<a class="ts-button" href="${escapeHtml(config.shopUrl)}">${escapeHtml(config.i18n.browseProducts)}</a>
				</div>
			</div>
		`;
	}

	return `
		<div class="ts-cart-drawer-cart ts-cart-drawer-cart--filled">
			${renderCartFeedbackMarkup()}
			<div class="ts-cart-drawer-cart__body">
				<ul class="ts-cart-drawer-cart__items" data-ts-cart-items>
					${cart.items.map((item) => renderCartItem(item, config)).join('')}
				</ul>
			</div>
			${renderCartCouponMarkup(config)}
			<div class="ts-cart-drawer-cart__footer ts-stack" data-ts-cart-footer>
				${renderCartFooterTotals(cart, config)}
				<a class="ts-button" href="${escapeHtml(config.checkoutUrl)}">${escapeHtml(config.i18n.checkout)}</a>
				<a class="ts-cart-drawer-cart__view-cart" href="${escapeHtml(config.cartUrl)}">${escapeHtml(config.i18n.viewCart)}</a>
			</div>
		</div>
	`;
}

function reportCartError(error: unknown, surface: 'drawer' | 'toast' | 'page' = 'drawer') {
	const message = error instanceof Error ? error.message : 'Store API request failed.';

	notify({
		type: 'error',
		message,
		surface,
	});
}

function syncPendingCartItems() {
	document.querySelectorAll<HTMLElement>('[data-ts-cart-item]').forEach((item) => {
		const cartItemKey = item.dataset.cartItemKey;
		const isPending = !!cartItemKey && pendingCartItemKeys.has(cartItemKey);

		item.toggleAttribute('aria-busy', isPending);
		item.dataset.state = isPending ? 'pending' : 'idle';

		item
			.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
				'[data-ts-cart-quantity-step], [data-ts-cart-quantity-input], [data-ts-cart-remove]',
			)
			.forEach((control) => {
				control.disabled = isPending;
			});
	});
}

function syncCartUi(cart: StoreApiCart) {
	const config = getConfigOrThrow();
	const count = Number(cart.items_count || 0);
	const summary = renderCartSummary(count, config);
	const drawerMarkup = renderCartDrawer(cart, config);

	document.querySelectorAll<HTMLElement>('[data-ts-cart-badge]').forEach((element) => {
		element.innerHTML = renderCartBadge(count);
	});

	document.querySelectorAll<HTMLElement>('[data-ts-cart-screen-reader]').forEach((element) => {
		element.innerHTML = renderCartScreenReader(count, config);
	});

	document.querySelectorAll<HTMLElement>('[data-ts-cart-summary]').forEach((element) => {
		element.textContent = summary;
	});

	document.querySelectorAll<HTMLElement>(cartDrawerSelector).forEach((element) => {
		const existingFeedback = element.querySelector<HTMLElement>('[data-ts-cart-feedback][data-ts-cart-feedback-surface="drawer"]');
		const feedbackMarkup = existingFeedback && !existingFeedback.hidden ? existingFeedback.innerHTML : '';
		element.innerHTML = drawerMarkup;

		if (feedbackMarkup) {
			const feedback = element.querySelector<HTMLElement>('[data-ts-cart-feedback][data-ts-cart-feedback-surface="drawer"]');

			if (feedback) {
				feedback.hidden = false;
				feedback.innerHTML = feedbackMarkup;
			}
		}
	});

	syncPendingCartItems();
}

function updateStoreApiHeaders(response: Response) {
	const nextNonce = response.headers.get('Nonce');
	const nextCartToken = response.headers.get('Cart-Token');

	if (nextNonce) {
		storeApiNonce = nextNonce;
	}

	if (nextCartToken) {
		cartToken = nextCartToken;
	}
}

async function readErrorResponse(response: Response) {
	let payload: StoreApiErrorResponse | null = null;

	try {
		payload = (await response.json()) as StoreApiErrorResponse;
	} catch {
		payload = null;
	}

	if (payload?.data?.cart) {
		currentCart = payload.data.cart;
		syncCartUi(payload.data.cart);
	}

	throw new Error(payload?.message || 'Store API request failed.');
}

async function requestStoreApi<TResponse>(path: string, options?: { method?: string; body?: unknown }) {
	const config = getConfigOrThrow();
	const method = options?.method ?? 'GET';
	const url = new URL(path, config.storeApiUrl);
	const headers = new Headers({
		Accept: 'application/json',
	});

	if ('GET' !== method && storeApiNonce) {
		headers.set('Nonce', storeApiNonce);
	}

	if (cartToken) {
		headers.set('Cart-Token', cartToken);
	}

	if (undefined !== options?.body) {
		headers.set('Content-Type', 'application/json');
	}

	const response = await fetch(url.toString(), {
		method,
		headers,
		credentials: 'same-origin',
		body: undefined === options?.body ? undefined : JSON.stringify(options.body),
	});

	updateStoreApiHeaders(response);

	if (!response.ok) {
		await readErrorResponse(response);
	}

	return (await response.json()) as TResponse;
}

function setPendingState(cartItemKey: string, isPending: boolean) {
	if (isPending) {
		pendingCartItemKeys.add(cartItemKey);
	} else {
		pendingCartItemKeys.delete(cartItemKey);
	}

	syncPendingCartItems();
}

function setCart(cart: StoreApiCart) {
	currentCart = cart;
	syncCartUi(cart);
}

export async function refreshCart() {
	const cart = await requestStoreApi<StoreApiCart>('cart');
	setCart(cart);
	return cart;
}

export async function addCartItem(request: AddCartItemRequest) {
	const itemId = request.variationId && request.variationId > 0 ? request.variationId : request.productId;
	const cart = await requestStoreApi<StoreApiCart>('cart/add-item', {
		method: 'POST',
		body: {
			id: itemId,
			quantity: Math.max(1, request.quantity),
			variation: request.variation ?? [],
		},
	});

	setCart(cart);
	document.dispatchEvent(new CustomEvent('tailwindscore:open-cart'));
	return cart;
}

async function updateCartItemQuantity(cartItemKey: string, quantity: number) {
	setPendingState(cartItemKey, true);

	try {
		const cart = await requestStoreApi<StoreApiCart>(`cart/update-item?key=${encodeURIComponent(cartItemKey)}&quantity=${Math.max(1, quantity)}`, {
			method: 'POST',
		});
		setCart(cart);
	} finally {
		setPendingState(cartItemKey, false);
	}
}

async function removeCartItem(cartItemKey: string) {
	setPendingState(cartItemKey, true);

	try {
		const cart = await requestStoreApi<StoreApiCart>(`cart/remove-item?key=${encodeURIComponent(cartItemKey)}`, {
			method: 'POST',
		});
		setCart(cart);
		const config = getCartConfig();
		notify({
			type: 'success',
			message: config?.i18n.itemRemoved ?? 'Item removed from your cart.',
			surface: 'toast',
		});
	} finally {
		setPendingState(cartItemKey, false);
	}
}

async function applyCartCoupon(code: string, surface: 'drawer' | 'toast' | 'page' = 'drawer') {
	const trimmed = code.trim();
	const config = getConfigOrThrow();

	if ('' === trimmed) {
		notify({
			type: 'error',
			message: config.i18n.couponRequired,
			surface,
		});
		return;
	}

	try {
		const cart = await requestStoreApi<StoreApiCart>('cart/apply-coupon', {
			method: 'POST',
			body: {
				code: trimmed,
			},
		});
		setCart(cart);
		clearFeedback('drawer');
		notify({
			type: 'success',
			message: config.i18n.couponApplied,
			surface,
		});
	} catch (error) {
		notify({
			type: 'error',
			message: error instanceof Error && error.message ? error.message : config.i18n.couponInvalid,
			surface,
		});
		throw error;
	}
}

async function removeCartCoupon(code: string) {
	const trimmed = code.trim();

	if ('' === trimmed) {
		return;
	}

	const config = getConfigOrThrow();

	const cart = await requestStoreApi<StoreApiCart>('cart/remove-coupon', {
		method: 'POST',
		body: {
			code: trimmed,
		},
	});
	setCart(cart);
	notify({
		type: 'success',
		message: config.i18n.couponRemoved,
		surface: 'toast',
	});
}

function getCheckoutCouponInput(scope?: ParentNode | null) {
	return (
		scope?.querySelector<HTMLInputElement>('[data-ts-checkout-coupon-input]') ??
		document.querySelector<HTMLInputElement>(
			'input[data-ts-checkout-coupon-input][form="woocommerce-checkout-form-coupon"]',
		)
	);
}

function clearCheckoutCouponInput(scope?: ParentNode | null) {
	const input = getCheckoutCouponInput(scope);

	if (input) {
		input.value = '';
	}
}

async function applyCheckoutCouponFromButton(submitButton: HTMLButtonElement | null): Promise<void> {
	const fields = submitButton?.closest('[data-ts-checkout-coupon-fields]');
	const input = getCheckoutCouponInput(fields ?? undefined);
	const code = input?.value ?? '';

	if (!code.trim()) {
		await applyCartCoupon(code, 'toast');
		return;
	}

	try {
		await withControlLoading(submitButton, async () => {
			await applyCartCoupon(code, 'toast');
			clearFeedback('page');
			clearCheckoutCouponInput(fields ?? undefined);
		});
		await refreshCheckoutOrderReview();
	} catch {
		// applyCartCoupon already surfaced the error.
	}
}

function buildCheckoutOrderReviewPayload() {
	const checkoutForm = document.querySelector<HTMLFormElement>('form.checkout');

	if (!checkoutForm || !window.wc_checkout_params) {
		return null;
	}

	const country = (document.querySelector<HTMLInputElement>('#billing_country')?.value ?? '').trim();
	const state = (document.querySelector<HTMLInputElement>('#billing_state')?.value ?? '').trim();
	const postcode = (document.querySelector<HTMLInputElement>('#billing_postcode')?.value ?? '').trim();
	const city = (document.querySelector<HTMLInputElement>('#billing_city')?.value ?? '').trim();
	const address = (document.querySelector<HTMLInputElement>('#billing_address_1')?.value ?? '').trim();
	const address2 = (document.querySelector<HTMLInputElement>('#billing_address_2')?.value ?? '').trim();

	let sCountry = '';
	let sState = '';
	let sPostcode = '';
	let sCity = '';
	let sAddress = '';
	let sAddress2 = '';

	if (document.querySelector<HTMLInputElement>('#ship-to-different-address')?.checked) {
		sCountry = (document.querySelector<HTMLInputElement>('#shipping_country')?.value ?? '').trim();
		sState = (document.querySelector<HTMLInputElement>('#shipping_state')?.value ?? '').trim();
		sPostcode = (document.querySelector<HTMLInputElement>('#shipping_postcode')?.value ?? '').trim();
		sCity = (document.querySelector<HTMLInputElement>('#shipping_city')?.value ?? '').trim();
		sAddress = (document.querySelector<HTMLInputElement>('#shipping_address_1')?.value ?? '').trim();
		sAddress2 = (document.querySelector<HTMLInputElement>('#shipping_address_2')?.value ?? '').trim();
	}

	const paymentMethod =
		document.querySelector<HTMLInputElement>('input[name="payment_method"]:checked')?.value ??
		document.querySelector<HTMLInputElement>('input[name="payment_method"][type="hidden"]')?.value ??
		'';

	const postData = new URLSearchParams();
	new FormData(checkoutForm).forEach((value, key) => {
		if ('string' === typeof value) {
			postData.append(key, value);
		}
	});

	return {
		security: window.wc_checkout_params.update_order_review_nonce,
		payment_method: paymentMethod,
		country,
		state,
		postcode,
		city,
		address,
		address_2: address2,
		s_country: sCountry,
		s_state: sState,
		s_postcode: sPostcode,
		s_city: sCity,
		s_address: sAddress,
		s_address_2: sAddress2,
		has_full_address: Boolean(country && address && city),
		post_data: postData.toString(),
	};
}

async function refreshCheckoutOrderReview(): Promise<void> {
	const $ = (window as unknown as { jQuery?: WooCommerceJQuery }).jQuery;
	const checkoutParams = window.wc_checkout_params;
	const payload = buildCheckoutOrderReviewPayload();

	if (!payload || !checkoutParams || !$?.ajax) {
		document.dispatchEvent(new CustomEvent('tailwindscore:checkout-updated'));
		return;
	}

	await new Promise<void>((resolve) => {
		$.ajax({
			type: 'POST',
			url: checkoutParams.wc_ajax_url.toString().replace('%%endpoint%%', 'update_order_review'),
			data: payload,
			success: (response: { fragments?: Record<string, string> }) => {
				if (response?.fragments) {
					Object.entries(response.fragments).forEach(([selector, markup]) => {
						$(selector).replaceWith(markup);
					});
				}

				$(document.body).trigger('updated_checkout');
				document.dispatchEvent(new CustomEvent('tailwindscore:checkout-updated'));
				resolve();
			},
			error: () => {
				$(document.body).trigger('update_checkout', {
					update_shipping_method: false,
				});
				document.dispatchEvent(new CustomEvent('tailwindscore:checkout-updated'));
				resolve();
			},
		});
	});
}

function getQuantityInput(cartItemKey: string) {
	return document.querySelector<HTMLInputElement>(`[data-ts-cart-quantity-input][data-cart-item-key="${cartItemKey}"]`);
}

function bindQuantityControls() {
	document.addEventListener('click', async (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const stepButton = target.closest<HTMLButtonElement>('[data-ts-cart-quantity-step]');

		if (stepButton) {
			event.preventDefault();

			const cartItemKey = stepButton.dataset.cartItemKey;
			const step = Number(stepButton.dataset.tsCartQuantityStep ?? '0');

			if (!cartItemKey || Number.isNaN(step)) {
				return;
			}

			const input = getQuantityInput(cartItemKey);

			if (!input) {
				return;
			}

			const nextQuantity = Math.max(0, Number(input.value || '0') + step);
			input.value = String(nextQuantity);

			try {
				if (nextQuantity < 1) {
					await removeCartItem(cartItemKey);
				} else {
					await updateCartItemQuantity(cartItemKey, nextQuantity);
				}
			} catch (error) {
				reportCartError(error);
			}

			return;
		}

		const removeButton = target.closest<HTMLButtonElement>('[data-ts-cart-remove]');

		if (!removeButton) {
			return;
		}

		event.preventDefault();

		const cartItemKey = removeButton.dataset.cartItemKey;

		if (!cartItemKey) {
			return;
		}

		try {
			await removeCartItem(cartItemKey);
		} catch (error) {
			reportCartError(error);
		}
	});

	document.addEventListener('change', async (event) => {
		const target = event.target;

		if (!(target instanceof HTMLInputElement) || !target.matches('[data-ts-cart-quantity-input]')) {
			return;
		}

		const cartItemKey = target.dataset.cartItemKey;

		if (!cartItemKey) {
			return;
		}

		try {
			const nextQuantity = Number(target.value || '0');

			if (nextQuantity < 1) {
				await removeCartItem(cartItemKey);
				return;
			}

			await updateCartItemQuantity(cartItemKey, nextQuantity);
		} catch (error) {
			reportCartError(error);
		}
	});
}

function bindCouponControls() {
	document.addEventListener('submit', async (event) => {
		const target = event.target;

		if (!(target instanceof HTMLFormElement) || !target.matches('[data-ts-cart-coupon-form]')) {
			return;
		}

		event.preventDefault();

		const input = target.querySelector<HTMLInputElement>('[data-ts-cart-coupon-input]');
		const code = input?.value ?? '';
		const submitButton = resolveCouponApplyButton(target);

		if (!code.trim()) {
			await applyCartCoupon(code);
			return;
		}

		try {
			await withControlLoading(submitButton, () => applyCartCoupon(code));
		} catch {
			return;
		}

		if (input) {
			input.value = '';
		}
	});
}

function bindCheckoutCouponControls() {
	if (!document.body.classList.contains('woocommerce-checkout')) {
		return;
	}

	document.addEventListener(
		'click',
		async (event) => {
			const target = event.target;

			if (!(target instanceof Element)) {
				return;
			}

			const removeLink = target.closest<HTMLElement>('.woocommerce-remove-coupon');

			if (!removeLink) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			const coupon = removeLink.dataset.coupon;

			if (!coupon) {
				return;
			}

			const removeControl = removeLink instanceof HTMLAnchorElement ? removeLink : null;

			try {
				await withControlLoading(removeControl, async () => {
					clearCheckoutCouponInput();
					clearFeedback('page');
					await removeCartCoupon(coupon);
					await refreshCheckoutOrderReview();
				});
			} catch (error) {
				reportCartError(error, 'toast');
			}
		},
		true,
	);

	document.addEventListener(
		'click',
		async (event) => {
			const target = event.target;

			if (!(target instanceof Element)) {
				return;
			}

			const applyButton = target.closest<HTMLButtonElement>(
				'button[data-ts-cart-coupon-submit][form="woocommerce-checkout-form-coupon"]',
			);

			if (!applyButton) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			await applyCheckoutCouponFromButton(applyButton);
		},
		true,
	);

	document.addEventListener(
		'submit',
		async (event) => {
			const target = event.target;

			if (!(target instanceof HTMLFormElement)) {
				return;
			}

			const isCheckoutCouponForm =
				target.id === 'woocommerce-checkout-form-coupon' || target.matches('[data-ts-checkout-coupon-form]');

			if (!isCheckoutCouponForm) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			event.stopImmediatePropagation();

			const submitButton = resolveCouponApplyButton(target, event.submitter);
			await applyCheckoutCouponFromButton(submitButton);
		},
		true,
	);
}

function initializeCartUi() {
	const config = getCartConfig();

	if (!config) {
		return;
	}

	const isCheckout = document.body.classList.contains('woocommerce-checkout');

	storeApiNonce = config.nonce;
	bindQuantityControls();
	bindCouponControls();
	bindCheckoutCouponControls();

	// Avoid Store API GET /cart on checkout: calculate_totals() can persist a stale coupon
	// back to the session after a concurrent remove (classic WC AJAX vs Store API race).
	if (!isCheckout) {
		void refreshCart().catch((error) => {
			reportCartError(error);
		});
	}
}

initializeCartUi();
