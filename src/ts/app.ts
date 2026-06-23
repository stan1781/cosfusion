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

function scheduleIdleTask(task: () => void, timeoutMs = 4000): void {
	if ('requestIdleCallback' in window) {
		window.requestIdleCallback(task, { timeout: timeoutMs });
		return;
	}

	globalThis.setTimeout(task, Math.min(timeoutMs, 2500));
}

function loadQuickViewModule(): void {
	void import('./shop/quick-view');
}

function scheduleQuickViewModule(): void {
	if (!document.body.classList.contains('ts-quick-view-enabled')) {
		return;
	}

	let loaded = false;
	const load = (): void => {
		if (loaded) {
			return;
		}
		loaded = true;
		loadQuickViewModule();
	};

	const onEarlyIntent = (event: Event): void => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		if (target.closest('[data-ts-quick-view], [data-product-card]')) {
			load();
		}
	};

	document.addEventListener('pointerover', onEarlyIntent, true);
	document.addEventListener('focusin', onEarlyIntent, true);
	scheduleIdleTask(load);
}

function scheduleMailnestModule(): void {
	if (!document.body.classList.contains('ts-mailnest')) {
		return;
	}

	scheduleIdleTask(() => {
		void import('./integrations/mailnest');
	});
}

function bootHomeModule(): void {
	if (!document.querySelector('.ts-home')) {
		return;
	}

	void import('./home/index');
}

scheduleQuickViewModule();
scheduleMailnestModule();
bootHomeModule();

if (document.body.classList.contains('woocommerce-account')) {
	void import('./integrations/order-process');
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
