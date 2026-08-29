import type { WcVariationRow } from '../product/types';
import {
	collectVariationFromUi,
	initVariationForm,
} from '../product/variation-engine';
import { initSizeGuideInlineInRoot } from '../product/size-guide';

function updatePriceEl(root: HTMLElement, priceHtml: string | undefined): void {
	if (!priceHtml) {
		return;
	}

	const targets = root.querySelectorAll<HTMLElement>(
		'.wc-product-detail__summary .price, .cart-item-edit-form__price',
	);
	targets.forEach((priceEl) => {
		priceEl.innerHTML = priceHtml;
	});
}

function applyCartEditImage(img: HTMLImageElement, variation: WcVariationRow): void {
	const image = variation.image;
	if (!image) {
		return;
	}

	const nextSrc = image.gallery_thumbnail_src || image.src || '';
	if ('' === nextSrc) {
		return;
	}

	img.src = nextSrc;

	if (image.srcset) {
		img.srcset = image.srcset;
	} else {
		img.removeAttribute('srcset');
	}

	if (image.sizes) {
		img.sizes = image.sizes;
	} else {
		img.removeAttribute('sizes');
	}

	if (image.alt) {
		img.alt = image.alt;
	}
}

function restoreCartEditImage(img: HTMLImageElement): void {
	const fallbackSrc = img.dataset.tsCartItemEditImageFallbackSrc ?? '';
	if ('' === fallbackSrc) {
		return;
	}

	img.src = fallbackSrc;

	const fallbackSrcset = img.dataset.tsCartItemEditImageFallbackSrcset ?? '';
	if ('' !== fallbackSrcset) {
		img.srcset = fallbackSrcset;
	} else {
		img.removeAttribute('srcset');
	}

	const fallbackSizes = img.dataset.tsCartItemEditImageFallbackSizes ?? '';
	if ('' !== fallbackSizes) {
		img.sizes = fallbackSizes;
	} else {
		img.removeAttribute('sizes');
	}

	const fallbackAlt = img.dataset.tsCartItemEditImageFallbackAlt ?? '';
	if ('' !== fallbackAlt) {
		img.alt = fallbackAlt;
	}
}

/**
 * Initialize variation forms inside Quick View or cart-item-edit content.
 */
export async function initQuickViewVariations(root: HTMLElement): Promise<void> {
	initVariationForm(root, {
		onShowVariation(variation) {
			updatePriceEl(root, variation.price_html);

			const editImg = root.querySelector<HTMLImageElement>('.cart-item-edit-form__image img');
			if (editImg) {
				applyCartEditImage(editImg, variation);
			}
		},
		onHideVariation() {
			const editImg = root.querySelector<HTMLImageElement>('.cart-item-edit-form__image img');
			if (editImg) {
				restoreCartEditImage(editImg);
			}
		},
	});
	initSizeGuideInlineInRoot(root);
}

export { collectVariationFromUi };
