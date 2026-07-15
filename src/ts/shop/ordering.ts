/**
 * 归档排序：替代已 dequeue 的 WC `woocommerce.js` 中
 * `.woocommerce-ordering select.orderby` change → form submit。
 */

function onOrderingChange(event: Event): void {
	const target = event.target;
	if (!(target instanceof HTMLSelectElement) || !target.classList.contains('orderby')) {
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
