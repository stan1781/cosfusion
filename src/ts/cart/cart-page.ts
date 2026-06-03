import { refreshCart } from './index';
import { notify } from './notifications';

function initCartPageUi(): void {
	const root = document.querySelector<HTMLElement>('[data-ts-cart-page]');

	if (!root) {
		return;
	}

	document.documentElement.classList.add('has-cart-ui');
	root.setAttribute('aria-busy', 'true');

	void refreshCart()
		.catch((error) => {
			const message = error instanceof Error ? error.message : 'Failed to load cart.';

			notify({
				type: 'error',
				message,
				surface: 'page',
			});
		})
		.finally(() => {
			root.removeAttribute('aria-busy');
		});
}

if (document.body.classList.contains('woocommerce-cart')) {
	initCartPageUi();
}
