import './site/index';
import './search/index';
import './product/index';
import './cart/index';
import './checkout/index';

import Alpine from 'alpinejs';

declare global {
	interface Window {
		Alpine: typeof Alpine;
	}
}

window.Alpine = Alpine;
Alpine.start();
