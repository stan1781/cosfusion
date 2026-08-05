import type { VariationsI18n } from './types';

export type MissingAttributeInfo = {
	select: HTMLSelectElement;
	row: HTMLElement | null;
	label: string;
};

const INCOMPLETE_CLASS = 'is-variation-incomplete';
const DEFAULT_SELECT_ATTRIBUTE = 'Select %s';

export function getSelectAttributeTemplate(i18n?: Partial<VariationsI18n> | null): string {
	return i18n?.selectAttribute || DEFAULT_SELECT_ATTRIBUTE;
}

export function formatSelectAttributeLabel(
	attrLabel: string,
	i18n?: Partial<VariationsI18n> | null,
): string {
	const template = getSelectAttributeTemplate(i18n);
	if (template.includes('%s')) {
		return template.replace('%s', attrLabel);
	}
	return `${template} ${attrLabel}`.trim();
}

export function isVariationReady(form: HTMLFormElement): boolean {
	const variationId = Number(
		form.querySelector<HTMLInputElement>('input.variation_id, input[name="variation_id"]')?.value ??
			'0',
	);
	return variationId > 0;
}

export function getFirstMissingAttribute(form: HTMLFormElement): MissingAttributeInfo | null {
	const selects = Array.from(
		form.querySelectorAll<HTMLSelectElement>('select[name^="attribute_"]'),
	);

	for (const select of selects) {
		if ((select.value || '').length > 0) {
			continue;
		}

		const row = select.closest<HTMLElement>('.variation-row');
		const label =
			row?.getAttribute('data-ts-attribute-label')?.trim() ||
			select.getAttribute('data-attribute_name')?.replace(/^attribute_/, '') ||
			'options';

		return { select, row, label };
	}

	return null;
}

function ensureDefaultLabel(button: HTMLButtonElement): string {
	const stored = button.dataset.tsAtcDefaultLabel?.trim();
	if (stored) {
		return stored;
	}

	const current = (button.textContent || '').replace(/\s+/g, ' ').trim();
	button.dataset.tsAtcDefaultLabel = current;
	return current;
}

function setButtonIncomplete(button: HTMLButtonElement, label: string): void {
	ensureDefaultLabel(button);
	button.classList.add(INCOMPLETE_CLASS);
	button.setAttribute('aria-disabled', 'true');
	button.textContent = label;
}

function setButtonReady(button: HTMLButtonElement, label: string): void {
	ensureDefaultLabel(button);
	button.classList.remove(INCOMPLETE_CLASS);
	button.removeAttribute('aria-disabled');
	button.textContent = label;
}

function getMainAtcButton(form: HTMLFormElement): HTMLButtonElement | null {
	return form.querySelector<HTMLButtonElement>('button.single_add_to_cart_button');
}

function getStickyAtcButton(): HTMLButtonElement | null {
	return document.querySelector<HTMLButtonElement>('[data-ts-product-sticky-atc]');
}

/**
 * 同步主加购与 sticky 按钮的就绪态 / 文案（就绪时均为默认「Add to cart」，不含价格）。
 */
export function syncVariationAtcButtons(
	form: HTMLFormElement,
	i18n?: Partial<VariationsI18n> | null,
): void {
	const main = getMainAtcButton(form);
	const sticky = getStickyAtcButton();
	const buttons = [main, sticky].filter((btn): btn is HTMLButtonElement => Boolean(btn));

	if (!buttons.length) {
		return;
	}

	const ready = isVariationReady(form);
	const missing = getFirstMissingAttribute(form);

	if (!ready) {
		const incompleteLabel = missing
			? formatSelectAttributeLabel(missing.label, i18n)
			: ensureDefaultLabel(buttons[0]);

		for (const button of buttons) {
			setButtonIncomplete(button, incompleteLabel);
		}
		return;
	}

	for (const button of buttons) {
		setButtonReady(button, ensureDefaultLabel(button));
	}
}

export function scrollToMissingAttribute(form: HTMLFormElement): void {
	const missing = getFirstMissingAttribute(form);
	const target =
		missing?.row ??
		form.querySelector<HTMLElement>('.variation-row') ??
		form.querySelector<HTMLElement>('.custom-variation-selector');

	if (!target) {
		return;
	}

	target.scrollIntoView({ behavior: 'smooth', block: 'start' });

	const focusable =
		target.querySelector<HTMLElement>('li[tabindex="0"]:not(.disabled), select, button') ??
		missing?.select ??
		null;

	if (focusable) {
		window.requestAnimationFrame(() => {
			focusable.focus();
		});
	}
}

/**
 * 未就绪时滚到缺失属性并返回 true；已就绪返回 false。
 */
export function guideIncompleteVariation(form: HTMLFormElement): boolean {
	if (isVariationReady(form)) {
		return false;
	}

	scrollToMissingAttribute(form);
	return true;
}

export function resolveVariationsI18n(
	form?: HTMLFormElement | null,
): Partial<VariationsI18n> {
	const raw = form?.getAttribute('data-tailwindscore-variations-config');
	if (raw) {
		try {
			const parsed = JSON.parse(raw) as { i18n?: Partial<VariationsI18n> };
			if (parsed.i18n) {
				return parsed.i18n;
			}
		} catch {
			/* ignore */
		}
	}

	return window.tailwindscoreProduct?.variationsI18n ?? {};
}
