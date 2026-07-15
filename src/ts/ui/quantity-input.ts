function getQuantityRoot(element: Element): HTMLElement | null {
	return element.closest<HTMLElement>('[data-ts-quantity]');
}

function getQuantityInput(root: HTMLElement): HTMLInputElement | null {
	return root.querySelector<HTMLInputElement>('[data-ts-quantity-input], .quantity__input');
}

function clampQuantity(value: number, input: HTMLInputElement): number {
	const min = Number(input.min);
	const max = Number(input.max);
	let next = value;

	if (Number.isFinite(min)) {
		next = Math.max(min, next);
	}

	if (Number.isFinite(max) && max > 0) {
		next = Math.min(max, next);
	}

	return next;
}

function bindProductQuantityControls() {
	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		const stepButton = target.closest<HTMLButtonElement>('[data-ts-quantity-step]');
		if (!stepButton) {
			return;
		}

		const root = getQuantityRoot(stepButton);
		if (!root || root.hasAttribute('data-ts-cart-quantity-controls')) {
			return;
		}

		const input = getQuantityInput(root);
		if (!input || input.disabled) {
			return;
		}

		event.preventDefault();

		const step = Number(stepButton.dataset.tsQuantityStep ?? '0');
		if (!Number.isFinite(step) || step === 0) {
			return;
		}

		const nextQuantity = clampQuantity(Number(input.value || '0') + step, input);
		input.value = String(nextQuantity);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
}

bindProductQuantityControls();
