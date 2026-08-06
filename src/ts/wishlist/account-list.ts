import { addCartItem, notify } from '../cart/index';
import { setControlLoading } from '../ui/button-loading';
import { deleteWishlistItem, getWishlistConfig } from './api';
import { setCachedServerKeysFromItems } from './merge';
import { hydrateWishlistButtons } from './button';
import { syncWishlistMenuBadges } from './menu-badge';

function updateCount(root: HTMLElement, count: number): void {
	const countEl = root.querySelector<HTMLElement>('[data-ts-wishlist-count]');
	const config = getWishlistConfig();
	if (!countEl || !config) {
		return;
	}

	countEl.textContent =
		count === 1 ? config.i18n.itemCountOne : config.i18n.itemCount.replace('%d', String(count));
}

function ensureEmptyState(root: HTMLElement): void {
	const list = root.querySelector('[data-ts-wishlist-list]');
	if (list && list.children.length === 0) {
		list.remove();
	}

	if (root.querySelector('[data-ts-wishlist-empty]')) {
		return;
	}

	const empty = document.createElement('div');
	empty.className = 'wishlist-account__empty';
	empty.setAttribute('data-ts-wishlist-empty', '');
	const message = document.createElement('p');
	message.textContent = getWishlistConfig()?.i18n.empty ?? 'Your wishlist is empty.';
	empty.appendChild(message);
	root.appendChild(empty);
}

	async function removeRow(
		row: HTMLElement,
		root: HTMLElement,
		button: HTMLButtonElement,
	): Promise<void> {
		const config = getWishlistConfig();
		if (!config) {
			return;
		}

		const productId = Math.max(0, Number(row.getAttribute('data-product-id') ?? '0'));
		if (productId < 1) {
			return;
		}

		setControlLoading(button, true);

		try {
			const response = await deleteWishlistItem(productId);
			setCachedServerKeysFromItems(response.items);
			row.remove();
			updateCount(root, response.count);
			hydrateWishlistButtons();
			syncWishlistMenuBadges(response.count);

			if (response.count < 1) {
				ensureEmptyState(root);
			}

			notify({ type: 'success', message: config.i18n.removed, surface: 'toast' });
		} catch (error) {
			notify({
				type: 'error',
				message: error instanceof Error && error.message ? error.message : config.i18n.error,
				surface: 'toast',
			});
		} finally {
			if (button.isConnected) {
				setControlLoading(button, false);
			}
		}
	}

async function addRowToCart(row: HTMLElement, button: HTMLButtonElement): Promise<void> {
	const config = getWishlistConfig();
	if (!config) {
		return;
	}

	const productId = Math.max(0, Number(row.getAttribute('data-product-id') ?? '0'));
	if (productId < 1) {
		return;
	}

	setControlLoading(button, true);

	try {
		await addCartItem({ productId, quantity: 1 });
		notify({ type: 'success', message: config.i18n.addedToCart, surface: 'toast' });
	} catch (error) {
		notify({
			type: 'error',
			message: error instanceof Error ? error.message : config.i18n.error,
			surface: 'toast',
		});
	} finally {
		setControlLoading(button, false);
	}
}

export function initWishlistAccountList(): void {
	const root = document.querySelector<HTMLElement>('[data-ts-wishlist-account]');
	if (!root || !getWishlistConfig()?.isLoggedIn) {
		return;
	}

	root.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		const row = target.closest<HTMLElement>('[data-ts-wishlist-row]');
		if (!row) {
			return;
		}

		// 可变品 Quick View 由 quick-view 模块委托处理（data-ts-quick-view）。
		if (target.closest('[data-ts-quick-view]')) {
			return;
		}

			const removeBtn = target.closest<HTMLButtonElement>('[data-ts-wishlist-remove]');
			if (removeBtn) {
				event.preventDefault();
				if (removeBtn.disabled || removeBtn.classList.contains('is-loading')) {
					return;
				}
				void removeRow(row, root, removeBtn);
				return;
			}

		const addBtn = target.closest<HTMLButtonElement>('[data-ts-wishlist-add-cart]');
		if (addBtn) {
			event.preventDefault();
			void addRowToCart(row, addBtn);
		}
	});
}
