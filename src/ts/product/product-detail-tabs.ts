/**
 * Product detail tabs functionality
 * - Mobile accordion interaction
 * - Embedded size guide with unit toggle (reuses dialog structure from size-guide.ts)
 */

import type { SizeGuideConfig } from './types';
import { renderTableHTML } from './size-guide';

/**
 * Get size guide configuration
 */
function getSizeGuideConfig(): SizeGuideConfig | null {
	const cfg = window.tailwindscoreProduct?.sizeGuide;
	return cfg?.enabled ? cfg : null;
}

/**
 * Render size guide content (same as dialog body)
 * This reuses the exact same HTML structure as the modal dialog
 */
function renderSizeGuideContent(cfg: SizeGuideConfig, unit: 'cm' | 'inch', container: HTMLElement): void {
	// Create the same structure as .size-guide-dialog__inner
	let html = '';

	// Header with unit toggle (if applicable)
	if (cfg.showUnitSwitcher && cfg.table) {
		html += '<div class="size-guide-dialog__header">';
		html += '<div class="size-guide-dialog__controls">';
		html += '<div class="size-guide-unit-toggle" data-ts-size-guide-unit-toggle-embed>';
		html += `<button type="button" class="size-guide-unit-btn ${unit === 'cm' ? 'is-active' : ''}" data-unit="cm">cm</button>`;
		html += `<button type="button" class="size-guide-unit-btn ${unit === 'inch' ? 'is-active' : ''}" data-unit="inch">inch</button>`;
		html += '</div>';
		html += '</div>';
		html += '</div>';
	}

	// Body content
	html += '<div class="size-guide-dialog__body" data-ts-size-guide-body-embed></div>';

	container.innerHTML = html;
}

/**
 * Update body content based on unit
 * Reuses renderTableHTML from size-guide.ts
 */
function updateSizeGuideBody(cfg: SizeGuideConfig, unit: 'cm' | 'inch', bodyElement: HTMLElement): void {
	if (cfg.legacyHtml) {
		bodyElement.innerHTML = `<div class="size-guide-legacy">${cfg.legacyHtml}</div>`;
	} else if (cfg.table) {
		bodyElement.innerHTML = renderTableHTML(cfg.table, unit, false);
	}
}

/**
 * Initialize embedded size guide with unit toggle
 */
function initEmbeddedSizeGuide(): void {
	const cfg = getSizeGuideConfig();
	if (!cfg || !cfg.hasContent) {
		return;
	}

	const containers = document.querySelectorAll<HTMLElement>('[data-ts-product-size-guide-embed]');
	if (!containers.length) {
		return;
	}

	let currentUnit: 'cm' | 'inch' = cfg.defaultUnit === 'inch' ? 'inch' : 'cm';

	containers.forEach((container) => {
		// Render the structure
		renderSizeGuideContent(cfg, currentUnit, container);

		// Get body element
		const bodyElement = container.querySelector<HTMLElement>('[data-ts-size-guide-body-embed]');
		if (!bodyElement) return;

		// Update body content
		updateSizeGuideBody(cfg, currentUnit, bodyElement);

		// Setup unit toggle if present
		if (cfg.showUnitSwitcher && cfg.table) {
			const unitToggle = container.querySelector('[data-ts-size-guide-unit-toggle-embed]');
			if (unitToggle) {
				unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((btn) => {
					btn.addEventListener('click', () => {
						const newUnit = btn.getAttribute('data-unit') as 'cm' | 'inch';
						if (!newUnit) return;

						currentUnit = newUnit;

						// Update button states
						unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((b) => {
							b.classList.toggle('is-active', b.getAttribute('data-unit') === newUnit);
						});

						// Update body content
						updateSizeGuideBody(cfg, newUnit, bodyElement);
					});
				});
			}
		}
	});
}

/**
 * Initialize accordion functionality
 */
function initAccordion(): void {
	const triggers = document.querySelectorAll<HTMLButtonElement>('[data-ts-accordion-trigger]');

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', () => {
			const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
			const content = trigger.nextElementSibling as HTMLElement | null;

			if (!content || !content.hasAttribute('data-ts-accordion-content')) {
				return;
			}

			// Toggle current item
			if (isExpanded) {
				trigger.setAttribute('aria-expanded', 'false');
				content.setAttribute('hidden', '');
			} else {
				trigger.setAttribute('aria-expanded', 'true');
				content.removeAttribute('hidden');
			}
		});
	});
}

/**
 * Initialize product detail tabs
 */
export function initProductDetailTabs(): void {
	// Check if we're on a product page
	if (!document.querySelector('.product-detail-sections')) {
		return;
	}

	initEmbeddedSizeGuide();
	initAccordion();
}
