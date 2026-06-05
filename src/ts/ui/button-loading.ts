export type LoadableControl = HTMLButtonElement | HTMLAnchorElement;

export type ControlLoadingOptions = {
	/** When false, only show spinner — do not disable (keeps submit name in POST). */
	disable?: boolean;
};

/**
 * Toggle AJAX loading state on buttons and button-like links.
 * Uses .is-loading (+ legacy .loading) with a CSS spinner (::after).
 */
export function setControlLoading(
	control: LoadableControl | null | undefined,
	isLoading: boolean,
	options: ControlLoadingOptions = {},
): void {
	if (!control) {
		return;
	}

	const shouldDisable = options.disable ?? true;

	control.classList.toggle('is-loading', isLoading);
	control.classList.toggle('loading', isLoading);
	control.setAttribute('aria-busy', isLoading ? 'true' : 'false');

	if (control instanceof HTMLButtonElement) {
		if (shouldDisable) {
			control.disabled = isLoading;
		}

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

function isModifiedNavigationClick(event: MouseEvent): boolean {
	return event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
}

/**
 * Show loading on navigation links (e.g. drawer checkout) until the next page loads.
 */
export function initLoadingNavLinks(): void {
	document.addEventListener(
		'click',
		(event) => {
			if (!(event instanceof MouseEvent) || isModifiedNavigationClick(event)) {
				return;
			}

			const target = event.target;

			if (!(target instanceof Element)) {
				return;
			}

			const link = target.closest<HTMLAnchorElement>('a[data-ts-button-loading][href]');

			if (!link) {
				return;
			}

			if (link.classList.contains('is-loading')) {
				event.preventDefault();
				return;
			}

			setControlLoading(link, true);
		},
		true,
	);

	window.addEventListener('pageshow', (event) => {
		if (!event.persisted) {
			return;
		}

		document
			.querySelectorAll<HTMLAnchorElement>('a[data-ts-button-loading].is-loading')
			.forEach((link) => setControlLoading(link, false));
	});
}

initLoadingNavLinks();

function formHasLoadingSubmit(form: HTMLFormElement): boolean {
	if (
		form.classList.contains('woocommerce-form-login') ||
		form.classList.contains('woocommerce-form-register') ||
		form.classList.contains('checkout')
	) {
		return false;
	}

	// AJAX-handled forms manage their own loading state (see integrations/mailnest.ts, cart, etc.).
	if (
		form.classList.contains('wc-subscribe-form') ||
		form.matches('[data-ts-mailnest-form], .mailnest-form')
	) {
		return false;
	}

	return (
		form.classList.contains('ts-account-auth__form') ||
		form.querySelector('[data-ts-button-loading]') !== null
	);
}

/**
 * Show loading on classic form POST submits until navigation completes.
 */
export function initLoadingFormSubmits(): void {
	document.addEventListener(
		'submit',
		(event) => {
			if (!(event.target instanceof HTMLFormElement) || !formHasLoadingSubmit(event.target)) {
				return;
			}

			setControlLoading(resolveSubmitButton(event.target, event.submitter), true, { disable: false });
		},
		true,
	);

	window.addEventListener('pageshow', (event) => {
		if (!event.persisted) {
			return;
		}

		document
			.querySelectorAll<HTMLButtonElement>(
				'.ts-account-auth__form [type="submit"].is-loading, [data-ts-button-loading].is-loading',
			)
			.forEach((button) => setControlLoading(button, false));
	});
}

initLoadingFormSubmits();
