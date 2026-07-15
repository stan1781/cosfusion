import { notify } from '../cart/index';

function getProductForm(): HTMLFormElement | null {
	return document.querySelector<HTMLFormElement>('form.cart, form.variations_form');
}

function getMainAddToCartButton(form: HTMLFormElement): HTMLButtonElement | null {
	return form.querySelector<HTMLButtonElement>('button.single_add_to_cart_button');
}

function scrollToVariations(): void {
	const target =
		document.querySelector<HTMLElement>('.variation-row') ??
		document.querySelector<HTMLElement>('.custom-variation-selector');

	if (!target) {
		return;
	}

	target.scrollIntoView({ behavior: 'smooth', block: 'start' });

	const focusable = target.querySelector<HTMLElement>('li[tabindex="0"], select, button');

	if (focusable) {
		window.requestAnimationFrame(() => {
			focusable.focus();
		});
	}

	notify({
		type: 'warning',
		message: 'Please select product options.',
		surface: 'toast',
	});
}

function handleStickyAddToCart(): void {
	const form = getProductForm();

	if (!form) {
		return;
	}

	const isVariationForm = form.classList.contains('variations_form');
	const variationId = Number(form.querySelector<HTMLInputElement>('input.variation_id')?.value ?? '0');

	if (isVariationForm && variationId < 1) {
		scrollToVariations();
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
