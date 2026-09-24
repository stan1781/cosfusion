/**
 * PWA Level 1：注册 SW + 可选 install 底部提示条（无遮罩，不阻断浏览）。
 */

type BeforeInstallPromptEvent = Event & {
	prompt: () => Promise<void>;
	userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

type TsPwaConfig = {
	enabled: boolean;
	installPromptEnable: boolean;
	swUrl: string;
	scope: string;
	i18n?: Record<string, string>;
};

declare global {
	interface Window {
		tsPwa?: TsPwaConfig;
	}
}

const DISMISS_KEY = 'ts_pwa_install_dismissed';

function isStandalone(): boolean {
	const nav = window.navigator as Navigator & { standalone?: boolean };
	return window.matchMedia('(display-mode: standalone)').matches || true === nav.standalone;
}

function isIosSafari(): boolean {
	const ua = window.navigator.userAgent;
	const isIos =
		/iPad|iPhone|iPod/.test(ua) || ('MacIntel' === navigator.platform && navigator.maxTouchPoints > 1);
	const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|Chrome/.test(ua);
	return isIos && (isSafari || /iPad|iPhone|iPod/.test(ua));
}

function wasDismissed(): boolean {
	try {
		return '1' === window.sessionStorage.getItem(DISMISS_KEY);
	} catch {
		return false;
	}
}

function markDismissed(): void {
	try {
		window.sessionStorage.setItem(DISMISS_KEY, '1');
	} catch {
		// ignore
	}
}

function i18n(key: string, fallback: string): string {
	return window.tsPwa?.i18n?.[key] || fallback;
}

/**
 * 对齐 Starcore `.sc-pwa-install`：固定底部提示条，无全屏 overlay。
 */
function mountBanner(opts: {
	title: string;
	body: string;
	actionLabel?: string;
	onAction?: () => void;
}): HTMLElement {
	const root = document.createElement('div');
	root.className = 'pwa-install';
	root.setAttribute('role', 'status');
	root.setAttribute('aria-live', 'polite');

	const copy = document.createElement('div');
	copy.className = 'pwa-install__copy';

	const title = document.createElement('p');
	title.className = 'pwa-install__title';
	title.textContent = opts.title;

	const body = document.createElement('p');
	body.className = 'pwa-install__body';
	body.textContent = opts.body;

	copy.append(title, body);

	const actions = document.createElement('div');
	actions.className = 'pwa-install__actions';

	const dismiss = document.createElement('button');
	dismiss.type = 'button';
	dismiss.className = 'btn btn-outline btn-sm';
	dismiss.textContent = i18n('installDismiss', 'Not now');
	dismiss.addEventListener('click', () => {
		markDismissed();
		root.remove();
	});

	actions.append(dismiss);

	if (opts.actionLabel && opts.onAction) {
		const action = document.createElement('button');
		action.type = 'button';
		action.className = 'btn btn-primary btn-sm';
		action.textContent = opts.actionLabel;
		action.addEventListener('click', () => {
			opts.onAction?.();
		});
		actions.append(action);
	}

	root.append(copy, actions);
	document.body.append(root);
	return root;
}

function setupInstallPrompt(): void {
	if (isStandalone() || wasDismissed()) {
		return;
	}

	let deferred: BeforeInstallPromptEvent | null = null;
	let banner: HTMLElement | null = null;

	window.addEventListener('beforeinstallprompt', (event) => {
		event.preventDefault();
		deferred = event as BeforeInstallPromptEvent;
		if (banner) {
			return;
		}
		banner = mountBanner({
			title: i18n('installTitle', 'Add to Home Screen'),
			body: i18n('installBody', 'Install for quick access like an app.'),
			actionLabel: i18n('installAction', 'Install'),
			onAction: () => {
				if (!deferred) {
					return;
				}
				void deferred
					.prompt()
					.then(() => deferred?.userChoice)
					.then(() => {
						deferred = null;
						banner?.remove();
						banner = null;
					});
			},
		});
	});

	if (isIosSafari()) {
		window.setTimeout(() => {
			if (banner || wasDismissed() || isStandalone()) {
				return;
			}
			banner = mountBanner({
				title: i18n('installTitle', 'Add to Home Screen'),
				body: i18n('iosHint', 'In Safari, tap Share, then Add to Home Screen.'),
			});
		}, 2500);
	}
}

/**
 * 生产环境注册 Level 1 Service Worker（Vite dev / PHP 关闭 enabled 时跳过）。
 */
export function registerServiceWorker(): void {
	const config = window.tsPwa;
	if (!config?.enabled || !config.swUrl) {
		return;
	}

	if (config.installPromptEnable) {
		setupInstallPrompt();
	}

	if (!('serviceWorker' in navigator)) {
		return;
	}

	const swUrl = config.swUrl;
	const scope = config.scope || '/';

	window.addEventListener('load', () => {
		void navigator.serviceWorker.register(swUrl, { scope }).catch(() => {
			// 静默失败：不影响购买流程。
		});
	});
}
