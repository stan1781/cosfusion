import { guideIncompleteVariation } from './variation-atc-ready';

function getProductForm(): HTMLFormElement | null {
	return document.querySelector<HTMLFormElement>('form.cart, form.variations_form');
}

function getMainAddToCartButton(form: HTMLFormElement): HTMLButtonElement | null {
	return form.querySelector<HTMLButtonElement>('button.single_add_to_cart_button');
}

function handleStickyAddToCart(): void {
	const form = getProductForm();

	if (!form) {
		return;
	}

	const isVariationForm = form.classList.contains('variations_form');

	if (isVariationForm && guideIncompleteVariation(form)) {
		return;
	}

	const button = getMainAddToCartButton(form);

	if (!button) {
		return;
	}

	if (typeof form.requestSubmit === 'function') {
		form.requestSubmit(button);
		return;
	}

	button.click();
}

export function initProductStickyBar(): void {
	const bar = document.querySelector<HTMLElement>('[data-ts-product-sticky-bar]');

	if (!bar) {
		return;
	}

	// Always show the sticky bar on product pages
	document.body.classList.add('product-sticky-bar--visible');

	const atc = bar.querySelector<HTMLButtonElement>('[data-ts-product-sticky-atc]');

	atc?.addEventListener('click', (event) => {
		event.preventDefault();
		handleStickyAddToCart();
	});
}
