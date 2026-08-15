import { TS_MEDIA_DOWN } from '../breakpoints';
import { escapeHtml } from '../utils/escape-html';

export type NotificationSurface = 'toast' | 'inline' | 'drawer' | 'page';

export type NotificationType = 'success' | 'info' | 'warning' | 'error';

export type PurchaseFlowNotification = {
	type: NotificationType;
	message: string;
	surface: NotificationSurface;
	context?: string;
};

type ToastTimer = {
	id: number;
};

const defaultSelectors: Record<NotificationSurface, string> = {
	toast: '[data-ts-purchase-flow-toast-root]',
	drawer: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="drawer"]',
	inline: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="inline"]',
	page: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="page"]',
};

const toastDismissMs = 4200;
const toastLeaveMs = 220;
const toastDragThresholdPx = 48;
const toastDragThresholdRatio = 0.35;

const toastTitles: Record<NotificationType, string> = {
	success: 'Success',
	info: 'Notice',
	warning: 'Warning',
	error: 'Error',
};

function getSelectors(): Record<NotificationSurface, string> {
	const configSelectors = window.tailwindscoreCart?.notifications?.selectors;

	if (!configSelectors) {
		return defaultSelectors;
	}

	return {
		toast: configSelectors.toast || defaultSelectors.toast,
		drawer: configSelectors.drawer || defaultSelectors.drawer,
		inline: configSelectors.inline || defaultSelectors.inline,
		page: configSelectors.page || defaultSelectors.page,
	};
}

function resolveTarget(surface: NotificationSurface, context?: string) {
	const selectors = getSelectors();
	const selector = selectors[surface];
	const nodes = document.querySelectorAll<HTMLElement>(selector);

	if (nodes.length < 1) {
		return null;
	}

	if (!context) {
		return nodes[0];
	}

	return Array.from(nodes).find((node) => node.dataset.tsCartFeedbackContext === context) ?? nodes[0];
}

function renderInlineMessage(notification: PurchaseFlowNotification) {
	return `<p class="cart-feedback__message cart-feedback__message--${notification.type}">${escapeHtml(notification.message)}</p>`;
}

/** Decode entities from WooCommerce / Store API messages before re-escaping for display. */
export function decodeHtmlEntities(value: string): string {
	if (!value || !value.includes('&')) {
		return value;
	}

	const textarea = document.createElement('textarea');
	textarea.innerHTML = value;
	return textarea.value;
}

function isMobileToastLayout(): boolean {
	return window.matchMedia(TS_MEDIA_DOWN.md).matches;
}

function dismissToast(toast: HTMLElement, timeoutId: number) {
	window.clearTimeout(timeoutId);

	if (!toast.isConnected) {
		return;
	}

	toast.classList.remove('is-dragging');
	toast.style.transform = '';
	toast.classList.add('is-leaving');
	window.setTimeout(() => {
		toast.remove();
	}, toastLeaveMs);
}

function scheduleDismiss(toast: HTMLElement, timer: ToastTimer): void {
	window.clearTimeout(timer.id);
	timer.id = window.setTimeout(() => {
		dismissToast(toast, timer.id);
	}, toastDismissMs);
}

function bindMobileSwipeDismiss(toast: HTMLElement, timer: ToastTimer): void {
	if (!isMobileToastLayout()) {
		return;
	}

	const dragState = {
		pointerId: null as number | null,
		startY: 0,
		dragY: 0,
		moved: false,
	};

	const onPointerMove = (event: PointerEvent): void => {
		if (dragState.pointerId === null || event.pointerId !== dragState.pointerId) {
			return;
		}

		// 仅向上（负 dy）跟手。
		const dy = Math.min(0, event.clientY - dragState.startY);

		if (!dragState.moved && dy < -6) {
			dragState.moved = true;
			toast.classList.add('is-dragging');
			window.clearTimeout(timer.id);
		}

		if (!dragState.moved) {
			return;
		}

		dragState.dragY = dy;
		toast.style.transform = `translateY(${dy}px)`;
		event.preventDefault();
	};

	const teardown = (): void => {
		window.removeEventListener('pointermove', onPointerMove);
		window.removeEventListener('pointerup', onPointerUp);
		window.removeEventListener('pointercancel', onPointerUp);
	};

	const onPointerUp = (event: PointerEvent): void => {
		if (dragState.pointerId === null || event.pointerId !== dragState.pointerId) {
			return;
		}

		const dragY = dragState.dragY;
		const wasDragging = dragState.moved;

		teardown();

		try {
			if (typeof toast.releasePointerCapture === 'function') {
				toast.releasePointerCapture(event.pointerId);
			}
		} catch {
			// 指针可能已释放。
		}

		dragState.pointerId = null;
		dragState.startY = 0;
		dragState.dragY = 0;
		dragState.moved = false;

		if (!wasDragging) {
			toast.classList.remove('is-dragging');
			toast.style.transform = '';
			return;
		}

		const height = toast.getBoundingClientRect().height || 80;
		const threshold = Math.max(toastDragThresholdPx, height * toastDragThresholdRatio);

		if (Math.abs(dragY) >= threshold) {
			dismissToast(toast, timer.id);
			return;
		}

		toast.classList.remove('is-dragging');
		toast.style.transform = '';
		scheduleDismiss(toast, timer);
	};

	toast.addEventListener('pointerdown', (event: PointerEvent) => {
		if (toast.classList.contains('is-leaving')) {
			return;
		}

		if (event.pointerType === 'mouse' && event.button !== 0) {
			return;
		}

		const target = event.target;

		if (target instanceof Element && target.closest('[data-ts-close]')) {
			return;
		}

		dragState.pointerId = event.pointerId;
		dragState.startY = event.clientY;
		dragState.dragY = 0;
		dragState.moved = false;

		try {
			toast.setPointerCapture(event.pointerId);
		} catch {
			// 忽略不支持 pointer capture 的浏览器。
		}

		window.addEventListener('pointermove', onPointerMove, { passive: false });
		window.addEventListener('pointerup', onPointerUp, true);
		window.addEventListener('pointercancel', onPointerUp, true);
	});
}

function showToast(notification: PurchaseFlowNotification) {
	const root = resolveTarget('toast');

	if (!root) {
		return;
	}

	const toast = document.createElement('div');
	toast.className = `purchase-flow-toast purchase-flow-toast--${notification.type} is-entering`;
	toast.setAttribute('role', 'alert');
	toast.setAttribute('aria-live', 'assertive');
	toast.setAttribute('aria-atomic', 'true');
	toast.style.setProperty('--ts-toast-duration', `${toastDismissMs}ms`);
	toast.innerHTML = `
		<div class="purchase-flow-toast__header">
			<span class="purchase-flow-toast__accent" aria-hidden="true"></span>
			<strong class="purchase-flow-toast__title">${escapeHtml(toastTitles[notification.type])}</strong>
			<button type="button" class="btn-close btn-close-sm" data-ts-close aria-label="Close"><span class="icon" aria-hidden="true">${window.tailwindscoreCart?.icons?.close ?? ''}</span><span class="screen-reader-text">Close</span></button>
		</div>
		<div class="purchase-flow-toast__body">${escapeHtml(notification.message)}</div>
		<div class="purchase-flow-toast__progress" aria-hidden="true">
			<span class="purchase-flow-toast__progress-bar"></span>
		</div>
		<div class="purchase-flow-toast__handle" data-ts-toast-handle aria-hidden="true"></div>
	`;

	const timer: ToastTimer = { id: 0 };

	toast.querySelector<HTMLButtonElement>('[data-ts-close]')?.addEventListener('click', () => {
		dismissToast(toast, timer.id);
	});

	bindMobileSwipeDismiss(toast, timer);

	root.appendChild(toast);

	// 下一帧去掉 is-entering，触发从上滑入。
	window.requestAnimationFrame(() => {
		window.requestAnimationFrame(() => {
			toast.classList.remove('is-entering');
		});
	});

	scheduleDismiss(toast, timer);
}

function showPersistentFeedback(notification: PurchaseFlowNotification) {
	const target = resolveTarget(notification.surface === 'inline' ? 'inline' : notification.surface, notification.context);

	if (!target) {
		if ('toast' !== notification.surface) {
			showToast({ ...notification, surface: 'toast' });
		}
		return;
	}

	target.hidden = false;
	target.innerHTML = renderInlineMessage(notification);
	target.dataset.tsCartFeedbackType = notification.type;
}

export function clearFeedback(surface: NotificationSurface, context?: string) {
	const target = resolveTarget(surface, context);

	if (!target) {
		return;
	}

	target.hidden = true;
	target.innerHTML = '';
	delete target.dataset.tsCartFeedbackType;
}

export function notify(notification: PurchaseFlowNotification) {
	const normalized: PurchaseFlowNotification = {
		...notification,
		message: decodeHtmlEntities(notification.message),
	};

	// Drawer 短时反馈走 toast；持久 inline 用于购物车 / 结账页。
	if ('toast' === normalized.surface || 'drawer' === normalized.surface) {
		showToast(normalized);
		return;
	}

	showPersistentFeedback(normalized);
}
