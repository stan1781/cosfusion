/** AJAX 登录文案（合并进 window.tailwindscoreAccountAuth.i18n）。 */
export type AccountAuthLoginI18n = {
	usernameRequired: string;
	passwordRequired: string;
	loginFailed: string;
	networkError: string;
	registrationSuccess?: string;
	showPassword?: string;
	hidePassword?: string;
};

/** Strings for AJAX register (window.tailwindscoreAccountAuth.registerI18n). */
export type AccountAuthRegisterI18n = {
	usernameRequired: string;
	emailRequired: string;
	passwordRequired: string;
	passwordMismatch: string;
	registerFailed: string;
	networkError: string;
};

/** Runtime object from PHP (login + register fields merged on account pages). */
export type TailwindscoreAccountAuthConfig = {
	ajaxUrl: string;
	ajaxAction?: string;
	redirectUrl?: string;
	i18n?: AccountAuthLoginI18n;
	registerAjaxAction?: string;
	registerRedirectUrl?: string;
	registerI18n?: AccountAuthRegisterI18n;
};

export type AccountAuthLoginConfig = {
	ajaxUrl: string;
	ajaxAction: string;
	redirectUrl: string;
	i18n: AccountAuthLoginI18n;
};

export type AccountAuthRegisterConfig = {
	ajaxUrl: string;
	registerAjaxAction: string;
	registerRedirectUrl: string;
	registerI18n: AccountAuthRegisterI18n;
};
