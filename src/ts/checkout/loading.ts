import { setLoadingHost, setLoadingHosts } from '../ui/loading-host';

const CHECKOUT_REGION_LOADING_SELECTOR =
	'.ts-wc-checkout__summary, #payment.woocommerce-checkout-payment';

function getCheckoutLoadingHosts(): HTMLElement[] {
	return Array.from(document.querySelectorAll<HTMLElement>(CHECKOUT_REGION_LOADING_SELECTOR));
}

export function setCheckoutRegionsLoading(isLoading: boolean): void {
	setLoadingHosts(getCheckoutLoadingHosts(), isLoading);
}

export function setCheckoutPageLoading(isLoading: boolean): void {
	document.body.classList.toggle('ts-checkout-processing', isLoading);

	if (isLoading) {
		document.body.setAttribute('aria-busy', 'true');
		return;
	}

	if (!document.querySelector('.ts-loading-host.is-loading')) {
		document.body.removeAttribute('aria-busy');
	}
}

function prepareCheckoutLoadingHosts(): void {
	getCheckoutLoadingHosts().forEach((host) => setLoadingHost(host, false));
}

export function initCheckoutLoading(): void {
	prepareCheckoutLoadingHosts();

	const jQuery = (window as Window & {
		jQuery?: (target: Document | Element) => {
			on: (event: string, handler: () => void) => void;
		};
	}).jQuery;

	jQuery?.(document.body).on('update_checkout', () => {
		setCheckoutRegionsLoading(true);
	});

	const endRegionLoading = () => {
		setCheckoutRegionsLoading(false);
		prepareCheckoutLoadingHosts();
	};

	jQuery?.(document.body).on('updated_checkout checkout_error', endRegionLoading);
	document.addEventListener('tailwindscore:checkout-updated', endRegionLoading);
}

/** Re-apply host markers after checkout fragments replace #payment / order review markup. */
export function refreshCheckoutLoadingHosts(): void {
	prepareCheckoutLoadingHosts();
}
