/**
 * Modal 开关公共原语（薄层辅助）。
 *
 * 覆盖两套现有模式的公共部分，不改变各调用方的差异化行为：
 * - `hidden` + `aria-hidden` + body class 型：login-modal / item-edit-modal / quick-view / review-form-modal
 * - 原生 `<dialog>` 型：size-guide / review-lightbox（仅补 backdrop 点击关闭）
 *
 * 焦点目标解析、returnFocus 存储时机、Esc 是否 preventDefault 等差异较大，
 * 仍由各调用方自行处理，避免为了「统一」引入行为变化。
 */

/** `hidden` 型 modal 是否处于打开状态。 */
export function isHiddenModalOpen(modal: HTMLElement | null): boolean {
	return modal !== null && !modal.hasAttribute('hidden');
}

/** 打开 `hidden` 型 modal：清除 `hidden`、置 `aria-hidden="false"`、可选加 body class。 */
export function showHiddenModal(modal: HTMLElement, bodyOpenClass?: string): void {
	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');

	if (bodyOpenClass) {
		document.body.classList.add(bodyOpenClass);
	}
}

/** 关闭 `hidden` 型 modal：置 `hidden`、`aria-hidden="true"`、可选移除 body class。 */
export function hideHiddenModal(modal: HTMLElement, bodyOpenClass?: string): void {
	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');

	if (bodyOpenClass) {
		document.body.classList.remove(bodyOpenClass);
	}
}

/** 关闭后若目标元素仍在文档中则聚焦回去（对已从文档移除的元素调用 `.focus()` 本身是安全的 no-op）。 */
export function restoreFocus(target: HTMLElement | null): void {
	if (target?.isConnected) {
		target.focus();
	}
}

/** 绑定文档级 Esc 关闭：仅当 `isOpen()` 为真时触发，并 `preventDefault`。 */
export function bindEscapeToClose(isOpen: () => boolean, close: () => void): void {
	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape' || !isOpen()) {
			return;
		}

		event.preventDefault();
		close();
	});
}

/** 原生 `<dialog>`：点击 backdrop（事件目标即 dialog 本身）时关闭。 */
export function bindBackdropClickToClose(dialog: HTMLDialogElement, close: () => void): void {
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) {
			close();
		}
	});
}
