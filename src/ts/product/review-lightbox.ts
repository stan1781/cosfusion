import Swiper from 'swiper';
import { A11y, Thumbs } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import type { TailwindscoreReviewImagesI18n } from './types';

import 'swiper/css';
import 'swiper/css/thumbs';

import { bindSwiperWrapNavigation } from '../swiper/wrap-navigation';

export interface ReviewLightboxImage {
	full: string;
	thumb: string;
	id?: number;
}

export interface ReviewLightboxSlideMeta {
	id?: number;
	author?: string;
	date?: string;
	rating?: number;
	content?: string;
	country_code?: string;
	country_name?: string;
	country_flag_url?: string;
	verified?: boolean;
}

export interface ReviewLightboxPayload {
	images: ReviewLightboxImage[];
	slides?: ReviewLightboxSlideMeta[];
}

let dialogEl: HTMLDialogElement | null = null;
let mainSwiper: SwiperInstance | null = null;
let thumbsSwiper: SwiperInstance | null = null;
let wrapNavCleanup: (() => void) | null = null;
let productLightboxCache: ReviewLightboxPayload | null | undefined;

function getReviewImagesCloseIcon(): string {
	const wrapper = document.querySelector('#review-images-upload-wrapper');
	const raw = wrapper?.getAttribute('data-tailwindscore-review-images-config');

	if (raw) {
		try {
			const cfg = JSON.parse(raw) as { reviewImages?: { icons?: { close?: string } } };
			if (cfg.reviewImages?.icons?.close) {
				return cfg.reviewImages.icons.close;
			}
		} catch {
			// Fall through to window config.
		}
	}

	return window.tailwindscoreProduct?.reviewImages?.icons?.close ?? '';
}

function renderOverlayCloseButton(label: string, closeIcon: string): string {
	const iconMarkup = closeIcon.trim()
		? `<span class="icon" aria-hidden="true">${closeIcon}</span>`
		: '<span class="icon" aria-hidden="true">×</span>';

	return `<button type="button" class="btn-close review-lightbox__close" data-action="close" aria-label="${escapeHtml(label)}">${iconMarkup}<span class="screen-reader-text">${escapeHtml(label)}</span></button>`;
}

function escapeHtml(text: string): string {
	return text
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function renderStarRatingHtml(rating: number): string {
	const clamped = Math.max(0, Math.min(5, rating));
	const width = (clamped / 5) * 100;
	const label = `Rated ${clamped} out of 5`;

	return `<div class="star-rating" role="img" aria-label="${escapeHtml(label)}"><span style="width:${width}%">${escapeHtml(label)}</span></div>`;
}

function getSlideMeta(data: ReviewLightboxPayload, index: number): ReviewLightboxSlideMeta | null {
	const meta = data.slides?.[index];
	if (!meta?.author) {
		return null;
	}
	return meta;
}

function payloadHasReviewSidebar(data: ReviewLightboxPayload): boolean {
	return Boolean(data.slides?.some((slide) => slide.author));
}

function renderSidebarContent(meta: ReviewLightboxSlideMeta, i18n: TailwindscoreReviewImagesI18n): string {
	const verifiedLabel = i18n.verifiedOwner ?? 'Verified owner';
	const verified = meta.verified
		? `<span class="wc-review__verified" title="${escapeHtml(verifiedLabel)}">${escapeHtml('Verified')}</span>`
		: '';

	const flag = meta.country_flag_url
		? `<span class="wc-review__country"><img src="${escapeHtml(meta.country_flag_url)}" alt="${escapeHtml(meta.country_name ?? meta.country_code ?? '')}" width="20" height="14"><span>${escapeHtml(meta.country_code ?? '')}</span></span>`
		: '';

	const ratingHtml =
		(meta.rating ?? 0) > 0 ? `<div class="wc-review__rating">${renderStarRatingHtml(meta.rating ?? 0)}</div>` : '';

	return `
		<article class="wc-review wc-review--lightbox">
			<div class="wc-review__meta">
				<div class="wc-review__author-row">
					<strong>${escapeHtml(meta.author ?? '')}</strong>
					${flag}
					${verified}
				</div>
				<div class="wc-review__rating-row">
					${ratingHtml}
					<time>${escapeHtml(meta.date ?? '')}</time>
				</div>
			</div>
			<div class="wc-review__body">
				<div class="wc-review__text"><p>${escapeHtml(meta.content ?? '')}</p></div>
			</div>
		</article>
	`;
}

function renderSidebarShell(): string {
	return '<aside class="review-lightbox__sidebar hidden"></aside>';
}

function updateSidebar(
	sidebarEl: HTMLElement | null,
	meta: ReviewLightboxSlideMeta | null,
	i18n: TailwindscoreReviewImagesI18n,
): void {
	if (!sidebarEl) {
		return;
	}

	if (!meta?.author) {
		sidebarEl.innerHTML = '';
		sidebarEl.classList.add('hidden');
		return;
	}

	sidebarEl.classList.remove('hidden');
	sidebarEl.innerHTML = renderSidebarContent(meta, i18n);
}

function getProductLightboxData(): ReviewLightboxPayload | null {
	if (productLightboxCache !== undefined) {
		return productLightboxCache;
	}

	const scriptEl = document.getElementById('ts-product-lightbox-data');
	if (!scriptEl?.textContent) {
		productLightboxCache = null;
		return null;
	}

	try {
		productLightboxCache = JSON.parse(scriptEl.textContent) as ReviewLightboxPayload;
	} catch {
		productLightboxCache = null;
	}

	return productLightboxCache;
}

function buildDialog(
	data: ReviewLightboxPayload,
	startIndex: number,
	i18n: TailwindscoreReviewImagesI18n,
	closeIcon: string,
): HTMLDialogElement {
	const showSidebarColumn = payloadHasReviewSidebar(data);
	const initialMeta = getSlideMeta(data, startIndex);
	const dialog = document.createElement('dialog');
	dialog.className = 'review-lightbox';
	dialog.setAttribute('aria-label', i18n.closeLightbox ?? 'Review images');

	const slides = data.images
		.map((img) => `<div class="swiper-slide"><img src="${escapeHtml(img.full)}" alt="" loading="lazy"></div>`)
		.join('');

	const thumbs = data.images.length > 1
		? `<div class="review-lightbox__thumbs swiper"><div class="swiper-wrapper">${data.images
				.map((img) => `<div class="swiper-slide"><img src="${escapeHtml(img.thumb)}" alt="" loading="lazy" width="80" height="80"></div>`)
				.join('')}</div></div>`
		: '';

	dialog.innerHTML = `
		<div class="review-lightbox__inner">
			${renderOverlayCloseButton(i18n.closeLightbox ?? 'Close', closeIcon)}
			<div class="review-lightbox__layout${showSidebarColumn ? '' : ' is-gallery-only'}">
				<div class="review-lightbox__gallery">
					<div class="review-lightbox__stage">
						<div class="review-lightbox__main swiper"><div class="swiper-wrapper">${slides}</div></div>
						${data.images.length > 1 ? '<button type="button" class="review-lightbox__nav review-lightbox__nav--prev" aria-label="' + escapeHtml(i18n.previousImage ?? 'Previous') + '">‹</button><button type="button" class="review-lightbox__nav review-lightbox__nav--next" aria-label="' + escapeHtml(i18n.nextImage ?? 'Next') + '">›</button>' : ''}
					</div>
					${thumbs}
				</div>
				${showSidebarColumn ? renderSidebarShell() : ''}
			</div>
		</div>
	`;

	document.body.appendChild(dialog);

	const mainEl = dialog.querySelector<HTMLElement>('.review-lightbox__main');
	const thumbsEl = dialog.querySelector<HTMLElement>('.review-lightbox__thumbs');
	const sidebarEl = dialog.querySelector<HTMLElement>('.review-lightbox__sidebar');

	if (sidebarEl) {
		updateSidebar(sidebarEl, initialMeta, i18n);
	}

	if (mainEl) {
		if (thumbsEl && data.images.length > 1) {
			thumbsSwiper = new Swiper(thumbsEl, {
				modules: [Thumbs],
				slidesPerView: 'auto',
				spaceBetween: 8,
				watchSlidesProgress: true,
			});
		}

		mainSwiper = new Swiper(mainEl, {
			modules: [A11y, Thumbs],
			initialSlide: startIndex,
			thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
		});

		if (data.images.length > 1) {
			const wrapNav = bindSwiperWrapNavigation(mainSwiper, {
				prevEl: dialog.querySelector<HTMLElement>('.review-lightbox__nav--prev'),
				nextEl: dialog.querySelector<HTMLElement>('.review-lightbox__nav--next'),
				slideCount: data.images.length,
				keyboardRoot: dialog,
			});
			wrapNavCleanup = wrapNav.destroy;
		}

		if (showSidebarColumn && sidebarEl) {
			mainSwiper.on('slideChange', () => {
				const activeIndex = mainSwiper?.activeIndex ?? 0;
				updateSidebar(sidebarEl, getSlideMeta(data, activeIndex), i18n);
			});
		}
	}

	dialog.querySelector('[data-action="close"]')?.addEventListener('click', closeReviewLightbox);
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) {
			closeReviewLightbox();
		}
	});
	dialog.addEventListener('cancel', () => {
		closeReviewLightbox();
	});

	dialogEl = dialog;
	dialog.showModal();
	if (!dialog.contains(document.activeElement)) {
		dialog.focus();
	}
	document.documentElement.classList.add('review-lightbox-open');

	return dialog;
}

export function openReviewLightbox(trigger: HTMLElement, i18n: TailwindscoreReviewImagesI18n = {}): void {
	const data = getProductLightboxData();
	if (!data?.images?.length) {
		return;
	}

	const startIndex = Number.parseInt(trigger.dataset.imageIndex ?? '0', 10) || 0;

	closeReviewLightbox();
	buildDialog(data, startIndex, i18n, getReviewImagesCloseIcon());
}

export function closeReviewLightbox(): void {
	wrapNavCleanup?.();
	wrapNavCleanup = null;
	mainSwiper?.destroy(true, true);
	thumbsSwiper?.destroy(true, true);
	mainSwiper = null;
	thumbsSwiper = null;

	if (dialogEl) {
		dialogEl.close();
		dialogEl.remove();
		dialogEl = null;
	}

	document.documentElement.classList.remove('review-lightbox-open');
}

export function bindReviewLightbox(i18n: TailwindscoreReviewImagesI18n = {}): void {
	document.addEventListener('click', (event) => {
		const target = (event.target as HTMLElement).closest<HTMLElement>('.review-image-thumb');
		if (!target) {
			return;
		}
		event.preventDefault();
		openReviewLightbox(target, i18n);
	});
}
