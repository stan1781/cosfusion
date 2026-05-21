export type NotificationSurface = 'toast' | 'inline' | 'drawer' | 'page';

export type NotificationType = 'success' | 'info' | 'warning' | 'error';

export type PurchaseFlowNotification = {
	type: NotificationType;
	message: string;
	surface: NotificationSurface;
	context?: string;
};

type NotificationSelectors = Partial<Record<NotificationSurface, string>>;

const defaultSelectors: Record<NotificationSurface, string> = {
	toast: '[data-ts-purchase-flow-toast-root]',
	drawer: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="drawer"]',
	inline: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="inline"]',
	page: '[data-ts-cart-feedback][data-ts-cart-feedback-surface="page"]',
};

const toastDismissMs = 4200;

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
	return `<p class="ts-cart-feedback__message ts-cart-feedback__message--${notification.type}">${escapeHtml(notification.message)}</p>`;
}

function escapeHtml(value: string) {
	return value
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function dismissToast(toast: HTMLElement, timeoutId: number) {
	window.clearTimeout(timeoutId);

	if (!toast.isConnected) {
		return;
	}

	toast.classList.add('is-leaving');
	window.setTimeout(() => {
		toast.remove();
	}, 220);
}

function showToast(notification: PurchaseFlowNotification) {
	const root = resolveTarget('toast');

	if (!root) {
		return;
	}

	const toast = document.createElement('div');
	toast.className = `ts-purchase-flow-toast ts-purchase-flow-toast--${notification.type}`;
	toast.setAttribute('role', 'alert');
	toast.setAttribute('aria-live', 'assertive');
	toast.setAttribute('aria-atomic', 'true');
	toast.innerHTML = `
		<div class="ts-purchase-flow-toast__header">
			<span class="ts-purchase-flow-toast__accent" aria-hidden="true"></span>
			<strong class="ts-purchase-flow-toast__title">${escapeHtml(toastTitles[notification.type])}</strong>
			<button type="button" class="ts-purchase-flow-toast__close" aria-label="Close">&times;</button>
		</div>
		<div class="ts-purchase-flow-toast__body">${escapeHtml(notification.message)}</div>
	`;

	const timeoutId = window.setTimeout(() => {
		dismissToast(toast, timeoutId);
	}, toastDismissMs);

	toast.querySelector<HTMLButtonElement>('.ts-purchase-flow-toast__close')?.addEventListener('click', () => {
		dismissToast(toast, timeoutId);
	});

	root.appendChild(toast);
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
	if ('toast' === notification.surface) {
		showToast(notification);
		return;
	}

	showPersistentFeedback(notification);
}
