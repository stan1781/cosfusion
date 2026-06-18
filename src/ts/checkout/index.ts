import { initCheckoutLoading, refreshCheckoutLoadingHosts, setCheckoutPageLoading } from './loading';
import { setControlLoading } from '../ui/button-loading';

import { TS_MEDIA_UP } from '../breakpoints';

const DESKTOP_SUMMARY_MQL = window.matchMedia(TS_MEDIA_UP.lg);

/** Primary order summary open state on mobile (persists across checkout AJAX updates only). */
let mobilePrimarySummaryOpen = false;

function isDesktopSummary(): boolean {
	return DESKTOP_SUMMARY_MQL.matches;
}

function isPrimarySummary(details: HTMLDetailsElement): boolean {
	return details.hasAttribute('data-ts-checkout-order-summary-primary');
}

function getPrimaryOrderSummary(): HTMLDetailsElement | null {
	return document.querySelector<HTMLDetailsElement>('[data-ts-checkout-order-summary-primary]');
}

/** If a coupon is applied, keep the primary summary open on mobile. */
function syncPrimarySummaryOpenForCoupon(): void {
	if (isDesktopSummary()) {
		return;
	}

	const details = getPrimaryOrderSummary();

	if (!details) {
		return;
	}

	const hasCoupon = details.querySelector('.cart-discount');

	if (hasCoupon) {
		mobilePrimarySummaryOpen = true;
		details.open = true;
	}
}

function syncCheckoutDetailsState(): void {
	const desktop = isDesktopSummary();

	document.querySelectorAll<HTMLDetailsElement>('[data-ts-checkout-order-summary]').forEach((details) => {
		if (desktop) {
			details.open = true;
			return;
		}

		if (isPrimarySummary(details)) {
			details.open = mobilePrimarySummaryOpen;
			return;
		}

		details.open = false;
	});

	syncPrimarySummaryOpenForCoupon();
}

function initCheckoutDetails(): void {
	// Drop legacy persisted state so mobile always starts collapsed on a fresh checkout load.
	sessionStorage.removeItem('ts-checkout-summary-open');

	document.addEventListener('toggle', (event) => {
		const details = event.target;

		if (!(details instanceof HTMLDetailsElement) || !details.matches('[data-ts-checkout-order-summary]')) {
			return;
		}

		if (isDesktopSummary() || !isPrimarySummary(details)) {
			return;
		}

		mobilePrimarySummaryOpen = details.open;
	});

	syncCheckoutDetailsState();
}

function bindCheckoutFragments(onCheckoutUpdated?: () => void): void {
	const jQuery = (window as Window & {
		jQuery?: (target: Document | Element) => { on: (event: string, handler: () => void) => void };
	}).jQuery;

	jQuery?.(document.body).on('updated_checkout', () => {
		syncCheckoutDetailsState();
		onCheckoutUpdated?.();
	});

	document.addEventListener('tailwindscore:checkout-updated', () => {
		syncCheckoutDetailsState();
	});
}

/** Clicking the payment method row (outside fields / payment_box) selects that gateway. */
function initPaymentMethodListClick(): void {
	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const li = target.closest<HTMLLIElement>('#payment ul.payment_methods > li.wc_payment_method');

		if (!li) {
			return;
		}

		if (
			target.closest('.payment_box, a, button, select, textarea, input[name="payment_method"], label')
		) {
			return;
		}

		const radio = li.querySelector<HTMLInputElement>('input.input-radio[name="payment_method"]');

		if (!radio || radio.disabled) {
			return;
		}

		radio.click();
	});
}

function getPlaceOrderButton(): HTMLButtonElement | null {
	return document.querySelector<HTMLButtonElement>('#place_order');
}

function releaseCheckoutSubmitLoading(): void {
	setControlLoading(getPlaceOrderButton(), false);
	setCheckoutPageLoading(false);
}

function beginCheckoutSubmitLoading(): void {
	setControlLoading(getPlaceOrderButton(), true, { disable: false });
	setCheckoutPageLoading(true);
}

function initCheckoutUi(): void {
	initCheckoutDetails();
	initCheckoutLoading();
	initPaymentMethodListClick();

	const checkoutForm = document.querySelector<HTMLFormElement>('form.checkout');

	bindCheckoutFragments(() => {
		if (!checkoutForm?.classList.contains('processing')) {
			releaseCheckoutSubmitLoading();
		}

		refreshCheckoutLoadingHosts();
		syncPrimarySummaryOpenForCoupon();
	});

	checkoutForm?.addEventListener('submit', () => {
		beginCheckoutSubmitLoading();

		// WooCommerce may abort submit before adding `.processing` (e.g. gateway hook).
		requestAnimationFrame(() => {
			if (!checkoutForm.classList.contains('processing')) {
				releaseCheckoutSubmitLoading();
			}
		});
	});

	const jQuery = (window as Window & {
		jQuery?: (target: Document | Element) => { on: (event: string, handler: () => void) => void };
	}).jQuery;

	// WooCommerce triggers `checkout_error` via jQuery — native listeners never receive it.
	jQuery?.(document.body).on('checkout_error', () => {
		releaseCheckoutSubmitLoading();
	});

	DESKTOP_SUMMARY_MQL.addEventListener('change', () => {
		syncCheckoutDetailsState();
	});
}

if (document.body.classList.contains('woocommerce-checkout')) {
	document.documentElement.classList.add('has-checkout-ui');
	initCheckoutUi();
}
