/**
 * 全屏全局 loading 遮罩（引用计数，支持嵌套异步）。
 */

let overlayEl: HTMLElement | null = null;
let pendingCount = 0;

function ensureOverlay(): HTMLElement {
	if (overlayEl) {
		return overlayEl;
	}

	overlayEl = document.createElement('div');
	overlayEl.className = 'global-loading';
	overlayEl.hidden = true;
	overlayEl.setAttribute('aria-hidden', 'true');
	overlayEl.innerHTML = '<div class="spinner" role="status"></div>';
	document.body.appendChild(overlayEl);
	return overlayEl;
}

export function setGlobalLoading(isLoading: boolean): void {
	const overlay = ensureOverlay();
	pendingCount = Math.max(0, pendingCount + (isLoading ? 1 : -1));
	const active = pendingCount > 0;

	overlay.hidden = !active;
	overlay.setAttribute('aria-hidden', active ? 'false' : 'true');
	document.documentElement.classList.toggle('is-global-loading', active);
}

export async function withGlobalLoading<T>(action: () => Promise<T>): Promise<T> {
	setGlobalLoading(true);

	try {
		return await action();
	} finally {
		setGlobalLoading(false);
	}
}
