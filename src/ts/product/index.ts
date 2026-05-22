import { addCartItem, notify } from '../cart/index';
import { setButtonsLoading } from '../ui/button-loading';

function getProductForms() {
	return document.querySelectorAll<HTMLFormElement>('form.cart, form.variations_form');
}

function getSubmitButtons(form: HTMLFormElement) {
	return form.querySelectorAll<HTMLButtonElement>(
		'button.single_add_to_cart_button, button[name="add-to-cart"], button[type="submit"]',
	);
}

function setFormPending(form: HTMLFormElement, isPending: boolean) {
	setButtonsLoading(getSubmitButtons(form), isPending);
}

function collectVariation(formData: FormData) {
	const variation: Array<{
		attribute: string;
		value: string;
	}> = [];

	formData.forEach((value, key) => {
		if (typeof value !== 'string' || !key.startsWith('attribute_') || '' === value) {
			return;
		}

		variation.push({
			attribute: key.replace(/^attribute_/, ''),
			value,
		});
	});

	return variation;
}

function buildAddToCartRequest(form: HTMLFormElement, submitter: HTMLButtonElement | null) {
	const formData = submitter ? new FormData(form, submitter) : new FormData(form);
	const submitterProductId =
		submitter?.name === 'add-to-cart' ? Number(submitter.value || '0') : Number(submitter?.dataset.productId ?? '0');
	const productId = Number(formData.get('add-to-cart') ?? formData.get('product_id') ?? submitterProductId ?? '0');
	const quantity = Math.max(1, Number(formData.get('quantity') ?? '1'));
	const variationId = Math.max(0, Number(formData.get('variation_id') ?? '0'));
	const isVariationForm = form.classList.contains('variations_form');

	if (productId < 1) {
		return null;
	}

	if (isVariationForm && variationId < 1) {
		return null;
	}

	return {
		productId,
		quantity,
		variationId,
		variation: collectVariation(formData),
	};
}

async function handleAddToCart(form: HTMLFormElement, submitter: HTMLButtonElement | null) {
	const request = buildAddToCartRequest(form, submitter);

	if (!request) {
		return false;
	}

	setFormPending(form, true);

	try {
		await addCartItem(request);
		const config = window.tailwindscoreCart;
		notify({
			type: 'success',
			message: config?.i18n.addedToCart ?? 'Added to your cart.',
			surface: 'toast',
		});
		return true;
	} catch (error) {
		console.error(error);
		return false;
	} finally {
		setFormPending(form, false);
	}
}

const isProductPage =
	document.body.classList.contains('single-product') || document.body.classList.contains('product');

if (isProductPage) {
	void import('./gallery');
	document.documentElement.classList.add('has-product-ui');

	getProductForms().forEach((form) => {
		form.addEventListener('submit', async (event) => {
			if (form.dataset.tsBypassAjaxSubmit === 'true') {
				delete form.dataset.tsBypassAjaxSubmit;
				return;
			}

			const submitter =
				event instanceof SubmitEvent && event.submitter instanceof HTMLButtonElement ? event.submitter : null;
			const request = buildAddToCartRequest(form, submitter);

			if (!request) {
				return;
			}

			event.preventDefault();
			const handled = await handleAddToCart(form, submitter);

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
