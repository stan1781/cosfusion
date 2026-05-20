export {};

type TailwindscoreCartConfig = {
	ajaxUrl: string;
	addToCartAction: string;
	updateCartAction: string;
	removeCartAction: string;
	nonce: string;
};

type WooFragmentResponse = {
	success?: boolean;
	data?: {
		fragments?: Record<string, string>;
		message?: string;
	};
	fragments?: Record<string, string>;
	message?: string;
};

declare global {
	interface Window {
		jQuery?: (...args: unknown[]) => {
			on: (events: string, handler: (...args: unknown[]) => void) => void;
			trigger: (eventName: string) => void;
		};
		tailwindscoreCart?: TailwindscoreCartConfig;
	}
}

const cartDrawerSelector = '[data-ts-cart-drawer-inner]';
const pendingCartItemKeys = new Set<string>();

function getCartConfig() {
	if (window.tailwindscoreCart) {
		return window.tailwindscoreCart;
	}

	const configNode = document.querySelector<HTMLElement>('[data-ts-cart-config]');

	if (!configNode) {
		return undefined;
	}

	const ajaxUrl = configNode.dataset.ajaxUrl;
	const addToCartAction = configNode.dataset.addToCartAction;
	const updateCartAction = configNode.dataset.updateCartAction;
	const removeCartAction = configNode.dataset.removeCartAction;
	const nonce = configNode.dataset.nonce;

	if (!ajaxUrl || !addToCartAction || !updateCartAction || !removeCartAction || !nonce) {
		return undefined;
	}

	return {
		ajaxUrl,
		addToCartAction,
		updateCartAction,
		removeCartAction,
		nonce,
	};
}

export function applyFragments(fragments?: Record<string, string>) {
	if (!fragments) {
		return;
	}

	Object.entries(fragments).forEach(([selector, markup]) => {
		document.querySelectorAll<HTMLElement>(selector).forEach((element) => {
			element.innerHTML = markup;
		});
	});

	document.dispatchEvent(new CustomEvent('tailwindscore:cart-fragments-applied'));
	syncPendingCartItems();
}

function getCartItemElement(cartItemKey: string) {
	return document.querySelector<HTMLElement>(`[data-ts-cart-item][data-cart-item-key="${cartItemKey}"]`);
}

function toggleCartItemPending(cartItemKey: string, isPending: boolean) {
	const cartItem = getCartItemElement(cartItemKey);

	if (!cartItem) {
		return;
	}

	cartItem.toggleAttribute('aria-busy', isPending);
	cartItem.dataset.state = isPending ? 'pending' : 'idle';

	cartItem
		.querySelectorAll<HTMLButtonElement | HTMLInputElement | HTMLAnchorElement>(
			'[data-ts-cart-quantity-step], [data-ts-cart-quantity-input], [data-ts-cart-remove]',
		)
		.forEach((control) => {
			if (control instanceof HTMLAnchorElement) {
				control.setAttribute('aria-disabled', isPending ? 'true' : 'false');
				control.tabIndex = isPending ? -1 : 0;
				return;
			}

			control.disabled = isPending;
		});
}

function syncPendingCartItems() {
	document.querySelectorAll<HTMLElement>('[data-ts-cart-item]').forEach((item) => {
		const cartItemKey = item.dataset.cartItemKey;
		const isPending = !!cartItemKey && pendingCartItemKeys.has(cartItemKey);

		item.toggleAttribute('aria-busy', isPending);
		item.dataset.state = isPending ? 'pending' : 'idle';

		item
			.querySelectorAll<HTMLButtonElement | HTMLInputElement | HTMLAnchorElement>(
				'[data-ts-cart-quantity-step], [data-ts-cart-quantity-input], [data-ts-cart-remove]',
			)
			.forEach((control) => {
				if (control instanceof HTMLAnchorElement) {
					control.setAttribute('aria-disabled', isPending ? 'true' : 'false');
					control.tabIndex = isPending ? -1 : 0;
					return;
				}

				control.disabled = isPending;
			});
	});
}

async function requestCartMutation(payload: URLSearchParams) {
	const cartConfig = getCartConfig();

	if (!cartConfig) {
		throw new Error('Cart config unavailable.');
	}

	const response = await fetch(cartConfig.ajaxUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: payload.toString(),
		credentials: 'same-origin',
	});

	if (!response.ok) {
		throw new Error('Cart mutation failed.');
	}

	const data = (await response.json()) as WooFragmentResponse;
	const fragments = data.data?.fragments ?? data.fragments;
	const message = data.data?.message ?? data.message;

	if (data.success === false) {
		throw new Error(message || 'Cart mutation failed.');
	}

	applyFragments(fragments);
}

async function updateCartItemQuantity(cartItemKey: string, quantity: number) {
	const cartConfig = getCartConfig();

	if (!cartConfig) {
		throw new Error('Cart config unavailable.');
	}

	pendingCartItemKeys.add(cartItemKey);
	toggleCartItemPending(cartItemKey, true);

	try {
		await requestCartMutation(
			new URLSearchParams({
				action: cartConfig.updateCartAction,
				nonce: cartConfig.nonce,
				cart_item_key: cartItemKey,
				quantity: String(Math.max(1, quantity)),
			}),
		);
	} finally {
		pendingCartItemKeys.delete(cartItemKey);
		toggleCartItemPending(cartItemKey, false);
		syncPendingCartItems();
	}
}

async function removeCartItem(cartItemKey: string) {
	const cartConfig = getCartConfig();

	if (!cartConfig) {
		throw new Error('Cart config unavailable.');
	}

	pendingCartItemKeys.add(cartItemKey);
	toggleCartItemPending(cartItemKey, true);

	try {
		await requestCartMutation(
			new URLSearchParams({
				action: cartConfig.removeCartAction,
				nonce: cartConfig.nonce,
				cart_item_key: cartItemKey,
			}),
		);
	} finally {
		pendingCartItemKeys.delete(cartItemKey);
		toggleCartItemPending(cartItemKey, false);
		syncPendingCartItems();
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

		const removeButton = target.closest<HTMLAnchorElement>('[data-ts-cart-remove]');

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
			const href = removeButton.getAttribute('href');

			if (href) {
				window.location.href = href;
			}
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

function bindWooEvents() {
	if (!window.jQuery) {
		return;
	}

	const $body = window.jQuery(document.body);

	$body.on('added_to_cart removed_from_cart', (...args: unknown[]) => {
		const fragments = args[1] as Record<string, string> | undefined;
		applyFragments(fragments);
	});
}

bindQuantityControls();
bindWooEvents();
syncPendingCartItems();
