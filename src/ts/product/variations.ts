/**
 * Build sticky bar attribute summary from visible selection UI (color, size, etc.).
 */
function collectStickyAttributeSummary(form: HTMLFormElement): string {
	const parts: string[] = [];

	form.querySelectorAll<HTMLElement>('.selected-attribute-value').forEach((el) => {
		const text = el.textContent?.trim() ?? '';

		if (text) {
			parts.push(text);
		}
	});

	if (parts.length > 0) {
		return parts.join(' / ');
	}

	form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li.selected').forEach((li) => {
		const name = li.getAttribute('data-name')?.trim() ?? li.textContent?.trim() ?? '';

		if (name) {
			parts.push(name);
		}
	});

	return parts.join(' / ');
}

function syncStickyAttrsFromForm(form: HTMLFormElement): void {
	const attrsEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-attrs]');

	if (!attrsEl) {
		return;
	}

	const summary = collectStickyAttributeSummary(form);
	attrsEl.textContent = summary || 'Select options';
}

/**
 * Sync sticky purchase bar meta with variation state.
 */
function syncStickyPurchaseBar(
	variation?: {
		attributes?: Record<string, string>;
		price_html?: string;
		image?: { thumb_src?: string; src?: string };
	},
	reset = false,
	originalPriceHtml: string | null = null,
	form?: HTMLFormElement | null,
): void {
	const attrsEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-attrs]');
	const priceEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-price]');
	const thumbEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-thumb]');
	const targetForm = form ?? document.querySelector<HTMLFormElement>('form.variations_form');

	if (reset) {
		if (attrsEl) {
			attrsEl.textContent = 'Select options';
		}
		if (priceEl && originalPriceHtml !== null) {
			priceEl.innerHTML = originalPriceHtml;
		}
		if (thumbEl) {
			const originalSrc = thumbEl.dataset.tsOriginalThumbSrc;
			const img = thumbEl.querySelector<HTMLImageElement>('img');
			if (img && originalSrc) {
				img.src = originalSrc;
			}
		}
		return;
	}

	if (targetForm) {
		syncStickyAttrsFromForm(targetForm);
	} else if (variation?.attributes && attrsEl) {
		const values = Object.values(variation.attributes).filter((value) => Boolean(value));
		attrsEl.textContent = values.length > 0 ? values.join(' / ') : 'Select options';
	}

	if (variation?.price_html && priceEl) {
		priceEl.innerHTML = variation.price_html;
	}

	const imgSrc = variation?.image?.thumb_src ?? variation?.image?.src;
	if (imgSrc && thumbEl) {
		const img = thumbEl.querySelector<HTMLImageElement>('img');
		if (img) {
			img.src = imgSrc;
		}
	}
}

function initStickyThumbOriginal(): void {
	const thumbEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-thumb]');
	const img = thumbEl?.querySelector<HTMLImageElement>('img');

	if (thumbEl && img?.src) {
		thumbEl.dataset.tsOriginalThumbSrc = img.src;
	}
}

/**
 * Custom variation selector (syncs with wc-add-to-cart-variation).
 */

import type { VariationSettings, VariationsI18n, WcVariationRow } from './types';
import {
	findAttributeSelect,
	initVariationForm,
	refreshVariationForm,
	type VariationFormCallbacks,
} from './variation-engine';

function isVariationSelectorEnabled(settings: VariationSettings | undefined): boolean {
	if (!settings) {
		return false;
	}
	const enabled = settings.enabled;
	return enabled !== false && enabled !== 0 && enabled !== '0' && enabled !== '';
}

const DEFAULT_I18N: VariationsI18n = {
	list: 'List',
	grid: 'Grid',
	switchToList: 'Switch to list mode',
	switchToGrid: 'Switch to grid mode',
};

function parseFormConfig(form: HTMLFormElement): { settings: VariationSettings; i18n: VariationsI18n } | null {
	const raw = form.getAttribute('data-tailwindscore-variations-config');
	if (!raw) {
		return null;
	}
	try {
		const parsed = JSON.parse(raw) as {
			settings?: VariationSettings;
			i18n?: VariationsI18n;
		};
		if (!parsed.settings || !isVariationSelectorEnabled(parsed.settings)) {
			return null;
		}
		return {
			settings: parsed.settings,
			i18n: parsed.i18n ?? DEFAULT_I18N,
		};
	} catch {
		return null;
	}
}

function getConfig(): { settings: VariationSettings; i18n: VariationsI18n } | null {
	const form = document.querySelector<HTMLFormElement>(
		'form.variations_form[data-tailwindscore-variations-config]',
	);
	if (form) {
		const fromForm = parseFormConfig(form);
		if (fromForm) {
			return fromForm;
		}
	}

	const root = window.tailwindscoreProduct;
	const settings = root?.variations;
	if (isVariationSelectorEnabled(settings)) {
		return {
			settings: settings!,
			i18n: root?.variationsI18n ?? DEFAULT_I18N,
		};
	}

	if (document.querySelector('form.variations_form .custom-variation-selector')) {
		const swatchForm = document.querySelector<HTMLFormElement>('form.variations_form');
		const modeAttr = swatchForm?.getAttribute('data-ts-color-mode') === 'list' ? 'list' : 'grid';
		return {
			settings: {
				enabled: true,
				colorAttributes: [],
				sizeAttributes: [],
				colorSelectorDefaultMode: modeAttr,
				colorSelectorShape: 'square',
				gridShowText: false,
				priceSelector: '.wc-product-detail__summary .price, .entry-summary .price',
			},
			i18n: DEFAULT_I18N,
		};
	}

	return null;
}

function triggerSelectChange(select: HTMLSelectElement): void {
	select.dispatchEvent(new Event('change', { bubbles: true }));
}

function slugify(val: string): string {
	try {
		val = decodeURIComponent(val);
	} catch {
		/* ignore */
	}
	return val.trim().toLowerCase().replace(/\s+/g, '-');
}

function refreshToggleButton(toggle: Element, currentMode: 'grid' | 'list', i18n: VariationsI18n): void {
	const btn = toggle.querySelector<HTMLButtonElement>('.display-mode-btn');
	const label = toggle.querySelector('.display-mode-btn-label');
	if (!btn || !label) {
		return;
	}
	const validMode = currentMode === 'list' ? 'list' : 'grid';
	const nextMode = validMode === 'grid' ? 'list' : 'grid';
	btn.dataset.currentMode = validMode;
	btn.setAttribute('aria-label', nextMode === 'list' ? i18n.switchToList : i18n.switchToGrid);
	label.textContent = nextMode === 'list' ? i18n.list : i18n.grid;
}

function updateSelectedAttributeDisplay(form: Element, attributeName: string, selectedName: string): void {
	const el = form.querySelector(`.selected-attribute-value[data-for="${attributeName}"]`);
	if (el) {
		el.textContent = selectedName;
	}
}

function syncDisabledFromSelects(form: Element): void {
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
			li.classList.toggle('disabled', !!option?.disabled);
		});
	});
}

function initVariationSelector(): void {
	const config = getConfig();
	if (!config) {
		return;
	}

	const forms = Array.from(document.querySelectorAll<HTMLFormElement>('form.variations_form'));
	if (!forms.length) {
		return;
	}

	const { settings, i18n } = config;
	const priceEl = document.querySelector<HTMLElement>(settings.priceSelector);
	const originalPriceHtml = priceEl?.innerHTML ?? null;
	const stickyPriceEl = document.querySelector<HTMLElement>('[data-ts-product-sticky-price]');
	const stickyOriginalPriceHtml = stickyPriceEl?.innerHTML ?? null;
	initStickyThumbOriginal();
	const globalMode = settings.colorSelectorDefaultMode === 'list' ? 'list' : 'grid';

	const callbacks: VariationFormCallbacks = {
		onShowVariation(variation: WcVariationRow) {
			if (variation.attributes) {
				for (const attribute of Object.keys(variation.attributes)) {
					const attributeName = attribute.replace('attribute_', '');
					const val = variation.attributes[attribute];
					if (!val) {
						continue;
					}
					const selector = document.querySelector(
						`form.variations_form .custom-variation-selector[data-attribute="${attributeName}"]`,
					);
					if (!selector) {
						continue;
					}
					selector.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
					const selectedLi = selector.querySelector<HTMLLIElement>(`li[data-value="${CSS.escape(val)}"]`);
					if (selectedLi) {
						const form = selector.closest('form.variations_form');
						selectedLi.classList.add('selected');
						if (form) {
							updateSelectedAttributeDisplay(form, attributeName, selectedLi.dataset.name ?? '');
						}
					}
				}
			}

			if (priceEl && variation.price_html) {
				priceEl.innerHTML = variation.price_html;
			}
			syncStickyPurchaseBar(variation, false, stickyOriginalPriceHtml);
		},
		onHideVariation() {
			document.querySelectorAll('form.variations_form .custom-variation-selector li').forEach((item) => {
				item.classList.remove('selected');
			});
			document.querySelectorAll('form.variations_form .selected-attribute-value').forEach((el) => {
				el.textContent = '';
			});
			if (priceEl && originalPriceHtml !== null) {
				priceEl.innerHTML = originalPriceHtml;
			}
			syncStickyPurchaseBar(undefined, true, stickyOriginalPriceHtml);
		},
	};

	function initDisplayModes(): void {
		forms.forEach((form) => {
			const modeAttr = form.getAttribute('data-ts-color-mode');
			const validMode: 'grid' | 'list' =
				modeAttr === 'list' || modeAttr === 'grid' ? modeAttr : globalMode;

			form.querySelectorAll<HTMLElement>('.custom-variation-selector.color-selector').forEach((selector) => {
				selector.classList.remove('mode-grid', 'mode-list');
				selector.classList.add(`mode-${validMode}`);
			});

			form.querySelectorAll<HTMLElement>('.variation-display-toggle').forEach((toggle) => {
				const attribute = toggle.getAttribute('data-attribute');
				const selector = attribute
					? form.querySelector<HTMLElement>(
							`.custom-variation-selector.color-selector[data-attribute="${attribute}"]`,
						)
					: null;
				let currentMode = validMode;
				if (selector?.classList.contains('mode-list')) {
					currentMode = 'list';
				} else if (selector?.classList.contains('mode-grid')) {
					currentMode = 'grid';
				}
				refreshToggleButton(toggle, currentMode, i18n);
			});
		});
	}

	function syncAllSelectedStates(): void {
		forms.forEach((form) => {
			form.querySelectorAll<HTMLElement>('.custom-variation-selector').forEach((list) => {
				const attribute = list.getAttribute('data-attribute');
				if (!attribute) {
					return;
				}
				const select = findAttributeSelect(form, attribute);
				const currentValue = select?.value ?? '';

				list.querySelectorAll('li').forEach((li) => li.classList.remove('selected'));

				if (currentValue) {
					const selectedLi =
						list.querySelector<HTMLLIElement>(`li[data-value="${CSS.escape(currentValue)}"]`) ??
						Array.from(list.querySelectorAll<HTMLLIElement>('li')).find(
							(li) => li.dataset.value === currentValue || li.dataset.name === currentValue,
						);
					if (selectedLi) {
						selectedLi.classList.add('selected');
						updateSelectedAttributeDisplay(form, attribute, selectedLi.dataset.name ?? '');
					}
				} else {
					updateSelectedAttributeDisplay(form, attribute, '');
				}
			});
			syncDisabledFromSelects(form);
			syncStickyAttrsFromForm(form);
		});
	}

	function preselectFromURL(): void {
		const params = new URLSearchParams(window.location.search);
		if (!params.toString()) {
			return;
		}

		forms.forEach((form) => {
			form.querySelectorAll<HTMLSelectElement>('.variations select[name^="attribute_"]').forEach((select) => {
				const name = select.getAttribute('name') ?? '';
				const attrKey = name.replace(/^attribute_/, '');
				const candidateKeys = [name, attrKey, `attribute_${attrKey}`];
				if (attrKey.startsWith('pa_')) {
					candidateKeys.push(attrKey.replace(/^pa_/, ''));
				}

				let foundValue: string | null = null;
				for (const key of candidateKeys) {
					if (params.has(key)) {
						foundValue = params.get(key);
						break;
					}
				}

				if (!foundValue) {
					return;
				}

				let option = select.querySelector<HTMLOptionElement>(`option[value="${foundValue}"]`);
				if (!option) {
					const sVal = slugify(foundValue);
					if (sVal) {
						option = select.querySelector<HTMLOptionElement>(`option[value="${sVal}"]`);
						if (option) {
							foundValue = sVal;
						}
					}
				}
				if (!option) {
					const targetText = foundValue.trim().toLowerCase();
					for (const opt of Array.from(select.querySelectorAll<HTMLOptionElement>('option'))) {
						if (opt.textContent?.trim().toLowerCase() === targetText) {
							option = opt;
							foundValue = opt.value;
							break;
						}
					}
				}

				if (option && foundValue) {
					select.value = foundValue;
					triggerSelectChange(select);
				}
			});

			refreshVariationForm(form, callbacks);
		});
	}

	document.addEventListener(
		'click',
		(event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}
		const btn = target.closest('.variation-display-toggle .display-mode-btn');
		if (!btn) {
			return;
		}
		event.preventDefault();
		const toggle = btn.closest('.variation-display-toggle');
		const form = btn.closest('form.variations_form');
		if (!toggle || !form) {
			return;
		}
		const attribute = toggle.getAttribute('data-attribute');
		const selector = attribute
			? form.querySelector<HTMLElement>(
					`.custom-variation-selector.color-selector[data-attribute="${attribute}"]`,
				)
			: null;
		if (!selector) {
			return;
		}
		const currentMode = btn.getAttribute('data-current-mode') === 'list' ? 'list' : 'grid';
		const mode = currentMode === 'grid' ? 'list' : 'grid';
		selector.classList.remove('mode-grid', 'mode-list');
		selector.classList.add(`mode-${mode}`);
		refreshToggleButton(toggle, mode, i18n);
		},
		true,
	);

	initVariationForm(document.body, callbacks);
	preselectFromURL();
	initDisplayModes();
	syncAllSelectedStates();
}

function bootVariations(): void {
	if (!document.querySelector('form.variations_form .custom-variation-selector')) {
		return;
	}
	initVariationSelector();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', bootVariations);
} else {
	bootVariations();
}

export {};
