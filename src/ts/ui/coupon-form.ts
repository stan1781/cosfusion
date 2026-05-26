const COUPON_INPUT_SELECTOR = '[data-ts-cart-coupon-input], [data-ts-checkout-coupon-input]';

export function syncCouponApplyButtonForInput(input: HTMLInputElement): void {
	const button = input
		.closest('.ts-input-group')
		?.querySelector<HTMLButtonElement>('[data-ts-cart-coupon-submit]');

	if (!button) {
		return;
	}

	button.disabled = input.value.trim() === '';
}

export function initCouponApplyDisabledState(root: ParentNode = document): void {
	root.querySelectorAll<HTMLInputElement>(COUPON_INPUT_SELECTOR).forEach(syncCouponApplyButtonForInput);
}

export function bindCouponApplyDisabledState(): void {
	document.addEventListener('input', (event) => {
		const target = event.target;

		if (!(target instanceof HTMLInputElement) || !target.matches(COUPON_INPUT_SELECTOR)) {
			return;
		}

		syncCouponApplyButtonForInput(target);
	});

	initCouponApplyDisabledState();
}
