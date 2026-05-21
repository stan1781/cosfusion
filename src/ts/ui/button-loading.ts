export type LoadableControl = HTMLButtonElement | HTMLAnchorElement;

/**
 * Toggle AJAX loading state on buttons and button-like links.
 * Uses .is-loading (+ legacy .loading) with a CSS spinner (::after).
 */
export function setControlLoading(control: LoadableControl | null | undefined, isLoading: boolean): void {
	if (!control) {
		return;
	}

	control.classList.toggle('is-loading', isLoading);
	control.classList.toggle('loading', isLoading);
	control.setAttribute('aria-busy', isLoading ? 'true' : 'false');

	if (control instanceof HTMLButtonElement) {
		control.disabled = isLoading;
		return;
	}

	if (isLoading) {
		control.setAttribute('aria-disabled', 'true');
		control.style.pointerEvents = 'none';
		return;
	}

	control.removeAttribute('aria-disabled');
	control.style.removeProperty('pointer-events');
}

export async function withControlLoading<T>(
	control: LoadableControl | null | undefined,
	action: () => Promise<T>,
): Promise<T> {
	setControlLoading(control, true);

	try {
		return await action();
	} finally {
		setControlLoading(control, false);
	}
}

export function resolveSubmitButton(
	form: HTMLFormElement | null,
	submitter?: EventTarget | null,
	fallbackSelector?: string,
): HTMLButtonElement | null {
	if (submitter instanceof HTMLButtonElement) {
		return submitter;
	}

	if (form) {
		const scoped =
			form.querySelector<HTMLButtonElement>('[data-ts-button-loading][type="submit"]') ??
			form.querySelector<HTMLButtonElement>('[type="submit"]');

		if (scoped) {
			return scoped;
		}
	}

	if (fallbackSelector) {
		return document.querySelector<HTMLButtonElement>(fallbackSelector);
	}

	return null;
}

export function resolveCouponApplyButton(
	form: HTMLFormElement | null,
	submitter?: EventTarget | null,
): HTMLButtonElement | null {
	if (submitter instanceof HTMLButtonElement) {
		return submitter;
	}

	if (form) {
		const button =
			form.querySelector<HTMLButtonElement>('[data-ts-cart-coupon-submit]') ??
			form.querySelector<HTMLButtonElement>('[type="submit"]');

		if (button) {
			return button;
		}
	}

	return document.querySelector<HTMLButtonElement>(
		'button[form="woocommerce-checkout-form-coupon"][data-ts-cart-coupon-submit]',
	);
}

export function setButtonsLoading(buttons: ArrayLike<HTMLButtonElement>, isLoading: boolean): void {
	Array.from(buttons).forEach((button) => {
		setControlLoading(button, isLoading);
	});
}

export async function withButtonsLoading<T>(
	buttons: ArrayLike<HTMLButtonElement>,
	action: () => Promise<T>,
): Promise<T> {
	const list = Array.from(buttons);
	list.forEach((button) => setControlLoading(button, true));

	try {
		return await action();
	} finally {
		list.forEach((button) => setControlLoading(button, false));
	}
}
