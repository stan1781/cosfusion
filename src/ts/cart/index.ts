import { clearFeedback, decodeHtmlEntities, notify } from './notifications';
import { resolveCouponApplyButton, withControlLoading } from '../ui/button-loading';
import {
	bindCouponApplyDisabledState,
	initCouponApplyDisabledState,
	syncCouponApplyButtonForInput,
} from '../ui/coupon-form';
import { initProductCarousels } from '../shop/product-carousel';
import { mountRecentlyViewedHosts } from '../product/recently-viewed';
import { escapeHtml } from '../utils/escape-html';

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
	shippingAtCheckout: string;
};

type CartIconConfig = {
	close?: string;
	plus: string;
	minus: string;
	trash: string;
	pencil: string;
	gift?: string;
};

type NotificationSelectorConfig = {
	toast?: string;
	drawer?: string;
	inline?: string;
	page?: string;
};

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

type TailwindscoreCartConfig = {
	storeApiUrl: string;
	nonce: string;
	showDrawerCoupon?: boolean;
	showDrawerFreeShippingProgress?: boolean;
	couponJs?: boolean;
	cartUrl: string;
	checkoutUrl: string;
	shopUrl: string;
	productSuggestionsHtml?: string;
	notifications?: {
		selectors?: NotificationSelectorConfig;
	};
	i18n: CartI18nConfig;
	icons: CartIconConfig;
	cartItemEdit?: CartItemEditConfig;
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

type StoreApiCartVariation = {
	attribute: string;
	value: string;
};

type StoreApiCartItemData = {
	key?: string;
	name?: string;
	value?: string;
	display?: string;
	hidden?: boolean | string;
};

type CartLineItemMetaRow = {
	type: 'variation' | 'custom';
	label: string;
	value: string;
};

type StoreApiCartItem = {
	key: string;
	id: number;
	type?: string;
	quantity: number;
	name: string;
	permalink: string;
	images: StoreApiImage[];
	variation?: StoreApiCartVariation[];
	item_data?: StoreApiCartItemData[];
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
	extensions?: {
		tailwindscore?: {
			freeShippingProgress?: {
				qualified?: boolean;
				percent?: number;
				message?: string;
			} | null;
		};
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
const cartPageSelector = '[data-ts-cart-page-inner]';
const pendingCartItemKeys = new Set<string>();
let currentCart: StoreApiCart | null = null;
let storeApiNonce: string | undefined;
let storeApiNonceRefresh: Promise<void> | null = null;
let storeApiNonceFreshAt = 0;
/** Trust page-injected nonce for a short window; 403 still retries with a fresh GET. */
const STORE_API_NONCE_TTL_MS = 60_000;
let lastCartSyncAt = 0;

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
			shippingAtCheckout: 'Shipping and taxes calculated at checkout.',
		},
		icons: {
			plus: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
			minus: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"></path></svg>',
			trash: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12h10l1-12"></path><path d="M9 7V5h6v2"></path></svg>',
			pencil: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
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
		storeApiNonceFreshAt = Date.now();
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

	return `<span class="site-header__badge" aria-hidden="true">${count}</span>`;
}

function renderCartScreenReader(count: number, config: TailwindscoreCartConfig) {
	const template = count === 1 ? config.i18n.cartScreenReaderSingular : config.i18n.cartScreenReaderPlural;

	return `<span class="screen-reader-text">${escapeHtml(replaceCount(template, count))}</span>`;
}

function renderCartSummary(count: number, config: TailwindscoreCartConfig) {
	const template = count === 1 ? config.i18n.cartSummarySingular : config.i18n.cartSummaryPlural;
	return replaceCount(template, count);
}

function isCartItemEditable(item: StoreApiCartItem): boolean {
	if (item.type === 'variation') {
		return true;
	}

	return Array.isArray(item.variation) && item.variation.length > 0;
}

function getCartLineItemMeta(item: StoreApiCartItem): CartLineItemMetaRow[] {
	const meta: CartLineItemMetaRow[] = [];

	if (Array.isArray(item.variation)) {
		for (const row of item.variation) {
			const label = row.attribute?.trim() ?? '';
			const value = row.value?.trim() ?? '';

			if (!label || !value) {
				continue;
			}

			meta.push({
				type: 'variation',
				label,
				value,
			});
		}
	}

	if (Array.isArray(item.item_data)) {
		for (const row of item.item_data) {
			if (row.hidden === true || row.hidden === '1') {
				continue;
			}

			const label = (row.key || row.name || '').trim();
			const value = (row.display || row.value || '').trim();

			if (!label || !value) {
				continue;
			}

			meta.push({
				type: 'custom',
				label,
				value,
			});
		}
	}

	return meta;
}

function renderCartLineItemMeta(item: StoreApiCartItem): string {
	const meta = getCartLineItemMeta(item);

	if (meta.length === 0) {
		return '';
	}

	const rows = meta
		.map(
			({ label, value }) => `
		<li class="cart-drawer-cart__detail">
			<span class="cart-drawer-cart__detail-label">${escapeHtml(label)}:</span>
			<span class="cart-drawer-cart__detail-value">${escapeHtml(value)}</span>
		</li>`
		)
		.join('');

	return `<ul class="cart-drawer-cart__details">${rows}</ul>`;
}

function renderCartItemEditButton(item: StoreApiCartItem, config: TailwindscoreCartConfig): string {
	if (!isCartItemEditable(item) || !config.cartItemEdit) {
		return '';
	}

	const label = escapeHtml(config.cartItemEdit.i18n.title);

	return `
		<button
			type="button"
			class="cart-drawer-cart__edit"
			data-ts-cart-item-edit
			data-cart-item-key="${escapeHtml(item.key)}"
			aria-label="${label}"
		>
			<span class="icon" aria-hidden="true">${config.icons.pencil}</span>
		</button>
	`;
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
	const isRemoveStep = item.quantity < 2;
	const decreaseLabel = escapeHtml(isRemoveStep ? config.i18n.removeItem : config.i18n.decreaseQuantity);
	const decreaseIcon = isRemoveStep ? config.icons.trash : config.icons.minus;
	const decreaseClass = isRemoveStep ? 'quantity__step quantity__step--remove' : 'quantity__step';

	return `
		<li class="cart-drawer-cart__item" data-ts-cart-item data-cart-item-key="${escapeHtml(item.key)}">
			<div class="cart-drawer-cart__media">
				${productUrl ? `<a href="${productUrl}">${imageMarkup}</a>` : imageMarkup}
			</div>
			<div class="cart-drawer-cart__content stack">
				<div class="cart-drawer-cart__row">
					<div class="cart-drawer-cart__meta stack">
						${productUrl ? `<a class="cart-drawer-cart__name" href="${productUrl}">${productName}</a>` : `<span class="cart-drawer-cart__name">${productName}</span>`}
						${renderCartLineItemMeta(item)}
						<span class="cart-drawer-cart__quantity">${item.quantity} x ${escapeHtml(unitPrice)}</span>
					</div>
				</div>
				<div class="cart-drawer-cart__controls">
					<div class="quantity quantity quantity-compact" data-ts-quantity data-ts-cart-quantity-controls>
						<button
							type="button"
							class="${decreaseClass}"
							data-ts-quantity-step="-1"
							data-cart-item-key="${escapeHtml(item.key)}"
							aria-label="${decreaseLabel}"
						>
							<span class="icon" aria-hidden="true">${decreaseIcon}</span>
						</button>
						<label class="screen-reader-text" for="cart-qty-${escapeHtml(item.key)}">${escapeHtml(config.i18n.quantityLabel)}</label>
						<input
							id="ts-cart-qty-${escapeHtml(item.key)}"
							class="quantity__input qty"
							type="number"
							inputmode="numeric"
							min="0"${maxAttribute}
							step="1"
							value="${item.quantity}"
							data-ts-quantity-input
							data-cart-item-key="${escapeHtml(item.key)}"
						>
						<button
							type="button"
							class="quantity__step"
							data-ts-quantity-step="1"
							data-cart-item-key="${escapeHtml(item.key)}"
							aria-label="${escapeHtml(config.i18n.increaseQuantity)}"
						>
							<span class="icon" aria-hidden="true">${config.icons.plus}</span>
						</button>
					</div>
					${renderCartItemEditButton(item, config)}
				</div>
				<div class="cart-drawer-cart__subtotal">${escapeHtml(subtotal)}</div>
			</div>
		</li>
	`;
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

function isCartPageActive() {
	return document.querySelector('[data-ts-cart-page]') instanceof HTMLElement;
}

function getDefaultFeedbackSurface(): 'drawer' | 'toast' | 'page' {
	return isCartPageActive() ? 'page' : 'toast';
}

function renderCartFooterTotals(cart: StoreApiCart, config: TailwindscoreCartConfig, includeShippingNote = false) {
	const discount = Number(cart.totals.total_discount || 0);
	const subtotalAfterDiscount = getCartSubtotalAfterDiscount(cart);
	const discountRow =
		discount > 0
			? `
				<div class="cart-drawer-cart__totals cart-drawer-cart__totals--discount">
					<span>${escapeHtml(config.i18n.couponDiscount)}</span>
					<strong class="cart-drawer-cart__discount-amount">${escapeHtml(formatDiscountAmount(String(discount), cart.totals))}</strong>
				</div>
			`
			: '';
	const shippingNote = includeShippingNote
		? `<p class="wc-cart__shipping-note">${escapeHtml(config.i18n.shippingAtCheckout)}</p>`
		: '';

	return `
		<div class="cart-drawer-cart__totals-group stack">
			${discountRow}
			<div class="cart-drawer-cart__totals cart-drawer-cart__totals--subtotal">
				<span>${escapeHtml(config.i18n.subtotal)}</span>
				<strong data-ts-cart-subtotal>${escapeHtml(formatMoney(String(subtotalAfterDiscount), cart.totals))}</strong>
			</div>
			${shippingNote}
		</div>
	`;
}

function renderCartCouponMarkup(config: TailwindscoreCartConfig, surface: 'drawer' | 'page' = 'drawer') {
	const inputId = 'page' === surface ? 'cart-page-coupon-code' : 'cart-coupon-code';
	const formModifier = 'page' === surface ? 'coupon-form--cart-page' : 'coupon-form--drawer';

	return `
		<div class="cart-drawer-cart__coupon" data-ts-cart-coupon>
			<form class="cart-drawer-cart__coupon-form coupon-form ${formModifier}" data-ts-cart-coupon-form action="${escapeHtml(config.cartUrl)}" method="post">
				<label class="coupon-form__label" for="${inputId}">${escapeHtml(config.i18n.couponLabel)}</label>
				<div class="input-inline">
					<input
						id="${inputId}"
						class="input"
						type="text"
						name="coupon_code"
						autocomplete="off"
						data-ts-cart-coupon-input
						placeholder="${escapeHtml(config.i18n.couponPlaceholder)}"
					>
					<button class="btn btn-secondary" type="submit" disabled data-ts-button-loading data-ts-cart-coupon-submit>${escapeHtml(config.i18n.applyCoupon)}</button>
				</div>
			</form>
		</div>
	`;
}

function renderFreeShippingProgress(cart: StoreApiCart, config: TailwindscoreCartConfig): string {
	if (config.showDrawerFreeShippingProgress === false) {
		return '';
	}

	const progress = cart.extensions?.tailwindscore?.freeShippingProgress;

	if (!progress || typeof progress.message !== 'string') {
		return '';
	}

	const percent = Math.max(0, Math.min(100, Number(progress.percent) || 0));
	const qualified = progress.qualified === true;
	const giftIcon = config.icons.gift ?? '';

	return `
		<div
			class="cart-free-shipping-progress"
			data-ts-cart-free-shipping-progress
			${qualified ? ' data-qualified="true"' : ''}
		>
			<p class="cart-free-shipping-progress__message">
				<span class="cart-free-shipping-progress__icon" aria-hidden="true"><span class="icon">${giftIcon}</span></span>
				<span class="cart-free-shipping-progress__text">${escapeHtml(progress.message)}</span>
			</p>
			<div class="cart-free-shipping-progress__track">
				<div
					class="cart-free-shipping-progress__bar"
					role="progressbar"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow="${percent}"
					aria-label="${escapeHtml('Free shipping progress')}"
				>
					<div class="cart-free-shipping-progress__fill" style="width: ${percent}%;"></div>
				</div>
				<span class="cart-free-shipping-progress__percent">${percent}%</span>
			</div>
		</div>`;
}

function renderEmptyCartMarkup(config: TailwindscoreCartConfig, wrapperClass: string): string {
	const recentlyHost =
		wrapperClass.includes('cart-page-cart') && window.tailwindscoreRecentlyViewed?.showOnCart
			? `<div class="recently-viewed-host" data-ts-recently-viewed-host data-ts-recently-viewed-exclude="0" data-ts-recently-viewed-limit="8" hidden></div>`
			: '';

	return `
		<div class="${wrapperClass}">
			<div class="cart-drawer-cart__empty stack">
				<p>${escapeHtml(config.i18n.emptyCartMessage)}</p>
				<a class="btn btn-outline" href="${escapeHtml(config.shopUrl)}">${escapeHtml(config.i18n.browseProducts)}</a>
			</div>
			${recentlyHost}
		</div>
	`;
}

function renderCartDrawer(cart: StoreApiCart, config: TailwindscoreCartConfig) {
	if (cart.items.length < 1) {
		return renderEmptyCartMarkup(config, 'cart-drawer-cart');
	}

	return `
		<div class="cart-drawer-cart cart-drawer-cart--filled">
			<div class="cart-drawer-cart__body">
				${renderFreeShippingProgress(cart, config)}
				<ul class="cart-drawer-cart__items" data-ts-cart-items>
					${cart.items.map((item) => renderCartItem(item, config)).join('')}
				</ul>
			</div>
			${config.showDrawerCoupon !== false ? renderCartCouponMarkup(config) : ''}
			<div class="cart-drawer-cart__footer stack" data-ts-cart-footer>
				${renderCartFooterTotals(cart, config)}
				<a class="btn" href="${escapeHtml(config.checkoutUrl)}" data-ts-button-loading>${escapeHtml(config.i18n.checkout)}</a>
				<a class="cart-drawer-cart__view-cart" href="${escapeHtml(config.cartUrl)}">${escapeHtml(config.i18n.viewCart)}</a>
			</div>
		</div>
	`;
}

function renderCartPage(cart: StoreApiCart, config: TailwindscoreCartConfig) {
	if (cart.items.length < 1) {
		return renderEmptyCartMarkup(config, 'cart-page-cart');
	}

	return `
		<div class="cart-page-cart cart-page-cart--filled">
			<div class="wc-cart__scroll">
				<div class="cart-drawer-cart__body">
					<ul class="cart-drawer-cart__items wc-cart__items" data-ts-cart-items>
						${cart.items.map((item) => renderCartItem(item, config)).join('')}
					</ul>
				</div>
				${config.productSuggestionsHtml ?? ''}
			</div>
			<div class="wc-cart__sidebar">
				${renderFreeShippingProgress(cart, config)}
				<aside class="wc-cart__aside">
					${renderCartCouponMarkup(config, 'page')}
					<div class="wc-cart__footer-bar">
						<div class="cart-drawer-cart__footer stack cart-drawer-cart__footer--cart-page" data-ts-cart-footer>
							${renderCartFooterTotals(cart, config, true)}
							<a class="btn btn-primary" href="${escapeHtml(config.checkoutUrl)}" data-ts-button-loading>${escapeHtml(config.i18n.checkout)}</a>
						</div>
					</div>
				</aside>
			</div>
		</div>
	`;
}

function reportCartError(error: unknown, surface?: 'drawer' | 'toast' | 'page') {
	const message = error instanceof Error ? error.message : 'Store API request failed.';

	notify({
		type: 'error',
		message,
		surface: surface ?? getDefaultFeedbackSurface(),
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
				'[data-ts-quantity-step], [data-ts-quantity-input], [data-ts-cart-item-edit]',
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

	document.querySelectorAll<HTMLElement>('[data-ts-cart-badge]').forEach((element) => {
		element.innerHTML = renderCartBadge(count);
	});

	document.querySelectorAll<HTMLElement>('[data-ts-cart-screen-reader]').forEach((element) => {
		element.innerHTML = renderCartScreenReader(count, config);
	});

	document.querySelectorAll<HTMLElement>('[data-ts-cart-summary]').forEach((element) => {
		element.textContent = summary;
	});

	// Only build each markup string when its target exists on the current page
	// (drawer is sitewide, cart page is not; avoids rendering unused HTML on every sync).
	const drawerElements = document.querySelectorAll<HTMLElement>(cartDrawerSelector);
	if (drawerElements.length > 0) {
		const drawerMarkup = renderCartDrawer(cart, config);
		drawerElements.forEach((element) => {
			element.innerHTML = drawerMarkup;
			initCouponApplyDisabledState(element);
		});
	}

	const pageElements = document.querySelectorAll<HTMLElement>(cartPageSelector);
	if (pageElements.length > 0) {
		const pageMarkup = renderCartPage(cart, config);
		pageElements.forEach((element) => {
			element.innerHTML = pageMarkup;
			initCouponApplyDisabledState(element);
			initProductCarousels(element);
			mountRecentlyViewedHosts(element);
		});
	}

	syncPendingCartItems();
}

function updateStoreApiHeaders(response: Response) {
	const nextNonce = response.headers.get('Nonce');

	if (nextNonce) {
		storeApiNonce = nextNonce;
		storeApiNonceFreshAt = Date.now();
	}
}

async function parseStoreApiError(response: Response): Promise<StoreApiErrorResponse | null> {
	try {
		return (await response.json()) as StoreApiErrorResponse;
	} catch {
		return null;
	}
}

async function readErrorResponse(response: Response, payload: StoreApiErrorResponse | null = null) {
	const errorPayload = payload ?? (await parseStoreApiError(response));

	if (errorPayload?.data?.cart) {
		currentCart = errorPayload.data.cart;
		syncCartUi(errorPayload.data.cart);
	}

	throw new Error(decodeHtmlEntities(errorPayload?.message || 'Store API request failed.'));
}

async function requestStoreApi<TResponse>(
	path: string,
	options?: { method?: string; body?: unknown },
	allowNonceRetry = true,
) {
	const config = getConfigOrThrow();
	const method = options?.method ?? 'GET';

	if ('GET' !== method) {
		await refreshStoreApiNonce();
	}

	const url = new URL(path, config.storeApiUrl);
	const headers = new Headers({
		Accept: 'application/json',
	});

	if ('GET' !== method && storeApiNonce) {
		headers.set('Nonce', storeApiNonce);
	}

	// Always use the browser cookie session. Cart-Token switches to a separate session
	// so add-to-cart via Store API would not appear on the PHP checkout page.

	if (undefined !== options?.body) {
		headers.set('Content-Type', 'application/json');
	}

	if ('GET' === method) {
		url.searchParams.set('_', String(Date.now()));
	}

	const response = await fetch(url.toString(), {
		method,
		headers,
		credentials: 'same-origin',
		cache: 'no-store',
		body: undefined === options?.body ? undefined : JSON.stringify(options.body),
	});

	updateStoreApiHeaders(response);

	if (!response.ok) {
		const payload = await parseStoreApiError(response);

		if (
			allowNonceRetry &&
			'GET' !== method &&
			response.status === 403 &&
			payload?.code === 'woocommerce_rest_invalid_nonce'
		) {
			// WooCommerce returns a fresh nonce in the response header; reuse it once.
			if (!response.headers.get('Nonce')) {
				await refreshStoreApiNonce();
			}
			return requestStoreApi<TResponse>(path, options, false);
		}

		await readErrorResponse(response, payload);
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
	lastCartSyncAt = Date.now();
	syncCartUi(cart);
}

async function refreshStoreApiNonce(): Promise<void> {
	if (storeApiNonce && Date.now() - storeApiNonceFreshAt < STORE_API_NONCE_TTL_MS) {
		return;
	}

	if (storeApiNonceRefresh) {
		return storeApiNonceRefresh;
	}

	storeApiNonceRefresh = (async () => {
		const config = getCartConfig();

		if (!config) {
			return;
		}

		const url = new URL('cart', config.storeApiUrl);
		url.searchParams.set('_', String(Date.now()));

		try {
			const response = await fetch(url.toString(), {
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store',
				headers: {
					Accept: 'application/json',
				},
			});

			updateStoreApiHeaders(response);

			if (response.ok) {
				await response.json().catch(() => null);
			}
		} catch {
			// Best-effort; Store API responses also rotate the nonce header.
		}
	})();

	try {
		return await storeApiNonceRefresh;
	} finally {
		storeApiNonceRefresh = null;
	}
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

async function applyCartCoupon(code: string, errorSurface: 'drawer' | 'toast' | 'page' = 'toast') {
	const trimmed = code.trim();
	const config = getConfigOrThrow();

	if ('' === trimmed) {
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
		clearFeedback('page');
		notify({
			type: 'success',
			message: config.i18n.couponApplied,
			surface: 'toast',
		});
	} catch (error) {
		notify({
			type: 'error',
			message: error instanceof Error && error.message ? error.message : config.i18n.couponInvalid,
			surface: errorSurface,
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
		syncCouponApplyButtonForInput(input);
	}
}

async function applyCheckoutCouponFromButton(submitButton: HTMLButtonElement | null): Promise<void> {
	const fields = submitButton?.closest('[data-ts-checkout-coupon-fields]');
	const input = getCheckoutCouponInput(fields ?? undefined);
	const code = input?.value ?? '';

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
	return document.querySelector<HTMLInputElement>(`[data-ts-quantity-input][data-cart-item-key="${cartItemKey}"]`);
}

function bindQuantityControls() {
	document.addEventListener('click', async (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const stepButton = target.closest<HTMLButtonElement>('[data-ts-quantity-step]');

		if (stepButton?.closest('[data-ts-cart-quantity-controls]')) {
			event.preventDefault();

			const cartItemKey = stepButton.dataset.cartItemKey;
			const step = Number(stepButton.dataset.tsQuantityStep ?? '0');

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
		}
	});

	document.addEventListener('change', async (event) => {
		const target = event.target;

		if (!(target instanceof HTMLInputElement) || !target.matches('[data-ts-quantity-input]')) {
			return;
		}

		if (!target.closest('[data-ts-cart-quantity-controls]')) {
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

		try {
			await withControlLoading(submitButton, () => applyCartCoupon(code, getDefaultFeedbackSurface()));
			if (input) {
				input.value = '';
			}
		} catch {
			return;
		} finally {
			if (input) {
				syncCouponApplyButtonForInput(input);
			}
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

function bindDrawerCartRefresh() {
	document.addEventListener('tailwindscore:cart-drawer-opened', () => {
		// add-to-cart already synced the drawer; avoid a stale cached GET overwriting it.
		if (Date.now() - lastCartSyncAt < 2000) {
			return;
		}

		void refreshCart().catch((error) => {
			reportCartError(error);
		});
	});
}

function initializeCartUi() {
	const config = getCartConfig();

	if (!config) {
		return;
	}

	// Treat the SSR-injected Store API nonce as fresh so the first add-to-cart
	// is a single POST (GET /cart only on TTL expiry or invalid-nonce 403).
	storeApiNonce = config.nonce;
	storeApiNonceFreshAt = Date.now();
	bindQuantityControls();

	if (config.couponJs !== false) {
		bindCouponControls();
		bindCouponApplyDisabledState();
		bindCheckoutCouponControls();
	}

	bindDrawerCartRefresh();
}

initializeCartUi();
