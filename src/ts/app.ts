import './account-auth/index';
import './ui/login-modal';
import './ui/quantity-input';
import './ui/button-loading';
import './site/index';
import './site/header-sticky';
import './site/mobile-nav';
import './site/mobile-tab-bar';
import './shop/loop-swatches';
import './shop/loop-add-to-cart';
import './search/index';
import './product/index';
import './cart/index';
import './cart/item-edit-modal';
import './cart/cart-page';
import './checkout/index';

if (document.body.classList.contains('ts-quick-view-enabled')) {
	void import('./shop/quick-view');
}

if (document.body.classList.contains('woocommerce-account')) {
	void import('./integrations/order-process');
}

if (document.body.classList.contains('ts-mailnest')) {
	void import('./integrations/mailnest');
}

if (document.querySelector('.ts-home')) {
	void import('./home/index');
}

import Alpine from 'alpinejs';

declare global {
	interface Window {
		Alpine: typeof Alpine & {
			$data?: (element: Element) => unknown;
		};
	}
}

window.Alpine = Alpine;
Alpine.start();
