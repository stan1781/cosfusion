import {
	collectVariationFromUi,
	initVariationForm,
} from '../product/variation-engine';
import { initSizeGuideInlineInRoot } from '../product/size-guide';

/**
 * Initialize variation forms inside Quick View or cart-item-edit content.
 */
export async function initQuickViewVariations(root: HTMLElement): Promise<void> {
	initVariationForm(root, {
		onShowVariation(variation) {
			const priceEl = root.querySelector<HTMLElement>('.wc-product-detail__summary .price');
			if (priceEl && variation.price_html) {
				priceEl.innerHTML = variation.price_html;
			}
		},
	});
	initSizeGuideInlineInRoot(root);
}

export { collectVariationFromUi };
