import { addCartItem } from '../cart/index';
import { buildAddToCartRequest } from './add-to-cart-request';
import { resolveSubmitButton, setButtonsLoading } from '../ui/button-loading';
import { initSizeGuide } from './size-guide';
import { initProductDetailTabs } from './product-detail-tabs';

function getProductForms() {
	return document.querySelectorAll<HTMLFormElement>('form.cart, form.variations_form');
}

function getSubmitButtons(form: HTMLFormElement) {
	return form.querySelectorAll<HTMLButtonElement>(
		'button.single_add_to_cart_button, button[name="add-to-cart"], button[type="submit"]',
	);
}

function setFormPending(form: HTMLFormElement, isPending: boolean, submitter: HTMLButtonElement | null = null) {
	const button = resolveSubmitButton(form, submitter, 'button.single_add_to_cart_button');
	const buttons = button ? [button] : Array.from(getSubmitButtons(form));
	const stickyAtc = document.querySelector<HTMLButtonElement>('[data-ts-product-sticky-atc]');

	if (stickyAtc && !buttons.includes(stickyAtc)) {
		buttons.push(stickyAtc);
	}

	setButtonsLoading(buttons, isPending);
}

async function handleAddToCart(form: HTMLFormElement, submitter: HTMLButtonElement | null) {
	const request = buildAddToCartRequest(form, submitter);

	if (!request) {
		return false;
	}

	setFormPending(form, true, submitter);

	try {
		await addCartItem(request);
		return true;
	} catch (error) {
		console.error(error);
		return false;
	} finally {
		setFormPending(form, false, submitter);
	}
}

const isProductPage =
	document.body.classList.contains('single-product') ||
	document.body.classList.contains('product') ||
	document.body.classList.contains('single-product') ||
	document.querySelector('form.variations_form .custom-variation-selector') !== null;

if (isProductPage) {
	const bootSizeGuide = (): void => {
		initSizeGuide();
	};
	const bootProductDetailTabs = (): void => {
		initProductDetailTabs();
	};
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			bootSizeGuide();
			bootProductDetailTabs();
		});
	} else {
		bootSizeGuide();
		bootProductDetailTabs();
	}
	void import('./variations');
	void import('./gallery');
	void import('./review-images').then(({ initReviewImages, shouldInitReviewImages }) => {
		if (shouldInitReviewImages()) {
			initReviewImages();
		}
	});
	void import('./sticky-bar').then(({ initProductStickyBar }) => {
		initProductStickyBar();
	});
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
