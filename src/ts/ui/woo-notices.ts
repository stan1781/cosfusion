/**
 * 将 WooCommerce 成功/信息 notice 提升为购买流 toast。
 * 错误与结账校验组仍留在页内 banner。
 */

import { notify } from '../cart/notifications';

const TOAST_DEDUP_MS = 3000;
const COUPON_APPLIED_NOTICE = /coupon code applied|discount code applied/i;
const GIFT_ADDED_NOTICE = /freebie|giveaway|goodie|free gift/i;
const COMBINED_COUPON_GIFT_TOAST = 'Discount applied and free gift added to your cart';

/** Session / AJAX 成功确认：顶栏 banner 改 toast；错误仍留在通知容器。 */
const SUCCESS_NOTICE_CONTAINERS =
	'.woocommerce-notices-wrapper, .woocommerce-NoticeGroup-updateOrderReview';

const recentlyToasted = new Map<string, number>();

function stripHtml(html: string): string {
	const doc = new DOMParser().parseFromString(html, 'text/html');
	return doc.body.textContent?.trim() ?? '';
}

function shouldToastMessage(message: string): boolean {
	const now = Date.now();
	const last = recentlyToasted.get(message);

	if (undefined !== last && now - last < TOAST_DEDUP_MS) {
		return false;
	}

	recentlyToasted.set(message, now);
	return true;
}

function resolveToastMessage(messages: string[]): string | null {
	if (messages.length < 1) {
		return null;
	}

	const hasCoupon = messages.some((message) => COUPON_APPLIED_NOTICE.test(message));
	const hasGift = messages.some((message) => GIFT_ADDED_NOTICE.test(message));

	if (hasCoupon && hasGift) {
		return COMBINED_COUPON_GIFT_TOAST;
	}

	return messages.join(' ');
}

/**
 * 将容器内 `.woocommerce-message` / `.woocommerce-info` 转为 toast 后移除。
 * 不处理 `.woocommerce-error`、不处理结账提交错误组 `.woocommerce-NoticeGroup-checkout`。
 */
export function promoteWooSuccessNotices(root: ParentNode = document): void {
	root.querySelectorAll(SUCCESS_NOTICE_CONTAINERS).forEach((container) => {
		if (container.closest('.woocommerce-NoticeGroup-checkout')) {
			return;
		}

		const ephemeral = Array.from(
			container.querySelectorAll(':scope > .woocommerce-message, :scope > .woocommerce-info'),
		);
		const messages: string[] = [];
		let toastType: 'success' | 'info' = 'success';

		ephemeral.forEach((node) => {
			const message = stripHtml((node as HTMLElement).innerHTML);

			if ('' !== message) {
				messages.push(message);

				if (node.classList.contains('woocommerce-info')) {
					toastType = 'info';
				}
			}

			node.remove();
		});

		const toastMessage = resolveToastMessage(messages);

		if (toastMessage && shouldToastMessage(toastMessage)) {
			notify({
				type: toastType,
				message: toastMessage,
				surface: 'toast',
			});
		}

		if (
			container.classList.contains('woocommerce-NoticeGroup-updateOrderReview') &&
			!container.querySelector('.woocommerce-error, .woocommerce-message, .woocommerce-info, li')
		) {
			container.remove();
		}
	});
}

export function initWooSuccessNotices(): void {
	promoteWooSuccessNotices();

	const jq = (
		window as Window & {
			jQuery?: (target: Document | HTMLElement) => {
				on: (events: string, handler: () => void) => void;
			};
		}
	).jQuery;

	jq?.(document.body).on('updated_checkout updated_wc_div updated_cart_totals', () => {
		window.requestAnimationFrame(() => {
			promoteWooSuccessNotices();
		});
	});
}
