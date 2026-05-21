import { clearFeedback } from '../cart/notifications';

if (document.body.classList.contains('woocommerce-checkout')) {
	document.documentElement.classList.add('has-checkout-ui');

	const checkoutForm = document.querySelector<HTMLFormElement>('form.checkout');

	checkoutForm?.addEventListener('submit', () => {
		clearFeedback('page');
	});

	document.addEventListener('checkout_error', () => {
		const feedback = document.querySelector<HTMLElement>(
			'[data-ts-cart-feedback][data-ts-cart-feedback-surface="page"]',
		);

		if (!feedback) {
			return;
		}

		const notice = document.querySelector<HTMLElement>('.woocommerce-NoticeGroup-checkout, .woocommerce-error');

		if (!notice?.textContent?.trim()) {
			return;
		}

		feedback.hidden = false;
		feedback.innerHTML = `<p class="ts-cart-feedback__message ts-cart-feedback__message--error">${notice.textContent.trim()}</p>`;
		feedback.dataset.tsCartFeedbackType = 'error';
	});
}
