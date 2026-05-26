import { notify } from '../cart/notifications';
import { setControlLoading } from '../ui/button-loading';

type SubscribeConfig = {
	ajax_url: string;
	nonce: string;
	cookie_duration: number;
	popup_delay: number;
	popup_enabled: boolean;
	messages: {
		success: string;
		error: string;
		email_required: string;
		email_invalid: string;
		confirmed: string;
		confirm_error: string;
	};
};

type FormConfig = {
	ajax_url: string;
	nonce: string;
	messages: {
		success: string;
		error: string;
		required: string;
		invalid: string;
	};
};

declare global {
	interface Window {
		wcSubscribeAjax?: SubscribeConfig;
		wcMailnestFormAjax?: FormConfig;
		mailnestFormAjax?: FormConfig;
	}
}

const DISMISS_COOKIE = 'wc_subscribe_popup_dismissed';

function getSubscribeConfig(): SubscribeConfig | null {
	return window.wcSubscribeAjax ?? null;
}

function getFormConfig(): FormConfig | null {
	return window.wcMailnestFormAjax ?? window.mailnestFormAjax ?? null;
}

function isValidEmail(email: string): boolean {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function setCookie(name: string, value: string, hours: number): void {
	const expires = new Date();
	expires.setTime(expires.getTime() + hours * 60 * 60 * 1000);
	document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

function hasDismissCookie(): boolean {
	return document.cookie.includes(`${DISMISS_COOKIE}=1`);
}

function getSubscribeModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-mailnest-subscribe-modal]');
}

function openSubscribeModal(): void {
	const modal = getSubscribeModal();
	if (!modal || hasDismissCookie()) {
		return;
	}

	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('ts-mailnest-subscribe-open');
}

function closeSubscribeModal(permanent = false): void {
	const modal = getSubscribeModal();
	if (!modal) {
		return;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('ts-mailnest-subscribe-open');

	if (permanent) {
		const config = getSubscribeConfig();
		const hours = config?.cookie_duration ?? 24;
		setCookie(DISMISS_COOKIE, '1', hours);
	}
}

function showSubscribeMessage(message: string, type: 'success' | 'error'): void {
	notify({
		type: type === 'success' ? 'success' : 'error',
		message,
		surface: 'toast',
	});
}

function handleConfirmationQueryParams(): void {
	const config = getSubscribeConfig();
	if (!config) {
		return;
	}

	const params = new URLSearchParams(window.location.search);
	if (params.has('wc_subscribe_confirmed')) {
		showSubscribeMessage(config.messages.confirmed, 'success');
	}
	if (params.has('wc_subscribe_error')) {
		showSubscribeMessage(config.messages.confirm_error, 'error');
	}
}

async function submitSubscribeForm(form: HTMLFormElement): Promise<void> {
	const config = getSubscribeConfig();
	if (!config) {
		return;
	}

	const emailInput = form.querySelector<HTMLInputElement>('input[name="email"]');
	const submitBtn = form.querySelector<HTMLButtonElement>('.wc-subscribe-form-submit');
	const email = emailInput?.value.trim() ?? '';

	if (!email) {
		showSubscribeMessage(config.messages.email_required, 'error');
		return;
	}

	if (!isValidEmail(email)) {
		showSubscribeMessage(config.messages.email_invalid, 'error');
		return;
	}

	setControlLoading(submitBtn, true);

	const body = new URLSearchParams({
		action: 'subscribe_email',
		email,
		wc_subscribe_nonce: config.nonce,
		source: form.closest('[data-ts-mailnest-subscribe-modal]') ? 'popup' : 'shortcode',
	});

	try {
		const response = await fetch(config.ajax_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
			credentials: 'same-origin',
		});

		const payload = (await response.json()) as { success?: boolean; data?: string };

		if (payload.success) {
			const message = typeof payload.data === 'string' ? payload.data : config.messages.success;
			showSubscribeMessage(message, 'success');
			form.reset();

			if (form.closest('[data-ts-mailnest-subscribe-modal]')) {
				window.setTimeout(() => closeSubscribeModal(true), 2000);
			}
		} else {
			const message = typeof payload.data === 'string' ? payload.data : config.messages.error;
			showSubscribeMessage(message, 'error');
		}
	} catch {
		showSubscribeMessage(config.messages.error, 'error');
	} finally {
		setControlLoading(submitBtn, false);
	}
}

function bindSubscribeForms(): void {
	document.addEventListener('submit', (event) => {
		const form = (event.target as HTMLElement).closest<HTMLFormElement>('.wc-subscribe-form');
		if (!form) {
			return;
		}

		event.preventDefault();
		void submitSubscribeForm(form);
	});
}

function bindSubscribeModal(): void {
	const config = getSubscribeConfig();
	const modal = getSubscribeModal();

	if (!modal) {
		return;
	}

	if (hasDismissCookie()) {
		modal.remove();
		return;
	}

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		if (target.closest('[data-ts-mailnest-subscribe-close]')) {
			event.preventDefault();
			closeSubscribeModal(false);
		}
	});

	const dismissCheckbox = modal.querySelector<HTMLInputElement>('.wc-subscribe-popup-dismiss-checkbox');
	dismissCheckbox?.addEventListener('change', () => {
		if (!dismissCheckbox.checked) {
			setCookie(DISMISS_COOKIE, '', -1);
			return;
		}

		const hours = config?.cookie_duration ?? 24;
		setCookie(DISMISS_COOKIE, '1', hours);
		window.setTimeout(() => closeSubscribeModal(false), 500);
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !modal.hidden) {
			closeSubscribeModal(false);
		}
	});

	const delaySeconds = Number.parseInt(modal.getAttribute('data-popup-delay') ?? '5', 10);
	const delayMs = Number.isFinite(delaySeconds) ? delaySeconds * 1000 : 5000;

	window.setTimeout(() => {
		if (!hasDismissCookie()) {
			openSubscribeModal();
		}
	}, delayMs);
}

const formSubmitting: Record<string, boolean> = {};

function clearFieldErrors(form: HTMLFormElement): void {
	form.querySelectorAll<HTMLElement>('[data-ts-mailnest-field-error]').forEach((el) => {
		el.hidden = true;
		el.textContent = '';
	});
	form.querySelectorAll<HTMLElement>('[aria-invalid="true"]').forEach((el) => {
		el.removeAttribute('aria-invalid');
	});
}

function showFieldError(form: HTMLFormElement, name: string, message: string): void {
	const slot = form.querySelector<HTMLElement>(`[data-ts-mailnest-field-error="${name}"]`);
	const field = form.querySelector<HTMLElement>(`[name="${name}"], [name="${name}[]"]`);
	if (field) {
		field.setAttribute('aria-invalid', 'true');
	}
	if (slot) {
		slot.textContent = message;
		slot.hidden = false;
	}
}

function collectFormData(form: HTMLFormElement, formId: string): URLSearchParams {
	const config = getFormConfig();
	const data = new URLSearchParams({
		action: 'wc_mailnest_submit_form',
		wc_mailnest_form_nonce: config?.nonce ?? '',
		form_id: formId,
	});

	const legacyNonce = form.querySelector<HTMLInputElement>('input[name="mailnest_form_nonce"]');
	if (legacyNonce?.value) {
		data.set('mailnest_form_nonce', legacyNonce.value);
	}

	form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
		'input, textarea, select',
	).forEach((field) => {
		const name = field.getAttribute('name');
		if (!name) {
			return;
		}

		if (name.startsWith('mailnest_') || name.startsWith('wc_mailnest_') || name === 'action') {
			return;
		}

		if (field instanceof HTMLInputElement && field.type === 'checkbox') {
			if (!field.checked) {
				return;
			}
			const existing = data.getAll(name);
			if (existing.length) {
				data.append(name, field.value);
			} else {
				data.set(name, field.value);
			}
			return;
		}

		if (field instanceof HTMLInputElement && field.type === 'radio') {
			if (field.checked) {
				data.set(name, field.value);
			}
			return;
		}

		data.set(name, field.value);
	});

	return data;
}

async function submitContactForm(form: HTMLFormElement): Promise<void> {
	const config = getFormConfig();
	if (!config) {
		return;
	}

	const formId =
		form.closest<HTMLElement>('.mailnest-form-container')?.dataset.formId ??
		form.querySelector<HTMLInputElement>('input[name="form_id"]')?.value ??
		'';
	const formKey = `form_${formId}`;

	if (formSubmitting[formKey]) {
		return;
	}

	if (!form.checkValidity()) {
		const messages = config.messages;
		form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
			'input, textarea, select',
		).forEach((field) => {
			if (field.validity.valid) {
				return;
			}
			const name = field.getAttribute('name')?.replace(/\[\]$/, '') ?? '';
			if (!name) {
				return;
			}
			const message = field.validity.valueMissing ? messages.required : messages.invalid;
			showFieldError(form, name, message);
		});
		return;
	}

	formSubmitting[formKey] = true;
	clearFieldErrors(form);

	const successEl = form.querySelector<HTMLElement>('[data-ts-mailnest-form-success]');
	const errorEl = form.querySelector<HTMLElement>('[data-ts-mailnest-form-error]');
	successEl?.setAttribute('hidden', '');
	errorEl?.setAttribute('hidden', '');

	const submitBtn = form.querySelector<HTMLButtonElement>('button[type="submit"]');
	setControlLoading(submitBtn, true);

	try {
		const response = await fetch(config.ajax_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: collectFormData(form, formId).toString(),
			credentials: 'same-origin',
		});

		const payload = (await response.json()) as {
			success?: boolean;
			data?: { message?: string } | string;
		};

		if (payload.success) {
			const message =
				typeof payload.data === 'object' && payload.data?.message
					? payload.data.message
					: typeof payload.data === 'string'
						? payload.data
						: config.messages.success;

			if (successEl) {
				successEl.textContent = message;
				successEl.removeAttribute('hidden');
			}

			form.reset();
			form.scrollIntoView({ behavior: 'smooth', block: 'start' });
		} else {
			const message =
				typeof payload.data === 'object' && payload.data?.message
					? payload.data.message
					: typeof payload.data === 'string'
						? payload.data
						: config.messages.error;

			if (errorEl) {
				errorEl.innerHTML = message;
				errorEl.removeAttribute('hidden');
			}
		}
	} catch {
		if (errorEl) {
			errorEl.textContent = config.messages.error;
			errorEl.removeAttribute('hidden');
		}
	} finally {
		setControlLoading(submitBtn, false);
		formSubmitting[formKey] = false;
	}
}

function bindContactForms(): void {
	document.addEventListener(
		'submit',
		(event) => {
			const form = (event.target as HTMLElement).closest<HTMLFormElement>(
				'[data-ts-mailnest-form], .mailnest-form',
			);
			if (!form) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();
			void submitContactForm(form);
		},
		true,
	);

	document.addEventListener('blur', (event) => {
		const target = event.target;
		if (
			!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement)
		) {
			return;
		}

		const form = target.closest<HTMLFormElement>('[data-ts-mailnest-form], .mailnest-form');
		if (!form) {
			return;
		}

		const name = target.getAttribute('name')?.replace(/\[\]$/, '') ?? '';
		if (!name || target.validity.valid) {
			return;
		}

		const config = getFormConfig();
		if (!config) {
			return;
		}

		const message = target.validity.valueMissing ? config.messages.required : config.messages.invalid;
		showFieldError(form, name, message);
	}, true);
}

export function initMailnest(): void {
	if (!document.body.classList.contains('ts-mailnest')) {
		return;
	}

	handleConfirmationQueryParams();
	bindSubscribeForms();
	bindSubscribeModal();
	bindContactForms();
}

initMailnest();
