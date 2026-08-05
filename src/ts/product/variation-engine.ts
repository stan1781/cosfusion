import type { WcVariationRow } from './types';
import { resolveVariationsI18n, syncVariationAtcButtons } from './variation-atc-ready';

export type ChosenAttributesState = {
	count: number;
	chosenCount: number;
	data: Record<string, string>;
};

export type VariationFormCallbacks = {
	onShowVariation?: (variation: WcVariationRow) => void;
	onHideVariation?: () => void;
};

export function parseVariationsForm(form: HTMLFormElement): WcVariationRow[] {
	const raw = form.getAttribute('data-product_variations');
	if (!raw) {
		return [];
	}

	try {
		const parsed = JSON.parse(raw) as WcVariationRow[];
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
}

export function getChosenAttributes(form: HTMLFormElement): Record<string, string> {
	return getChosenAttributesState(form).data;
}

export function getChosenAttributesState(form: HTMLFormElement): ChosenAttributesState {
	const data: Record<string, string> = {};
	let count = 0;
	let chosenCount = 0;

	form.querySelectorAll<HTMLSelectElement>('select[name^="attribute_"]').forEach((select) => {
		const name = select.getAttribute('name');
		if (!name) {
			return;
		}

		count++;
		const value = select.value || '';
		if (value.length > 0) {
			chosenCount++;
		}
		data[name] = value;
	});

	return { count, chosenCount, data };
}

/**
 * Mirrors WooCommerce VariationForm.isMatch().
 */
export function isVariationMatch(
	variationAttributes: Record<string, string>,
	chosen: Record<string, string>,
): boolean {
	for (const attrName of Object.keys(variationAttributes)) {
		const val1 = variationAttributes[attrName];
		const val2 = chosen[attrName];

		if (
			val1 !== undefined &&
			val2 !== undefined &&
			val1.length !== 0 &&
			val2.length !== 0 &&
			val1 !== val2
		) {
			return false;
		}
	}

	return true;
}

export function findMatchingVariations(
	variations: WcVariationRow[],
	attributes: Record<string, string>,
): WcVariationRow[] {
	return variations.filter((variation) => isVariationMatch(variation.attributes, attributes));
}

export function findMatchingVariation(
	form: HTMLFormElement,
	variations: WcVariationRow[],
): WcVariationRow | null {
	const { count, chosenCount, data } = getChosenAttributesState(form);

	if (count < 1 || chosenCount !== count) {
		return null;
	}

	return findMatchingVariations(variations, data)[0] ?? null;
}

export function findAttributeSelect(form: Element, attribute: string): HTMLSelectElement | null {
	const list = form.querySelector<HTMLElement>(
		`.custom-variation-selector[data-attribute="${CSS.escape(attribute)}"]`,
	);
	const rowSelect = list
		?.closest('.variation-row, .variation-row')
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

function triggerSelectChange(select: HTMLSelectElement): void {
	select.dispatchEvent(new Event('change', { bubbles: true }));
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

function syncSwatchDisabledFromSelects(form: Element): void {
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
}

function updateVariationSelects(form: HTMLFormElement, variations: WcVariationRow[]): void {
	const selects = Array.from(
		form.querySelectorAll<HTMLSelectElement>('.variations select[name^="attribute_"]'),
	);

	for (const select of selects) {
		const attrName = select.getAttribute('name') ?? '';
		const attributes = {
			...getChosenAttributesState(form).data,
			[attrName]: '',
		};
		const matching = findMatchingVariations(variations, attributes);
		const available = new Set<string>();

		for (const variation of matching) {
			if (variation.variation_is_active === false) {
				continue;
			}

			const value = variation.attributes[attrName];
			if (value) {
				available.add(value);
				continue;
			}

			select.querySelectorAll('option').forEach((option) => {
				if (option.value) {
					available.add(option.value);
				}
			});
		}

		select.querySelectorAll('option').forEach((option) => {
			if (!option.value) {
				return;
			}
			option.disabled = !available.has(option.value);
		});
	}
}

function setVariationId(form: HTMLFormElement, variationId: number): void {
	const input = form.querySelector<HTMLInputElement>('input.variation_id, input[name="variation_id"]');
	if (input) {
		input.value = variationId > 0 ? String(variationId) : '';
	}
}

	function checkVariations(
		form: HTMLFormElement,
		variations: WcVariationRow[],
		callbacks: VariationFormCallbacks,
	): void {
		updateVariationSelects(form, variations);
		syncSwatchDisabledFromSelects(form);

		if (ensureRequiredSelections(form, variations, callbacks)) {
			return;
		}

		const { count, chosenCount, data } = getChosenAttributesState(form);
		let matched: WcVariationRow | null = null;

		if (count > 0 && chosenCount === count) {
			const matches = findMatchingVariations(variations, data);
			const variation =
				matches.find((row) => row.variation_is_visible !== false) ?? matches[0] ?? null;

			if (variation) {
				setVariationId(form, variation.variation_id);
				callbacks.onShowVariation?.(variation);
				matched = variation;
			} else {
				setVariationId(form, 0);
				callbacks.onHideVariation?.();
			}
		} else {
			setVariationId(form, 0);
			callbacks.onHideVariation?.();
		}

		syncVariationAtcButtons(form, resolveVariationsI18n(form), matched);
	}

function ensureRequiredSelections(
	form: HTMLFormElement,
	variations: WcVariationRow[],
	callbacks: VariationFormCallbacks,
): boolean {
	if (!formRequiresVariationSelection(form)) {
		return false;
	}

	let changed = false;

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
			changed = true;
		}

		updateSelectedLabel(form, attribute, selectedLi.getAttribute('data-name') ?? '');
	});

	if (changed) {
		checkVariations(form, variations, callbacks);
		return true;
	}

	return false;
}

function bindSwatchButtons(
	form: HTMLFormElement,
	variations: WcVariationRow[],
	callbacks: VariationFormCallbacks,
): void {
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
			form.querySelectorAll<HTMLSelectElement>('.variations select').forEach((field) => {
				field.value = '';
				triggerSelectChange(field);
			});
			updateSelectedLabel(form, attribute, '');
			checkVariations(form, variations, callbacks);
			return;
		}

		if (li.classList.contains('selected')) {
			if (requireSelection) {
				return;
			}

			list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
			select.value = '';
			triggerSelectChange(select);
			updateSelectedLabel(form, attribute, '');
			checkVariations(form, variations, callbacks);
			return;
		}

		list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
		li.classList.add('selected');
		select.value = value;
		triggerSelectChange(select);
		updateSelectedLabel(form, attribute, li.getAttribute('data-name') ?? '');
		checkVariations(form, variations, callbacks);
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
}

function shouldBindVariationForm(form: HTMLFormElement, root: HTMLElement): boolean {
	const modalRoot = form.closest<HTMLElement>(
		'[data-ts-quick-view-content], [data-ts-cart-item-edit-content]',
	);

	if (!modalRoot) {
		return true;
	}

	// Site-wide init (e.g. document.body) must skip modal forms; modal init passes the content root.
	return root === modalRoot;
}

/**
 * Re-run variation matching for a single form (e.g. after URL preselect).
 */
export function refreshVariationForm(
	form: HTMLFormElement,
	callbacks: VariationFormCallbacks = {},
): void {
	const variations = parseVariationsForm(form);
	if (variations.length < 1) {
		return;
	}

	checkVariations(form, variations, callbacks);
}

/**
 * Initialize variation swatch UI without wc-add-to-cart-variation / jQuery.
 */
export function initVariationForm(
	root: HTMLElement,
	callbacks: VariationFormCallbacks = {},
): void {
	root.querySelectorAll<HTMLFormElement>('form.variations_form').forEach((form) => {
		if (! shouldBindVariationForm(form, root)) {
			return;
		}

		const variations = parseVariationsForm(form);
		if (variations.length < 1) {
			return;
		}

		const runCheck = (): void => {
			checkVariations(form, variations, callbacks);
		};

		form.querySelectorAll<HTMLSelectElement>('select[name^="attribute_"]').forEach((select) => {
			select.addEventListener('change', runCheck);
		});

		bindSwatchButtons(form, variations, callbacks);
		runCheck();

		form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li.selected').forEach((selected) => {
			const attribute = selected.closest('.custom-variation-selector')?.getAttribute('data-attribute');
			if (attribute) {
				updateSelectedLabel(form, attribute, selected.getAttribute('data-name') ?? '');
			}
		});

		form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li').forEach((item) => {
			item.setAttribute('tabindex', '0');
			item.setAttribute('role', 'button');
		});
	});
}

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
