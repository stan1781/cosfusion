/**
 * Size guide modal and inline row for variable products.
 */

import type { SizeGuideConfig, SizeGuideRow, SizeGuideTable } from './types';
import { escapeHtml } from '../utils/escape-html';
import { bindBackdropClickToClose } from '../ui/dialog';

const CM_PER_INCH = 2.54;

function getConfig(): SizeGuideConfig | null {
	const cfg = window.tailwindscoreProduct?.sizeGuide;
	return cfg?.enabled ? cfg : null;
}

function formatNumber(n: number): string {
	const rounded = Math.round(n * 10) / 10;
	return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(1);
}

function convertLength(valueCm: number, unit: 'cm' | 'inch'): number {
	return unit === 'inch' ? valueCm / CM_PER_INCH : valueCm;
}

function tableHasLengthColumns(table: SizeGuideTable): boolean {
	return (table.columns ?? []).some((col) => col.type === 'length');
}

function renderTable(table: SizeGuideTable, unit: 'cm' | 'inch', showUnitLabel = false): string {
	const cols = table.columns ?? [];
	const rows = table.rows ?? [];
	if (!cols.length || !rows.length) {
		return '';
	}

	const showLabel = showUnitLabel && tableHasLengthColumns(table);
	let html = '<div class="size-guide-table-wrap">';
	if (showLabel) {
		html += `<p class="size-guide-table__unit-label">${escapeHtml(unit)}</p>`;
	}
	html += '<table class="size-guide-table"><thead><tr>';
	for (const col of cols) {
		html += `<th class="size-guide-table__head">${escapeHtml(col.label)}</th>`;
	}
	html += '</tr></thead><tbody>';

	for (const row of rows) {
		html += `<tr data-size-key="${escapeAttr(row.sizeKey)}">`;
		for (const col of cols) {
			html += '<td class="size-guide-table__cell">';
			if (col.type === 'size') {
				html += escapeHtml(row.sizeLabel || row.sizeKey);
			} else {
				const val = row.values?.[col.key];
				if (col.type === 'length' && typeof val === 'number') {
					html += escapeHtml(formatNumber(convertLength(val, unit)));
				} else if (val != null && val !== '') {
					html += escapeHtml(String(val));
				}
			}
			html += '</td>';
		}
		html += '</tr>';
	}
	html += '</tbody></table></div>';
	return html;
}

function renderInlineRow(row: SizeGuideRow, table: SizeGuideTable, unit: 'cm' | 'inch'): string {
	const parts: string[] = [];
	let hasLength = false;
	for (const col of table.columns) {
		if (col.type === 'size') {
			continue;
		}
		const val = row.values?.[col.key];
		if (val == null || val === '') {
			continue;
		}
		let display = String(val);
		if (col.type === 'length' && typeof val === 'number') {
			hasLength = true;
			display = formatNumber(convertLength(val, unit));
		}
		parts.push(`<span class="size-guide-inline__item"><strong>${escapeHtml(col.label)}</strong> ${escapeHtml(display)}</span>`);
	}
	if (!parts.length) {
		return '';
	}
	let html = `<span class="size-guide-inline__items">${parts.join('')}</span>`;
	if (hasLength) {
		html += `<span class="size-guide-inline__unit">(${escapeHtml(unit)})</span>`;
	}
	return html;
}

function escapeAttr(s: string): string {
	return s.replace(/"/g, '&quot;');
}

function normalizeSlug(value: string): string {
	return value
		.toLowerCase()
		.trim()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '');
}

function syncUnitToggle(unitToggle: Element | null, unit: 'cm' | 'inch'): void {
	if (!unitToggle) {
		return;
	}
	unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((btn) => {
		btn.classList.toggle('is-active', btn.getAttribute('data-unit') === unit);
	});
}

function initModal(cfg: SizeGuideConfig, getUnit: () => 'cm' | 'inch', setUnit: (u: 'cm' | 'inch') => void): void {
	const dialog = document.getElementById('ts-size-guide-dialog') as HTMLDialogElement | null;
	const body = document.querySelector('[data-ts-size-guide-body]');
	const unitToggle = document.querySelector('[data-ts-size-guide-unit-toggle]');
	if (!dialog || !body) {
		console.warn('[tailwindscore] size guide: dialog shell missing in DOM');
		return;
	}

	const updateBody = (): void => {
		const unit = getUnit();
		if (cfg.legacyHtml) {
			body.innerHTML = `<div class="size-guide-legacy">${cfg.legacyHtml}</div>`;
			return;
		}
		if (cfg.table) {
			body.innerHTML = renderTable(cfg.table, unit, !cfg.showUnitSwitcher);
		}
	};

	if (cfg.showUnitSwitcher && cfg.table && unitToggle) {
		unitToggle.classList.remove('is-hidden');
		syncUnitToggle(unitToggle, getUnit());
		unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((btn) => {
			btn.addEventListener('click', () => {
				const u = btn.getAttribute('data-unit') as 'cm' | 'inch';
				if (!u) {
					return;
				}
				setUnit(u);
				syncUnitToggle(unitToggle, u);
				updateBody();
				updateInline(cfg, getUnit());
			});
		});
	}

	document.querySelectorAll('[data-ts-size-guide-open]').forEach((btn) => {
		btn.addEventListener('click', () => {
			updateBody();
			dialog.showModal();
		});
	});

	document.querySelectorAll('[data-ts-size-guide-close]').forEach((btn) => {
		btn.addEventListener('click', () => dialog.close());
	});

	bindBackdropClickToClose(dialog, () => dialog.close());

	updateBody();
}

function findSizeRow(): HTMLElement | null {
	return document.querySelector('.variation-row--size');
}

function getSelectedSizeSlug(form: HTMLFormElement, bindAttr: string): string {
	const slug = bindAttr.replace(/^attribute_/, '');
	const selectors = [`select[name="attribute_${slug}"]`, `select[name="attribute_pa_${slug}"]`, `select[name="attribute_${bindAttr}"]`];
	for (const sel of selectors) {
		const select = form.querySelector<HTMLSelectElement>(sel);
		if (select?.value) {
			return normalizeSlug(select.value);
		}
	}
	const list = form.querySelector<HTMLElement>(`.custom-variation-selector[data-attribute="${slug}"]`);
	const selected = list?.querySelector('li.selected');
	const val = selected?.getAttribute('data-value');
	return val ? normalizeSlug(val) : '';
}

function updateInline(cfg: SizeGuideConfig, unit: 'cm' | 'inch'): void {
	const inline = document.querySelector('[data-ts-size-guide-inline]');
	if (!inline || !cfg.inlineEnabled) {
		return;
	}

	if (cfg.legacyHtml || !cfg.table) {
		inline.setAttribute('hidden', '');
		inline.innerHTML = '';
		return;
	}

	const form = document.querySelector<HTMLFormElement>('form.variations_form');
	if (!form) {
		return;
	}

	const slug = getSelectedSizeSlug(form, cfg.bindAttribute);
	const row = slug ? cfg.rowsBySizeKey[slug] : undefined;

	if (!row) {
		inline.setAttribute('hidden', '');
		inline.innerHTML = '';
		return;
	}

	const html = renderInlineRow(row, cfg.table, unit);
	if (!html) {
		inline.setAttribute('hidden', '');
		return;
	}

	inline.innerHTML = html;
	inline.removeAttribute('hidden');
}

function bindVariationListeners(cfg: SizeGuideConfig, getUnit: () => 'cm' | 'inch'): void {
	const form = document.querySelector<HTMLFormElement>('form.variations_form');
	if (!form) {
		return;
	}

	const onChange = (): void => updateInline(cfg, getUnit());

	form.addEventListener('click', (e) => {
		const t = e.target;
		if (!(t instanceof Element)) {
			return;
		}
		if (t.closest('.custom-variation-selector') || t.closest('select[name^="attribute_"]')) {
			window.setTimeout(onChange, 30);
		}
	});

	form.querySelectorAll<HTMLSelectElement>('select[name^="attribute_"]').forEach((sel) => {
		sel.addEventListener('change', onChange);
	});

	onChange();
}

export function initSizeGuide(): void {
	const cfg = getConfig();
	if (!cfg) {
		if (document.querySelector('[data-ts-size-guide-open]')) {
			console.warn(
				'[tailwindscore] size guide button present but window.tailwindscoreProduct.sizeGuide is missing. Rebuild theme assets (npm run build) and ensure the product has a size guide.',
			);
		}
		return;
	}
	if (!cfg.hasContent && cfg.displayMode !== 'always') {
		return;
	}

	let currentUnit: 'cm' | 'inch' = cfg.defaultUnit === 'inch' ? 'inch' : 'cm';
	const getUnit = (): 'cm' | 'inch' => currentUnit;
	const setUnit = (u: 'cm' | 'inch'): void => {
		currentUnit = u;
	};

	initModal(cfg, getUnit, setUnit);
	if (cfg.inlineEnabled && cfg.table) {
		bindVariationListeners(cfg, getUnit);
	}
}

/**
 * Export renderTable for reuse in product-detail-tabs
 */
export function renderTableHTML(table: SizeGuideTable, unit: 'cm' | 'inch', showUnitLabel = false): string {
	return renderTable(table, unit, showUnitLabel);
}
