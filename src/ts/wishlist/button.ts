import { notify } from '../cart/index';
import { fetchWishlist, getWishlistConfig, toggleWishlistItem } from './api';
import { getCachedServerKeys, mergeGuestWishlistIfNeeded, setCachedServerKeysFromItems } from './merge';
import { syncWishlistMenuBadges } from './menu-badge';
import { guestHasItem, toggleGuestItem } from './storage';
import { itemKey } from './types';

const BUTTON_SELECTOR = '[data-ts-wishlist]';

function readProductId(button: HTMLElement): number {
	return Math.max(0, Number(button.getAttribute('data-product-id') ?? '0'));
}

function setButtonState(button: HTMLElement, active: boolean): void {
	const config = getWishlistConfig();
	button.classList.toggle('is-active', active);
	button.setAttribute('aria-pressed', active ? 'true' : 'false');
	button.setAttribute(
		'aria-label',
		active ? (config?.i18n.remove ?? 'Remove from wishlist') : (config?.i18n.add ?? 'Add to wishlist'),
	);
}

function isActive(productId: number): boolean {
	const config = getWishlistConfig();
	const key = itemKey(productId);

	if (config?.isLoggedIn) {
		const keys = getCachedServerKeys();
		return keys ? keys.has(key) : false;
	}

	return guestHasItem(productId);
}

export function hydrateWishlistButtons(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>(BUTTON_SELECTOR).forEach((button) => {
		const productId = readProductId(button);
		if (productId < 1) {
			return;
		}
		setButtonState(button, isActive(productId));
	});
}

async function loadServerWishlistAndHydrate(): Promise<void> {
	const config = getWishlistConfig();
	if (!config?.isLoggedIn) {
		hydrateWishlistButtons();
		return;
	}

	try {
		await mergeGuestWishlistIfNeeded();
		const response = await fetchWishlist();
		setCachedServerKeysFromItems(response.items);
	} catch (error) {
		console.error(error);
	}

	hydrateWishlistButtons();
	syncWishlistMenuBadges();
}

async function handleToggle(button: HTMLElement): Promise<void> {
	const config = getWishlistConfig();
	if (!config) {
		return;
	}

	const productId = readProductId(button);
	if (productId < 1) {
		return;
	}

	button.setAttribute('disabled', 'disabled');

	try {
		if (config.isLoggedIn) {
			const response = await toggleWishlistItem(productId);
			setCachedServerKeysFromItems(response.items);
			const active = isActive(productId);
			setButtonState(button, active);
			syncWishlistMenuBadges(response.count ?? response.items.length);
			notify({
				type: 'success',
				message: active ? config.i18n.added : config.i18n.removed,
				surface: 'toast',
			});
			return;
		}

		const result = toggleGuestItem(productId, config.maxItems);
		if (result.error === 'limit') {
			notify({ type: 'error', message: config.i18n.limit, surface: 'toast' });
			return;
		}

		setButtonState(button, result.added);
		syncWishlistMenuBadges(result.items.length);
		notify({
			type: 'success',
			message: result.added ? config.i18n.added : config.i18n.removed,
			surface: 'toast',
		});
	} catch (error) {
		notify({
			type: 'error',
			message: error instanceof Error && error.message ? error.message : config.i18n.error,
			surface: 'toast',
		});
	} finally {
		button.removeAttribute('disabled');
	}
}

export function initWishlistButtons(): void {
	if (!getWishlistConfig()) {
		return;
	}

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		const button = target.closest<HTMLElement>(BUTTON_SELECTOR);
		if (!button) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		void handleToggle(button);
	});

	document.addEventListener('tailwindscore:archive-updated', () => {
		hydrateWishlistButtons();
	});

	void loadServerWishlistAndHydrate();
}
