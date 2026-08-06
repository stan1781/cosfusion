import { getWishlistConfig } from './api';
import { getCachedServerKeys } from './merge';

const BADGE_SELECTOR = '[data-ts-wishlist-menu-badge]';

/**
 * 同步账户菜单心愿单数量徽章（仅登录用户渲染该节点）。
 */
export function syncWishlistMenuBadges(count?: number): void {
	const config = getWishlistConfig();
	if (!config?.isLoggedIn) {
		return;
	}

	let next = count;
	if (typeof next !== 'number') {
		const keys = getCachedServerKeys();
		if (!keys) {
			return;
		}
		next = keys.size;
	}

	if (next < 0) {
		next = 0;
	}

	document.querySelectorAll<HTMLElement>(BADGE_SELECTOR).forEach((badge) => {
		badge.textContent = String(next);
		if (next > 0) {
			badge.removeAttribute('hidden');
		} else {
			badge.setAttribute('hidden', '');
		}
	});
}
