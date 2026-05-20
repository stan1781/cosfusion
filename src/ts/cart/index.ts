export {};

type CartI18nConfig = {
	cartScreenReaderSingular: string;
	cartScreenReaderPlural: string;
	cartSummarySingular: string;
	cartSummaryPlural: string;
	emptyCartMessage: string;
	browseProducts: string;
	subtotal: string;
	checkout: string;
	viewCart: string;
	quantityLabel: string;
	decreaseQuantity: string;
	increaseQuantity: string;
	removeItem: string;
};

type CartIconConfig = {
	plus: string;
	minus: string;
	trash: string;
};

type TailwindscoreCartConfig = {
	storeApiUrl: string;
	nonce: string;
	cartUrl: string;
	checkoutUrl: string;
	shopUrl: string;
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

declare global {
	interface Window {
		tailwindscoreCart?: TailwindscoreCartConfig;
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

	if (!storeApiUrl || !nonce) {
		return undefined;
	}

	return {
		storeApiUrl,
		nonce,
		cartUrl: '/cart/',
		checkoutUrl: '/checkout/',
		shopUrl: '/shop/',
		i18n: {
			cartScreenReaderSingular: 'Cart, %d item',
			cartScreenReaderPlural: 'Cart, %d items',
			cartSummarySingular: '%d item ready for checkout',
			cartSummaryPlural: '%d items ready for checkout',
			emptyCartMessage: 'Your cart is empty.',
			browseProducts: 'Browse products',
			subtotal: 'Subtotal',
			checkout: 'Checkout',
			viewCart: 'View cart',
			quantityLabel: 'Quantity',
			decreaseQuantity: 'Decrease quantity',
			increaseQuantity: 'Increase quantity',
			removeItem: 'Remove item from your cart',
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
	const subtotal = formatMoney(item.totals.line_total, item.totals);
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

function renderCartDrawer(cart: StoreApiCart, config: TailwindscoreCartConfig) {
	if (cart.items.length < 1) {
		return `
			<div class="ts-cart-drawer-cart">
				<div class="ts-cart-drawer-cart__empty ts-stack">
					<p>${escapeHtml(config.i18n.emptyCartMessage)}</p>
					<a class="ts-button" href="${escapeHtml(config.shopUrl)}">${escapeHtml(config.i18n.browseProducts)}</a>
				</div>
			</div>
		`;
	}

	return `
		<div class="ts-cart-drawer-cart ts-cart-drawer-cart--filled">
			<div class="ts-cart-drawer-cart__body">
				<ul class="ts-cart-drawer-cart__items">
					${cart.items.map((item) => renderCartItem(item, config)).join('')}
				</ul>
			</div>
			<div class="ts-cart-drawer-cart__footer ts-stack">
				<div class="ts-cart-drawer-cart__totals">
					<span>${escapeHtml(config.i18n.subtotal)}</span>
					<strong>${escapeHtml(formatMoney(cart.totals.total_items, cart.totals))}</strong>
				</div>
				<a class="ts-button" href="${escapeHtml(config.checkoutUrl)}">${escapeHtml(config.i18n.checkout)}</a>
				<a class="ts-cart-drawer-cart__view-cart" href="${escapeHtml(config.cartUrl)}">${escapeHtml(config.i18n.viewCart)}</a>
			</div>
		</div>
	`;
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
		element.innerHTML = drawerMarkup;
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
	} finally {
		setPendingState(cartItemKey, false);
	}
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
				console.error(error);
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
			console.error(error);
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
			console.error(error);
		}
	});
}

function initializeCartUi() {
	const config = getCartConfig();

	if (!config) {
		return;
	}

	storeApiNonce = config.nonce;
	bindQuantityControls();
	void refreshCart().catch((error) => {
		console.error(error);
	});
}

initializeCartUi();
