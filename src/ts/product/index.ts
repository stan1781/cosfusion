import { applyFragments } from '../cart/index';

type TailwindscoreCartConfig = {
	ajaxUrl: string;
	addToCartAction: string;
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

function getCartConfig() {
	const runtimeWindow = window as typeof window & { tailwindscoreCart?: TailwindscoreCartConfig };

	if (runtimeWindow.tailwindscoreCart) {
		return runtimeWindow.tailwindscoreCart;
	}

	const configNode = document.querySelector<HTMLElement>('[data-ts-cart-config]');

	if (!configNode) {
		return undefined;
	}

	const ajaxUrl = configNode.dataset.ajaxUrl;
	const addToCartAction = configNode.dataset.addToCartAction;
	const nonce = configNode.dataset.nonce;

	if (!ajaxUrl || !addToCartAction || !nonce) {
		return undefined;
	}

	return {
		ajaxUrl,
		addToCartAction,
		nonce,
	};
}

function getProductForms() {
	return document.querySelectorAll<HTMLFormElement>('form.cart, form.variations_form');
}

function getSubmitButtons(form: HTMLFormElement) {
	return form.querySelectorAll<HTMLButtonElement>(
		'button.single_add_to_cart_button, button[name="add-to-cart"], button[type="submit"]',
	);
}

function setFormPending(form: HTMLFormElement, isPending: boolean) {
	getSubmitButtons(form).forEach((button) => {
		button.disabled = isPending;
		button.toggleAttribute('aria-busy', isPending);
		button.classList.toggle('loading', isPending);
	});
}

function collectVariation(formData: FormData) {
	const variation: Record<string, string> = {};

	formData.forEach((value, key) => {
		if (typeof value === 'string' && key.startsWith('attribute_') && value !== '') {
			variation[key] = value;
		}
	});

	return variation;
}

function buildAddToCartPayload(form: HTMLFormElement, submitter: HTMLButtonElement | null) {
	const cartConfig = getCartConfig();
	const formData = submitter ? new FormData(form, submitter) : new FormData(form);
	const submitterProductId =
		submitter?.name === 'add-to-cart' ? Number(submitter.value || '0') : Number(submitter?.dataset.productId ?? '0');
	const productId = Number(formData.get('add-to-cart') ?? formData.get('product_id') ?? submitterProductId ?? '0');
	const quantity = Math.max(1, Number(formData.get('quantity') ?? '1'));
	const variationId = Math.max(0, Number(formData.get('variation_id') ?? '0'));
	const isVariationForm = form.classList.contains('variations_form');

	if (!cartConfig || productId < 1) {
		return null;
	}

	if (isVariationForm && variationId < 1) {
		return null;
	}

	const payload = new URLSearchParams({
		action: cartConfig.addToCartAction,
		nonce: cartConfig.nonce,
		product_id: String(productId),
		quantity: String(quantity),
		variation_id: String(variationId),
	});

	Object.entries(collectVariation(formData)).forEach(([key, value]) => {
		payload.append(`variation[${key}]`, value);
	});

	return payload;
}

async function addToCart(form: HTMLFormElement, submitter: HTMLButtonElement | null, payload: URLSearchParams) {
	const cartConfig = getCartConfig();

	if (!cartConfig) {
		return false;
	}

	setFormPending(form, true);

	try {
		const response = await fetch(cartConfig.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: payload.toString(),
			credentials: 'same-origin',
		});

		if (!response.ok) {
			return false;
		}

		const data = (await response.json()) as WooFragmentResponse;
		const fragments = data.data?.fragments ?? data.fragments;

		if (data.success === false || !fragments) {
			return false;
		}

		applyFragments(fragments);
		document.dispatchEvent(new CustomEvent('tailwindscore:open-cart'));
		return true;
	} finally {
		setFormPending(form, false);
	}
}

if (document.body.classList.contains('single-product')) {
	document.documentElement.classList.add('has-product-ui');

	getProductForms().forEach((form) => {
		form.addEventListener('submit', async (event) => {
			if (form.dataset.tsBypassAjaxSubmit === 'true') {
				delete form.dataset.tsBypassAjaxSubmit;
				return;
			}

			const submitter =
				event instanceof SubmitEvent && event.submitter instanceof HTMLButtonElement ? event.submitter : null;
			const payload = buildAddToCartPayload(form, submitter);

			if (!payload) {
				return;
			}

			event.preventDefault();
			const handled = await addToCart(form, submitter, payload);

			if (!handled) {
				if (submitter && typeof form.requestSubmit === 'function') {
					form.dataset.tsBypassAjaxSubmit = 'true';
					form.requestSubmit(submitter);
					return;
				}

				form.submit();
			}
		});
	});
}
