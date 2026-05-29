/**
 * Quick View: text variation buttons synced with wc-add-to-cart-variation.
 */

type JqForm = {
	wc_variation_form?: () => JqForm;
	trigger: (event: string) => JqForm;
	on: (events: string, handler: (...args: unknown[]) => void) => JqForm;
};

type JQueryLike = (target: Document | Element | HTMLSelectElement) => JqForm;

function getJQuery(): JQueryLike | undefined {
	return (window as Window & { jQuery?: JQueryLike }).jQuery;
}

function findAttributeSelect(form: Element, attribute: string): HTMLSelectElement | null {
	const list = form.querySelector<HTMLElement>(
		`.custom-variation-selector[data-attribute="${CSS.escape(attribute)}"]`,
	);
	const rowSelect = list
		?.closest('.variation-row, .ts-variation-row')
		?.querySelector<HTMLSelectElement>('select[name^="attribute_"]');

	if (rowSelect) {
		return rowSelect;
	}

	const candidates = [
		`select[name="attribute_${attribute}"]`,
		`select[name="attribute_pa_${attribute}"]`,
	];

	if (attribute.startsWith('pa_')) {
		candidates.unshift(`select[name="attribute_${attribute}"]`);
	} else {
		candidates.push(`select[name="attribute_pa_${attribute}"]`);
	}

	for (const selector of candidates) {
		const select = form.querySelector<HTMLSelectElement>(`.variations ${selector}`);
		if (select) {
			return select;
		}
	}

	for (const selector of candidates) {
		const select = form.querySelector<HTMLSelectElement>(selector);
		if (select) {
			return select;
		}
	}

	return null;
}

function triggerSelectChange(select: HTMLSelectElement, jq: JQueryLike): void {
	jq(select).trigger('change');
}

function updateSelectedLabel(form: Element, attribute: string, label: string): void {
	const el = form.querySelector(`.selected-attribute-value[data-for="${attribute}"]`);
	if (el) {
		el.textContent = label;
	}
}

function formRequiresVariationSelection(form: Element): boolean {
	return form.hasAttribute('data-ts-require-variation-selection');
}

function syncDisabledFromSelects(form: Element, jq?: JQueryLike): void {
	form.querySelectorAll<HTMLElement>('.custom-variation-selector').forEach((list) => {
		const attribute = list.getAttribute('data-attribute');
		if (!attribute) {
			return;
		}

		const select = findAttributeSelect(form, attribute);
		if (!select) {
			return;
		}

		list.querySelectorAll<HTMLLIElement>('li').forEach((li) => {
			const value = li.getAttribute('data-value');
			if (!value) {
				return;
			}

			const option = select.querySelector<HTMLOptionElement>(`option[value="${CSS.escape(value)}"]`);
			const disabled = option?.disabled ?? option?.classList.contains('disabled') ?? false;
			li.classList.toggle('disabled', disabled);
			if (disabled && li.classList.contains('selected')) {
				li.classList.remove('selected');
			}
		});
	});

	if (jq) {
		ensureRequiredSelections(form, jq);
	}
}

function ensureRequiredSelections(form: Element, jq: JQueryLike): void {
	if (!formRequiresVariationSelection(form)) {
		return;
	}

	form.querySelectorAll<HTMLElement>('.custom-variation-selector').forEach((list) => {
		const attribute = list.getAttribute('data-attribute');
		if (!attribute) {
			return;
		}

		const select = findAttributeSelect(form, attribute);
		if (!select) {
			return;
		}

		let selectedLi = list.querySelector<HTMLLIElement>('li.selected:not(.disabled)');

		if (!selectedLi) {
			selectedLi = list.querySelector<HTMLLIElement>('li:not(.disabled)');
			if (!selectedLi) {
				return;
			}

			list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
			selectedLi.classList.add('selected');
		}

		const value = selectedLi.getAttribute('data-value') ?? '';
		if (select.value !== value) {
			select.value = value;
			triggerSelectChange(select, jq);
		}

		updateSelectedLabel(form, attribute, selectedLi.getAttribute('data-name') ?? '');
	});
}

function bindTextButtons(root: HTMLElement): void {
	const forms = root.querySelectorAll<HTMLFormElement>('form.variations_form');

	forms.forEach((form) => {
		form.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof Element)) {
				return;
			}

			const li = target.closest<HTMLLIElement>('.custom-variation-selector li');
			if (!li || !form.contains(li)) {
				return;
			}

			const list = li.parentElement;
			if (!list?.classList.contains('custom-variation-selector')) {
				return;
			}

			const attribute = list.getAttribute('data-attribute');
			const value = li.getAttribute('data-value');
			if (!attribute || !value) {
				return;
			}

			const jq = getJQuery();
			if (!jq) {
				return;
			}

			const select = findAttributeSelect(form, attribute);
			if (!select) {
				return;
			}

			event.preventDefault();

			const requireSelection = formRequiresVariationSelection(form);

			if (li.classList.contains('disabled')) {
				if (requireSelection) {
					return;
				}

				list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
				form.querySelectorAll<HTMLSelectElement>('.variations select').forEach((s) => {
					s.value = '';
					triggerSelectChange(s, jq);
				});
				updateSelectedLabel(form, attribute, '');
				return;
			}

			if (li.classList.contains('selected')) {
				if (requireSelection) {
					return;
				}

				list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
				select.value = '';
				triggerSelectChange(select, jq);
				updateSelectedLabel(form, attribute, '');
				return;
			}

			list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
			li.classList.add('selected');
			select.value = value;
			triggerSelectChange(select, jq);
			updateSelectedLabel(form, attribute, li.getAttribute('data-name') ?? '');
		});

		form.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter' && event.key !== ' ') {
				return;
			}

			const target = event.target;
			if (!(target instanceof HTMLLIElement) || !target.matches('.custom-variation-selector li')) {
				return;
			}

			event.preventDefault();
			target.click();
		});
	});
}

/**
 * Initialize WooCommerce variation forms inside Quick View content.
 */
export async function initQuickViewVariations(root: HTMLElement): Promise<void> {
	const jq = getJQuery();
	if (!jq) {
		return;
	}

	const forms = root.querySelectorAll<HTMLFormElement>('form.variations_form');

	forms.forEach((form) => {
		const $form = jq(form) as JqForm & { wc_variation_form?: () => void };
		if (typeof $form.wc_variation_form === 'function') {
			$form.wc_variation_form();
		}

		$form.on('woocommerce_update_variation_values', () => {
			syncDisabledFromSelects(form, jq);
		});

		$form.on('show_variation', (...args: unknown[]) => {
			const variation = args[1] as { price_html?: string } | undefined;
			const priceEl = root.querySelector<HTMLElement>('.ts-quick-view-product__price');
			if (priceEl && variation?.price_html) {
				priceEl.innerHTML = variation.price_html;
			}
		});

		$form.trigger('check_variations');
		syncDisabledFromSelects(form, jq);

		form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li.selected').forEach((selected) => {
			const attribute = selected.closest('.custom-variation-selector')?.getAttribute('data-attribute');
			if (attribute) {
				updateSelectedLabel(form, attribute, selected.getAttribute('data-name') ?? '');
			}
		});
	});

	bindTextButtons(root);
}

/**
 * Read selected variation attributes from the swatch UI for save requests.
 */
export function collectVariationFromUi(form: HTMLFormElement): Record<string, string> {
	const variation: Record<string, string> = {};

	form.querySelectorAll<HTMLElement>('.custom-variation-selector').forEach((list) => {
		const attribute = list.getAttribute('data-attribute');
		const selected = list.querySelector<HTMLLIElement>('li.selected:not(.disabled)');

		if (!attribute || !selected) {
			return;
		}

		const value = selected.getAttribute('data-value') ?? '';
		if ('' === value) {
			return;
		}

		const select = findAttributeSelect(form, attribute);
		const key = select?.name ?? (attribute.startsWith('attribute_') ? attribute : `attribute_${attribute}`);
		variation[key] = value;
	});

	return variation;
}
