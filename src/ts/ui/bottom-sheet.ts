/**
 * Bottom sheet 开闭辅助（面板 slide-up + overlay 同步关闭）。
 * 样式由 SCSS mixin `ts-bottom-sheet-*` 提供。
 */

/** 对齐 `ts-bottom-sheet-panel` mixin 默认时长。 */
export const BOTTOM_SHEET_TRANSITION_MS = 380;

export const OVERLAY_BACKDROP_TRANSITION_MS = 350;

export type WaitForTransitionOptions = {
	propertyName?: string;
	fallbackMs?: number;
};

export function waitForTransition(
	element: Element | null,
	options: WaitForTransitionOptions = {},
): Promise<void> {
	const { propertyName = 'transform', fallbackMs = BOTTOM_SHEET_TRANSITION_MS } = options;

	if (!element) {
		return Promise.resolve();
	}

	return new Promise((resolve) => {
		let settled = false;

		const finish = (): void => {
			if (settled) {
				return;
			}
			settled = true;
			element.removeEventListener('transitionend', onEnd);
			window.clearTimeout(fallback);
			resolve();
		};

		const onEnd: EventListener = (event): void => {
			if (!(event instanceof TransitionEvent)) {
				return;
			}
			if (event.target === element && event.propertyName === propertyName) {
				finish();
			}
		};

		element.addEventListener('transitionend', onEnd);
		const fallback = window.setTimeout(finish, fallbackMs);
	});
}

/** 滑入：取消 hidden → 双 rAF → 加 `is-open`。 */
export function openBottomSheetPanel(panel: HTMLElement): void {
	panel.hidden = false;
	panel.classList.remove('is-open');
	panel.getBoundingClientRect();

	requestAnimationFrame(() => {
		panel.getBoundingClientRect();
		panel.classList.add('is-open');
	});
}

/** 滑出：移除 `is-open` 并等待 transform。 */
export function closeBottomSheetPanel(panel: HTMLElement | null): Promise<void> {
	if (!panel || !panel.classList.contains('is-open')) {
		return Promise.resolve();
	}

	panel.classList.remove('is-open');
	return waitForTransition(panel);
}

/**
 * 同时关闭 overlay 壳与滑动面板。
 */
export async function closeBottomSheetOverlay(
	overlay: HTMLElement | null,
	panel: HTMLElement | null,
): Promise<void> {
	overlay?.classList.remove('is-open');

	if (panel?.classList.contains('is-open')) {
		panel.classList.remove('is-open');
		await waitForTransition(panel);
		return;
	}

	await waitForTransition(panel, { fallbackMs: OVERLAY_BACKDROP_TRANSITION_MS });
}
