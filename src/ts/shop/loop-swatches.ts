/**
 * Product loop variant swatches: click to swap primary thumbnail on archive cards.
 */

const SWATCH_SELECTOR = '[data-product-card-swatches] .wc-product-card__swatch';
const CARD_SELECTOR = '[data-product-card]';
const MEDIA_SELECTOR = '[data-product-card-media]';
const PRIMARY_IMG_SELECTOR = 'img.wc-product-card__image--primary';

interface SwatchImageData {
	src?: string;
	srcset?: string;
	sizes?: string;
	alt?: string;
}

function parseImageData(btn: HTMLElement): SwatchImageData | null {
	const raw = btn.getAttribute('data-image');
	if (!raw) {
		return null;
	}
	try {
		return JSON.parse(raw) as SwatchImageData;
	} catch {
		return null;
	}
}

function applyImage(img: HTMLImageElement, data: SwatchImageData): void {
	if (!data.src) {
		return;
	}

	if (data.srcset) {
		img.setAttribute('srcset', data.srcset);
	} else {
		img.removeAttribute('srcset');
	}

	if (data.sizes) {
		img.setAttribute('sizes', data.sizes);
	} else {
		img.removeAttribute('sizes');
	}

	img.setAttribute('src', data.src);

	if (typeof data.alt === 'string') {
		img.setAttribute('alt', data.alt);
	}
}

function setActive(group: HTMLElement, activeBtn: HTMLButtonElement): void {
	const buttons = group.querySelectorAll<HTMLButtonElement>('.wc-product-card__swatch');
	buttons.forEach((b) => {
		const isActive = b === activeBtn;
		b.classList.toggle('is-active', isActive);
		b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
	});
}

function onActivate(e: Event): void {
	const target = e.target;
	if (!(target instanceof Element)) {
		return;
	}

	const btn = target.closest<HTMLButtonElement>(SWATCH_SELECTOR);
	if (!btn) {
		return;
	}

	e.preventDefault();
	e.stopPropagation();

	const card = btn.closest(CARD_SELECTOR);
	if (!card) {
		return;
	}

	const img = card.querySelector<HTMLImageElement>(PRIMARY_IMG_SELECTOR);
	const data = parseImageData(btn);
	if (img && data) {
		applyImage(img, data);
	}

	const media = card.querySelector(MEDIA_SELECTOR);
	if (media) {
		media.classList.add('has-active-swatch');
	}

	const group = btn.closest<HTMLElement>('[data-product-card-swatches]');
	if (group) {
		setActive(group, btn);
	}
}

function onKeydown(e: KeyboardEvent): void {
	if (e.key !== 'Enter' && e.key !== ' ') {
		return;
	}
	const target = e.target;
	if (!(target instanceof Element)) {
		return;
	}
	const btn = target.closest<HTMLButtonElement>(SWATCH_SELECTOR);
	if (!btn || btn.tagName === 'BUTTON') {
		return;
	}
	onActivate(e);
}

function init(): void {
	document.addEventListener('click', onActivate, false);
	document.addEventListener('keydown', onKeydown, false);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
	init();
}
