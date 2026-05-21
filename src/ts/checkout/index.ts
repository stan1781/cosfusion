import { clearFeedback } from '../cart/notifications';
import { setControlLoading } from '../ui/button-loading';

const DESKTOP_SUMMARY_MQL = window.matchMedia('(width >= 64rem)');
const CHECKOUT_SUMMARY_OPEN_KEY = 'ts-checkout-summary-open';

function isDesktopSummary(): boolean {
	return DESKTOP_SUMMARY_MQL.matches;
}

function isPrimarySummary(details: HTMLDetailsElement): boolean {
	return details.hasAttribute('data-ts-checkout-order-summary-primary');
}

function syncCheckoutDetailsState(): void {
	const desktop = isDesktopSummary();
	const storedOpen = sessionStorage.getItem(CHECKOUT_SUMMARY_OPEN_KEY) === '1';

	document.querySelectorAll<HTMLDetailsElement>('[data-ts-checkout-order-summary]').forEach((details) => {
		if (desktop) {
			details.open = true;
			return;
		}

		if (isPrimarySummary(details)) {
			details.open = storedOpen;
		}
	});
}

function initCheckoutDetails(): void {
	document.addEventListener('toggle', (event) => {
		const details = event.target;

		if (!(details instanceof HTMLDetailsElement) || !details.matches('[data-ts-checkout-order-summary]')) {
			return;
		}

		if (isDesktopSummary() || !isPrimarySummary(details)) {
			return;
		}

		sessionStorage.setItem(CHECKOUT_SUMMARY_OPEN_KEY, details.open ? '1' : '0');
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

let checkoutLoginReturnFocus: HTMLElement | null = null;

function getCheckoutLoginModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-checkout-login-modal]');
}

function isCheckoutLoginOpen(): boolean {
	const modal = getCheckoutLoginModal();
	return modal !== null && !modal.hasAttribute('hidden');
}

function openCheckoutLoginModal(trigger?: HTMLElement): void {
	const modal = getCheckoutLoginModal();

	if (!modal || isCheckoutLoginOpen()) {
		return;
	}

	checkoutLoginReturnFocus =
		trigger ??
		(document.activeElement instanceof HTMLElement ? document.activeElement : null);

	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('ts-checkout-login-open');

	window.requestAnimationFrame(() => {
		const focusTarget =
			modal.querySelector<HTMLElement>('#username, input:not([type="hidden"])') ??
			modal.querySelector<HTMLElement>('button, a, input, select, textarea');

		focusTarget?.focus();
	});
}

function closeCheckoutLoginModal(): void {
	const modal = getCheckoutLoginModal();

	if (!modal || modal.hasAttribute('hidden')) {
		return;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('ts-checkout-login-open');

	if (checkoutLoginReturnFocus?.isConnected) {
		checkoutLoginReturnFocus.focus();
	}

	checkoutLoginReturnFocus = null;
}

function initCheckoutLoginModal(): void {
	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const openTrigger = target.closest<HTMLElement>('[data-ts-checkout-login-open]');

		if (openTrigger) {
			event.preventDefault();
			openCheckoutLoginModal(openTrigger);
			return;
		}

		if (target.closest('[data-ts-checkout-login-close]')) {
			event.preventDefault();
			closeCheckoutLoginModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isCheckoutLoginOpen()) {
			event.preventDefault();
			closeCheckoutLoginModal();
		}
	});
}

function initCheckoutUi(): void {
	initCheckoutDetails();
	initCheckoutLoginModal();

	const checkoutForm = document.querySelector<HTMLFormElement>('form.checkout');
	const placeOrderButton = document.querySelector<HTMLButtonElement>('#place_order');

	const releasePlaceOrderLoading = () => {
		setControlLoading(placeOrderButton, false);
	};

	bindCheckoutFragments(releasePlaceOrderLoading);

	checkoutForm?.addEventListener('submit', () => {
		clearFeedback('page');
		setControlLoading(placeOrderButton, true);
	});

	document.addEventListener('checkout_error', () => {
		releasePlaceOrderLoading();

		const feedback = document.querySelector<HTMLElement>(
			'[data-ts-cart-feedback][data-ts-cart-feedback-surface="page"]',
		);

		if (!feedback) {
			return;
		}

		const notice = document.querySelector<HTMLElement>(
			'.woocommerce-NoticeGroup-checkout, .woocommerce-error',
		);

		if (!notice?.textContent?.trim()) {
			return;
		}

		feedback.hidden = false;
		feedback.innerHTML = `<p class="ts-cart-feedback__message ts-cart-feedback__message--error">${notice.textContent.trim()}</p>`;
		feedback.dataset.tsCartFeedbackType = 'error';
	});

	DESKTOP_SUMMARY_MQL.addEventListener('change', () => {
		syncCheckoutDetailsState();
	});
}

if (document.body.classList.contains('woocommerce-checkout')) {
	document.documentElement.classList.add('has-checkout-ui');
	initCheckoutUi();
}
