/**
 * 归档排序：归档页走 AJAX（由 archive-nav 拦截）；其它页面仍 form submit。
 */

function onOrderingChange(event: Event): void {
	const target = event.target;
	if (!(target instanceof HTMLSelectElement) || !target.classList.contains('orderby')) {
		return;
	}

	// 归档 AJAX 由 archive-nav 在 capture 阶段处理。
	if (target.closest('[data-ts-archive]')) {
		return;
	}

	const form = target.closest<HTMLFormElement>('form.woocommerce-ordering');
	if (!form) {
		return;
	}

	form.submit();
}

function initOrdering(): void {
	document.addEventListener('change', onOrderingChange);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initOrdering, { once: true });
} else {
	initOrdering();
}
