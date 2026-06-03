import { notify } from '../cart/notifications';
import { resolveSubmitButton, withControlLoading } from '../ui/button-loading';
import type { AccountAuthLoginConfig } from './types';

type AjaxLoginSuccess = {
	success: true;
	data: {
		redirect: string;
	};
};

type AjaxLoginError = {
	success: false;
	data:
		| string
		| string[]
		| {
				message?: string;
		  };
};

function getErrorMessage(payload: AjaxLoginError, fallback: string): string {
	const { data } = payload;

	if (typeof data === 'string' && '' !== data) {
		return data;
	}

	if (Array.isArray(data) && data.length > 0) {
		return String(data[0]);
	}

	if (data && typeof data === 'object' && 'message' in data && typeof data.message === 'string') {
		return data.message;
	}

	return fallback;
}

function getConfig(): AccountAuthLoginConfig | null {
	const config = window.tailwindscoreAccountAuth;

	if (!config?.ajaxUrl || !config.ajaxAction || !config.redirectUrl || !config.i18n) {
		return null;
	}

	return {
		ajaxUrl: config.ajaxUrl,
		ajaxAction: config.ajaxAction,
		redirectUrl: config.redirectUrl,
		i18n: config.i18n,
	};
}

function isAjaxLoginForm(form: HTMLFormElement): boolean {
	if (form.hasAttribute('data-ts-auth-login-ajax')) {
		return true;
	}

	return form.closest('.ts-login-modal') !== null;
}

function readField(form: HTMLFormElement, selector: string): string {
	return form.querySelector<HTMLInputElement>(selector)?.value ?? '';
}

async function submitLogin(form: HTMLFormElement): Promise<void> {
	const config = getConfig();

	if (!config) {
		return;
	}

	const username = readField(form, '[name="username"]').trim();
	const password = readField(form, '[name="password"]');
	const rememberme = form.querySelector<HTMLInputElement>('[name="rememberme"]')?.checked ?? false;
	const nonce = readField(form, '[name="woocommerce-login-nonce"]');
	const redirect = readField(form, '[name="redirect"]').trim() || config.redirectUrl;

	if ('' === username) {
		notify({ type: 'error', message: config.i18n.usernameRequired, surface: 'toast' });
		return;
	}

	if ('' === password) {
		notify({ type: 'error', message: config.i18n.passwordRequired, surface: 'toast' });
		return;
	}

	const button = resolveSubmitButton(form);
	const body = new FormData();

	body.set('action', config.ajaxAction);
	body.set('username', username);
	body.set('password', password);
	body.set('redirect', redirect);
	body.set('woocommerce-login-nonce', nonce);

	if (rememberme) {
		body.set('rememberme', 'forever');
	}

	await withControlLoading(button, async () => {
		let response: Response;

		try {
			response = await fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body,
			});
		} catch {
			notify({ type: 'error', message: config.i18n.networkError, surface: 'toast' });
			return;
		}

		let payload: AjaxLoginSuccess | AjaxLoginError;

		try {
			payload = (await response.json()) as AjaxLoginSuccess | AjaxLoginError;
		} catch {
			notify({ type: 'error', message: config.i18n.networkError, surface: 'toast' });
			return;
		}

		if (!payload.success) {
			notify({
				type: 'error',
				message: getErrorMessage(payload, config.i18n.loginFailed),
				surface: 'toast',
			});
			return;
		}

		window.location.assign(payload.data.redirect || config.redirectUrl);
	});
}

export function initAjaxAccountLogin(): void {
	if (!getConfig()) {
		return;
	}

	document.addEventListener(
		'submit',
		(event) => {
			if (!(event.target instanceof HTMLFormElement) || !event.target.classList.contains('woocommerce-form-login')) {
				return;
			}

			if (!isAjaxLoginForm(event.target)) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			void submitLogin(event.target);
		},
		true,
	);
}

initAjaxAccountLogin();
