/**
 * 密码明文切换（对齐 WooCommerce `.password-input` + `.show-password-input`）。
 *
 * 账户页由 `woocommerce.js` 处理；登录 modal 在 `.woocommerce` 外，
 * 且非 cart/checkout/account 页会卸掉该脚本，故主题自行增强。
 */

type PasswordVisibilityLabels = {
	show: string;
	hide: string;
};

function getPasswordVisibilityLabels(): PasswordVisibilityLabels {
	const i18n = window.tailwindscoreAccountAuth?.i18n;

	return {
		show: i18n?.showPassword ?? 'Show password',
		hide: i18n?.hidePassword ?? 'Hide password',
	};
}

function togglePasswordVisibility(
	input: HTMLInputElement,
	button: HTMLButtonElement,
	labels: PasswordVisibilityLabels,
): void {
	const showing = button.classList.toggle('display-password');

	input.type = showing ? 'text' : 'password';
	button.setAttribute('aria-label', showing ? labels.hide : labels.show);
	input.focus();
}

function enhancePasswordInput(input: HTMLInputElement, labels: PasswordVisibilityLabels): void {
	if (input.closest('.password-input')) {
		return;
	}

	const parent = input.parentNode;

	if (!parent) {
		return;
	}

	const wrapper = document.createElement('span');
	wrapper.className = 'password-input';

	const button = document.createElement('button');
	button.type = 'button';
	button.className = 'show-password-input';
	button.setAttribute('data-ts-password-toggle', '');
	button.setAttribute('aria-label', labels.show);

	if (input.id) {
		button.setAttribute('aria-describedby', input.id);
	}

	parent.insertBefore(wrapper, input);
	wrapper.append(input, button);

	button.addEventListener('click', (event) => {
		event.preventDefault();
		togglePasswordVisibility(input, button, labels);
	});
}

/**
 * 为 `root` 内尚未包装的 Woo 密码框补上明文切换按钮。
 */
export function initPasswordVisibility(root: ParentNode): void {
	const labels = getPasswordVisibilityLabels();

	root
		.querySelectorAll<HTMLInputElement>('input.woocommerce-Input[type="password"]')
		.forEach((input) => {
			enhancePasswordInput(input, labels);
		});
}
