import { notify } from '../cart/notifications';
import './login';
import './register';

function stripHtml(html: string): string {
	const doc = new DOMParser().parseFromString(html, 'text/html');

	return doc.body.textContent?.trim() ?? '';
}

function promoteNoticeElements(elements: NodeListOf<Element>, type: 'error' | 'success' | 'info'): void {
	elements.forEach((element) => {
		const message = stripHtml(element.innerHTML);

		if ('' !== message) {
			notify({ type, message, surface: 'toast' });
		}
	});
}

function promoteAuthNotices(): void {
	if (!document.body.classList.contains('account-auth-surface')) {
		return;
	}

	document.querySelectorAll<HTMLElement>('[data-ts-auth-notices]').forEach((wrapper) => {
		promoteNoticeElements(wrapper.querySelectorAll('.woocommerce-error li'), 'error');
		promoteNoticeElements(wrapper.querySelectorAll('.woocommerce-message'), 'success');
		promoteNoticeElements(wrapper.querySelectorAll('.woocommerce-info'), 'info');

		wrapper.remove();
	});
}

function cleanRegistrationQueryParam(): void {
	const url = new URL(window.location.href);

	if (!url.searchParams.has('registration')) {
		return;
	}

	url.searchParams.delete('registration');
	window.history.replaceState({}, '', url);
}

function showRegistrationSuccessFromUrl(): void {
	const url = new URL(window.location.href);

	if ('success' !== url.searchParams.get('registration')) {
		return;
	}

	const message =
		window.tailwindscoreAccountAuth?.i18n?.registrationSuccess ??
		'Registration successful. You can now sign in.';

	notify({ type: 'success', message, surface: 'toast' });
	cleanRegistrationQueryParam();
}

function initAuthNotices(): void {
	const hadServerNotices = document.querySelector('[data-ts-auth-notices]') !== null;

	promoteAuthNotices();

	if (hadServerNotices) {
		cleanRegistrationQueryParam();
		return;
	}

	showRegistrationSuccessFromUrl();
}

if ('loading' === document.readyState) {
	document.addEventListener('DOMContentLoaded', initAuthNotices);
} else {
	initAuthNotices();
}
