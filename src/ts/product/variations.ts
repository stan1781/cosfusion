/**
 * Custom variation selector (syncs with wc-add-to-cart-variation).
 */

import type { VariationSettings, VariationsI18n } from './types';

type JqForm = {
	trigger: (event: string) => JqForm;
	on: (events: string, handler: (...args: unknown[]) => void) => JqForm;
};

type JQueryLike = (target: Document | Element) => JqForm;

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
				priceSelector: '.ts-wc-product-detail__summary .price, .entry-summary .price',
			},
			i18n: DEFAULT_I18N,
		};
	}

	return null;
}

function findAttributeSelect(form: Element, attribute: string): HTMLSelectElement | null {
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

	return null;
}

function getJQuery(): JQueryLike | undefined {
	return (window as Window & { jQuery?: JQueryLike }).jQuery;
}

function waitForJQuery(timeoutMs = 8000): Promise<JQueryLike> {
	return new Promise((resolve, reject) => {
		const started = Date.now();
		const tick = (): void => {
			const jq = getJQuery();
			if (jq) {
				resolve(jq);
				return;
			}
			if (Date.now() - started >= timeoutMs) {
				reject(new Error('jQuery not available'));
				return;
			}
			window.setTimeout(tick, 50);
		};
		tick();
	});
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

function triggerSelectChange(select: HTMLSelectElement, jq?: JQueryLike): void {
	if (jq) {
		jq(select).trigger('change');
		return;
	}
	select.dispatchEvent(new Event('change', { bubbles: true }));
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

async function initVariationSelector(): Promise<void> {
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
	const globalMode = settings.colorSelectorDefaultMode === 'list' ? 'list' : 'grid';

	let jq: JQueryLike | undefined;
	try {
		jq = await waitForJQuery();
	} catch {
		console.warn('[tailwindscore] variations: jQuery unavailable; limited WooCommerce sync.');
	}

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
					triggerSelectChange(select, jq);
				}
			});

			if (jq) {
				jq(form).trigger('woocommerce_update_variation_values');
				jq(form).trigger('check_variations');
			}
		});
	}

	document.addEventListener(
		'click',
		(event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}
		const li = target.closest('.variations_form .variations .custom-variation-selector li');
		if (!li) {
			return;
		}

		const form = li.closest('form.variations_form');
		if (!form) {
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

		if (li.classList.contains('disabled')) {
			form.querySelectorAll('.custom-variation-selector li').forEach((item) => {
				item.classList.remove('selected');
			});
			form.querySelectorAll('.selected-attribute-value').forEach((el) => {
				el.textContent = '';
			});
			form.querySelectorAll<HTMLSelectElement>('.variations select').forEach((s) => {
				s.value = '';
				triggerSelectChange(s, jq);
			});
			return;
		}

		if (li.classList.contains('selected')) {
			list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
			if (select) {
				select.value = '';
				triggerSelectChange(select, jq);
			}
			updateSelectedAttributeDisplay(form, attribute, '');
			return;
		}

		list.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
		li.classList.add('selected');
		if (select) {
			select.value = value;
			triggerSelectChange(select, jq);
		}
		updateSelectedAttributeDisplay(form, attribute, li.getAttribute('data-name') ?? '');

		window.setTimeout(() => syncAllSelectedStates(), 50);
		},
		true,
	);

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

	forms.forEach((form) => {
		form.addEventListener('change', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLSelectElement) || !target.name.startsWith('attribute_')) {
				return;
			}
			syncAllSelectedStates();
		});

		if (jq) {
			const $form = jq(form);

			$form.on('woocommerce_update_variation_values', () => {
				syncAllSelectedStates();
			});

			$form.on('show_variation', (...args: unknown[]) => {
				const variation = args[1] as
					| { attributes?: Record<string, string>; price_html?: string }
					| undefined;
				if (!variation?.attributes) {
					return;
				}
				for (const attribute of Object.keys(variation.attributes)) {
					const attributeName = attribute.replace('attribute_', '');
					const val = variation.attributes[attribute];
					if (!val) {
						continue;
					}
					const selector = form.querySelector(
						`.custom-variation-selector[data-attribute="${attributeName}"]`,
					);
					if (!selector) {
						continue;
					}
					selector.querySelectorAll('li').forEach((item) => item.classList.remove('selected'));
					const selectedLi = selector.querySelector<HTMLLIElement>(
						`li[data-value="${CSS.escape(val)}"]`,
					);
					if (selectedLi) {
						selectedLi.classList.add('selected');
						updateSelectedAttributeDisplay(form, attributeName, selectedLi.dataset.name ?? '');
					}
				}
				if (priceEl && variation.price_html) {
					priceEl.innerHTML = variation.price_html;
				}
			});

			$form.on('hide_variation', () => {
				form.querySelectorAll('.custom-variation-selector li').forEach((item) => {
					item.classList.remove('selected');
				});
				form.querySelectorAll('.selected-attribute-value').forEach((el) => {
					el.textContent = '';
				});
				if (priceEl && originalPriceHtml !== null) {
					priceEl.innerHTML = originalPriceHtml;
				}
			});

			$form.trigger('check_variations');
		}

		form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li.selected').forEach((selected) => {
			const attributeName = selected.closest('.custom-variation-selector')?.getAttribute('data-attribute');
			const selectedName = selected.getAttribute('data-name');
			if (attributeName && selectedName) {
				updateSelectedAttributeDisplay(form, attributeName, selectedName);
			}
		});

		form.querySelectorAll<HTMLLIElement>('.custom-variation-selector li').forEach((item) => {
			item.setAttribute('tabindex', '0');
			item.setAttribute('role', 'button');
		});
	});

	preselectFromURL();
	initDisplayModes();
	syncAllSelectedStates();
}

function bootVariations(): void {
	if (!document.querySelector('form.variations_form .custom-variation-selector')) {
		return;
	}
	void initVariationSelector();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', bootVariations);
} else {
	bootVariations();
}

export {};
