import type { GalleryImage } from './types';

import '../../scss/vendor/_photoswipe.scss';

const FALLBACK_SIZE = 1200;

export interface PhotoSwipeItem {
	src: string;
	w: number;
	h: number;
	title: string;
}

export interface GalleryLightboxMeta {
	productTitle?: string;
	attributeLabels?: Record<string, string>;
	variationsForm?: HTMLFormElement | null;
}

type PhotoSwipeInstance = {
	init: () => void;
	destroy: () => void;
	listen: (event: string, callback: () => void) => void;
};

type PhotoSwipeConstructor = new (
	element: HTMLElement,
	uiClass: unknown,
	items: PhotoSwipeItem[],
	options: Record<string, unknown>,
) => PhotoSwipeInstance;

let loadPromise: Promise<{
	PhotoSwipe: PhotoSwipeConstructor;
	PhotoSwipeUI_Default: unknown;
}> | null = null;

function resolveDimensions(img: GalleryImage): { w: number; h: number } {
	let w = img.width ?? 0;
	let h = img.height ?? 0;

	if (w > 0 && h > 0) {
		return { w, h };
	}

	if (w > 0) {
		return { w, h: w };
	}

	if (h > 0) {
		return { w: h, h };
	}

	return { w: FALLBACK_SIZE, h: FALLBACK_SIZE };
}

export function buildPhotoSwipeItems(images: GalleryImage[]): PhotoSwipeItem[] {
	const items: PhotoSwipeItem[] = [];

	for (const img of images) {
		const src = img.src?.trim();
		if (!src) {
			continue;
		}

		const { w, h } = resolveDimensions(img);
		items.push({
			src,
			w,
			h,
			title: img.alt?.trim() ?? '',
		});
	}

	return items;
}

function readSelectLabel(select: HTMLSelectElement): string {
	const option = select.options[select.selectedIndex];
	if (!option || '' === option.value) {
		return '';
	}
	return option.text.trim();
}

function findVariationSelect(form: HTMLFormElement, attrKey: string): HTMLSelectElement | null {
	const slug = attrKey.replace(/-/g, '_');
	const candidates = [
		`select[name="attribute_${attrKey}"]`,
		`select[name="attribute_${slug}"]`,
		`select[name="attribute_pa_${attrKey}"]`,
		`select[name="attribute_pa_${slug}"]`,
	];

	for (const selector of candidates) {
		const select = form.querySelector<HTMLSelectElement>(selector);
		if (select) {
			return select;
		}
	}

	return null;
}

/**
 * Selected variation attribute labels for lightbox caption (syncs with variations UI).
 */
export function buildVariationCaptionLines(
	form: HTMLFormElement | null | undefined,
	attributeLabels: Record<string, string> | undefined,
): string[] {
	if (!form || !attributeLabels || !Object.keys(attributeLabels).length) {
		return [];
	}

	const lines: string[] = [];

	for (const [attrKey, label] of Object.entries(attributeLabels)) {
		const display = form.querySelector<HTMLElement>(`.selected-attribute-value[data-for="${attrKey}"]`);
		let value = display?.textContent?.trim() ?? '';

		if (!value) {
			const select = findVariationSelect(form, attrKey);
			if (select) {
				value = readSelectLabel(select);
			}
		}

		if (value) {
			lines.push(`${label}: ${value}`);
		}
	}

	return lines;
}

function readProductTitleFromDom(): string {
	const el = document.querySelector<HTMLElement>(
		'.product_title, .entry-title, h1.title, .woocommerce-products-header__title',
	);
	return el?.textContent?.trim() ?? '';
}

function resolveLightboxMeta(meta?: GalleryLightboxMeta): GalleryLightboxMeta {
	return {
		productTitle: meta?.productTitle?.trim() || readProductTitleFromDom(),
		attributeLabels: meta?.attributeLabels,
		variationsForm:
			meta?.variationsForm ?? document.querySelector<HTMLFormElement>('form.variations_form'),
	};
}

function buildCaptionHtml(meta: GalleryLightboxMeta | undefined): string {
	const resolved = resolveLightboxMeta(meta);
	const title = resolved.productTitle?.trim() ?? '';
	const variationLines = buildVariationCaptionLines(
		resolved.variationsForm,
		resolved.attributeLabels,
	);
	const variationText = variationLines.join(' · ');

	if (!title && !variationText) {
		return '';
	}

	const parts: string[] = [];

	if (title) {
		parts.push(`<p class="pswp-caption__title">${escapeHtml(title)}</p>`);
	}

	if (variationText) {
		parts.push(`<p class="pswp-caption__variation">${escapeHtml(variationText)}</p>`);
	}

	return parts.join('');
}

function escapeHtml(text: string): string {
	return text
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function applyCaptionToItems(items: PhotoSwipeItem[], captionHtml: string): PhotoSwipeItem[] {
	if (!captionHtml) {
		return items;
	}

	return items.map((item) => ({
		...item,
		title: captionHtml,
	}));
}

async function loadPhotoSwipe(): Promise<{
	PhotoSwipe: PhotoSwipeConstructor;
	PhotoSwipeUI_Default: unknown;
}> {
	if (loadPromise) {
		return loadPromise;
	}

	loadPromise = (async () => {
		const [core, ui] = await Promise.all([
			import('photoswipe'),
			import('photoswipe/dist/photoswipe-ui-default'),
		]);

		const PhotoSwipe = (core.default ?? core) as PhotoSwipeConstructor;
		const PhotoSwipeUI_Default = ui.default ?? ui;

		return { PhotoSwipe, PhotoSwipeUI_Default };
	})();

	return loadPromise;
}

export interface GalleryLightboxController {
	open: (
		index: number,
		images: GalleryImage[],
		focusReturnEl?: HTMLElement | null,
		meta?: GalleryLightboxMeta,
	) => Promise<void>;
}

export function createLightboxController(): GalleryLightboxController {
	let lastFocusEl: HTMLElement | null = null;

	return {
		async open(
			index: number,
			images: GalleryImage[],
			focusReturnEl?: HTMLElement | null,
			meta?: GalleryLightboxMeta,
		): Promise<void> {
			const captionHtml = buildCaptionHtml(meta);
			const items = applyCaptionToItems(buildPhotoSwipeItems(images), captionHtml);
			if (!items.length) {
				return;
			}

			const element = document.querySelector<HTMLElement>('.pswp');
			if (!element) {
				return;
			}

			const safeIndex = Math.max(0, Math.min(index, items.length - 1));
			lastFocusEl = focusReturnEl ?? null;

			const { PhotoSwipe, PhotoSwipeUI_Default } = await loadPhotoSwipe();

			const instance = new PhotoSwipe(element, PhotoSwipeUI_Default, items, {
				index: safeIndex,
				showHideOpacity: true,
				timeToIdle: 0,
				history: false,
				arrowKeys: true,
				// PhotoSwipe UI reads item.title (HTML) for caption; must be non-empty to avoid caption--empty.
				addCaptionHTMLFn: (
					item: PhotoSwipeItem,
					captionEl: { children: HTMLCollectionOf<Element> },
				) => {
					const center = captionEl.children[0];
					if (!(center instanceof HTMLElement)) {
						return false;
					}

					if (!item.title) {
						center.textContent = '';
						return false;
					}

					center.innerHTML = item.title;
					return true;
				},
			});

			instance.listen('close', () => {
				const target = lastFocusEl;
				lastFocusEl = null;
				if (target && document.contains(target)) {
					target.focus({ preventScroll: true });
				}
			});

			instance.init();
		},
	};
}
