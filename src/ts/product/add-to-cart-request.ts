export type AddToCartVariationAttribute = {
	attribute: string;
	value: string;
};

export type AddToCartRequest = {
	productId: number;
	quantity: number;
	variationId?: number;
	variation?: AddToCartVariationAttribute[];
};

export function collectVariation(formData: FormData): AddToCartVariationAttribute[] {
	const variation: AddToCartVariationAttribute[] = [];

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

export function buildAddToCartRequest(
	form: HTMLFormElement,
	submitter: HTMLButtonElement | null,
): AddToCartRequest | null {
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
		variationId: variationId > 0 ? variationId : undefined,
		variation: collectVariation(formData),
	};
}
