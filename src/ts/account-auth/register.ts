import { notify } from '../cart/notifications';
import { resolveSubmitButton, withControlLoading } from '../ui/button-loading';
import type { AccountAuthRegisterConfig } from './types';

type AjaxRegisterSuccess = {
	success: true;
	data: {
		redirect: string;
	};
};

type AjaxRegisterError = {
	success: false;
	data:
		| string
		| string[]
		| {
				message?: string;
		  };
};

function getErrorMessage(payload: AjaxRegisterError, fallback: string): string {
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

function getRegisterConfig(): AccountAuthRegisterConfig | null {
	const config = window.tailwindscoreAccountAuth;

	if (
		!config?.ajaxUrl ||
		!config.registerAjaxAction ||
		!config.registerRedirectUrl ||
		!config.registerI18n
	) {
		return null;
	}

	return {
		ajaxUrl: config.ajaxUrl,
		registerAjaxAction: config.registerAjaxAction,
		registerRedirectUrl: config.registerRedirectUrl,
		registerI18n: config.registerI18n,
	};
}

function readField(form: HTMLFormElement, selector: string): string {
	return form.querySelector<HTMLInputElement>(selector)?.value ?? '';
}

async function submitRegister(form: HTMLFormElement): Promise<void> {
	const config = getRegisterConfig();

	if (!config) {
		return;
	}

	const { registerI18n: i18n } = config;
	const username = readField(form, '[name="username"]').trim();
	const email = readField(form, '[name="email"]').trim();
	const password = readField(form, '[name="password"]');
	const passwordConfirm = readField(form, '[name="password_confirm"]');
	const nonce = readField(form, '[name="woocommerce-register-nonce"]');

	if ('' === username) {
		notify({ type: 'error', message: i18n.usernameRequired, surface: 'toast' });
		return;
	}

	if ('' === email) {
		notify({ type: 'error', message: i18n.emailRequired, surface: 'toast' });
		return;
	}

	if ('' === password) {
		notify({ type: 'error', message: i18n.passwordRequired, surface: 'toast' });
		return;
	}

	if (password !== passwordConfirm) {
		notify({ type: 'error', message: i18n.passwordMismatch, surface: 'toast' });
		return;
	}

	const button = resolveSubmitButton(form);
	const body = new FormData();

	body.set('action', config.registerAjaxAction);
	body.set('username', username);
	body.set('email', email);
	body.set('password', password);
	body.set('woocommerce-register-nonce', nonce);

	await withControlLoading(button, async () => {
		let response: Response;

		try {
			response = await fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body,
			});
		} catch {
			notify({ type: 'error', message: i18n.networkError, surface: 'toast' });
			return;
		}

		let payload: AjaxRegisterSuccess | AjaxRegisterError;

		try {
			payload = (await response.json()) as AjaxRegisterSuccess | AjaxRegisterError;
		} catch {
			notify({ type: 'error', message: i18n.networkError, surface: 'toast' });
			return;
		}

		if (!payload.success) {
			notify({
				type: 'error',
				message: getErrorMessage(payload, i18n.registerFailed),
				surface: 'toast',
			});
			return;
		}

		window.location.assign(payload.data.redirect || config.registerRedirectUrl);
	});
}

export function initAjaxAccountRegister(): void {
	if (!getRegisterConfig()) {
		return;
	}

	document.addEventListener(
		'submit',
		(event) => {
			if (!(event.target instanceof HTMLFormElement) || !event.target.classList.contains('woocommerce-form-register')) {
				return;
			}

			if (!event.target.hasAttribute('data-ts-auth-register-ajax')) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			void submitRegister(event.target);
		},
		true,
	);
}

initAjaxAccountRegister();
