import { addCartItem, notify } from '../cart/index';
import { setControlLoading } from '../ui/button-loading';

const LOOP_ADD_TO_CART_SELECTOR = '[data-product-card-actions] .ajax_add_to_cart';

function readLoopProductId(button: HTMLAnchorElement) {
	return Number(button.getAttribute('data-product_id') ?? button.dataset.productId ?? '0');
}

function bindLoopAddToCart() {
	document.addEventListener(
		'click',
		async (event) => {
			const target = event.target;

			if (!(target instanceof Element)) {
				return;
			}

			const button = target.closest<HTMLAnchorElement>(LOOP_ADD_TO_CART_SELECTOR);

			if (!button || !window.tailwindscoreCart) {
				return;
			}

			const productId = readLoopProductId(button);
			const quantity = Math.max(1, Number(button.dataset.quantity ?? '1'));

			if (productId < 1) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			event.stopImmediatePropagation();

			setControlLoading(button, true);

			try {
				await addCartItem({ productId, quantity });
				button.classList.add('added');
			} catch (error) {
				console.error(error);
				notify({
					type: 'error',
					message: error instanceof Error ? error.message : 'Unable to add this product to your cart.',
					surface: 'toast',
				});
			} finally {
				setControlLoading(button, false);
			}
		},
		true,
	);
}

bindLoopAddToCart();
