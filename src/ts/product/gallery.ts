import Swiper from 'swiper';
import { A11y, Pagination, Thumbs } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';

import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/thumbs';

import { bindSwiperWrapNavigation } from '../swiper/wrap-navigation';

import { createLightboxController, type GalleryLightboxController } from './gallery-photoswipe';
import {
	findMatchingVariation,
	gallerySetsEqual,
	parseVariationsForm,
	resolveGalleryAction,
} from './gallery-variations';
import type { GalleryAction, GalleryImage, TailwindscoreProductGalleryConfig } from './types';
import { TS_MEDIA_UP } from '../breakpoints';

const DESKTOP_MQ = TS_MEDIA_UP.lg;
/** 与竖向 thumbs Swiper `spaceBetween` 一致。 */
const THUMBS_GAP_PX = 8;
const THUMB_LIMIT_DEFAULT = 6;
const THUMB_LIMIT_MIN = 3;
const THUMB_LIMIT_MAX = 12;

function parseConfigFromRoot(root: HTMLElement): TailwindscoreProductGalleryConfig | null {
	const raw = root.getAttribute('data-product-gallery');
	if (!raw) {
		return null;
	}
	try {
		const parsed = JSON.parse(raw) as TailwindscoreProductGalleryConfig;
		return parsed?.parent?.length ? parsed : null;
	} catch {
		return null;
	}
}

function getConfig(root: HTMLElement): TailwindscoreProductGalleryConfig | null {
	const fromAttr = parseConfigFromRoot(root);
	const cfg = window.tailwindscoreProduct;

	if (cfg?.parent?.length) {
		return {
			...cfg,
			productTitle: cfg.productTitle ?? fromAttr?.productTitle,
			attributeLabels: cfg.attributeLabels ?? fromAttr?.attributeLabels,
		} as TailwindscoreProductGalleryConfig;
	}

	return fromAttr;
}

function slideImageUrls(
	img: GalleryImage,
	forThumb: boolean,
): { src: string; srcset: string; sizes: string } {
	if (forThumb) {
		return {
			src: img.thumbnail || img.gallery_thumbnail_src || img.src,
			srcset: img.thumb_srcset || '',
			sizes: img.thumb_sizes || '',
		};
	}
	return {
		src: img.gallery || img.src,
		srcset: img.gallery_srcset || img.srcset || '',
		sizes: img.gallery_sizes || img.sizes || '',
	};
}

function applyImgAttrs(
	el: HTMLImageElement,
	img: GalleryImage,
	forThumb: boolean,
	eager: boolean,
): void {
	const { src, srcset, sizes } = slideImageUrls(img, forThumb);
	el.src = src;
	if (srcset) {
		el.setAttribute('srcset', srcset);
	} else {
		el.removeAttribute('srcset');
	}
	if (sizes) {
		el.setAttribute('sizes', sizes);
	} else {
		el.removeAttribute('sizes');
	}
	el.alt = img.alt || '';
	el.decoding = 'async';
	el.loading = eager ? 'eager' : 'lazy';
	if (eager) {
		el.setAttribute('fetchpriority', 'high');
		el.setAttribute('data-no-lazy', '1');
	} else {
		el.removeAttribute('fetchpriority');
		el.removeAttribute('data-no-lazy');
	}
}

function createSlideElement(
	img: GalleryImage,
	index: number,
	eager: boolean,
	forThumb = false,
): HTMLElement {
	const slide = document.createElement('div');
	slide.className = 'swiper-slide';
	slide.dataset.slideIndex = String(index);
	const el = document.createElement('img');
	applyImgAttrs(el, img, forThumb, eager);
	slide.appendChild(el);
	return slide;
}

function applyImageToSlide(
	slide: HTMLElement,
	img: GalleryImage,
	eager = false,
	forThumb = false,
): void {
	const el = slide.querySelector('img');
	if (!el) {
		return;
	}
	applyImgAttrs(el, img, forThumb, eager);
}

class ProductGallery {
	private readonly root: HTMLElement;
	private readonly config: TailwindscoreProductGalleryConfig;
	private readonly parentGallery: GalleryImage[];
	private readonly mainEl: HTMLElement;
	private readonly thumbsEl: HTMLElement;
	private readonly mainWrapper: HTMLElement;
	private readonly thumbsWrapper: HTMLElement;
	private readonly paginationEl: HTMLElement;
	private readonly counterEl: HTMLElement;
	private readonly navPrev: HTMLButtonElement | null;
	private readonly navNext: HTMLButtonElement | null;
	private readonly form: HTMLFormElement | null;
	private readonly lightbox: GalleryLightboxController;
	private readonly desktopMq: MediaQueryList;

	private mainSwiper: SwiperInstance | null = null;
	private thumbsSwiper: SwiperInstance | null = null;
	private wrapNavCleanup: (() => void) | null = null;
	private mainClickHandler: ((swiper: SwiperInstance) => void) | null = null;
	private currentImages: GalleryImage[] = [];
	private lastActionKey = '';
	private resizeTimer: number | null = null;

	constructor(root: HTMLElement, config: TailwindscoreProductGalleryConfig) {
		this.root = root;
		this.config = config;
		this.parentGallery = [...config.parent];
		this.mainEl = root.querySelector('[data-ts-gallery-main]') as HTMLElement;
		this.thumbsEl = root.querySelector('[data-ts-gallery-thumbs]') as HTMLElement;
		this.mainWrapper = root.querySelector('[data-ts-gallery-main-wrapper]') as HTMLElement;
		this.thumbsWrapper = root.querySelector('[data-ts-gallery-thumbs-wrapper]') as HTMLElement;
		this.paginationEl = root.querySelector('[data-ts-gallery-pagination]') as HTMLElement;
		this.counterEl = root.querySelector('[data-ts-gallery-counter]') as HTMLElement;
		this.navPrev = root.querySelector<HTMLButtonElement>('[data-ts-gallery-prev]');
		this.navNext = root.querySelector<HTMLButtonElement>('[data-ts-gallery-next]');
		this.form = document.querySelector<HTMLFormElement>('form.variations_form');
		this.lightbox = createLightboxController();
		this.desktopMq = window.matchMedia(DESKTOP_MQ);
	}

	init(): void {
		if (this.mainWrapper.querySelector('.swiper-slide')) {
			this.currentImages = [...this.parentGallery];
			this.initSwipers();
		} else {
			this.rebuildGallery(this.parentGallery);
		}
		this.bindVariationEvents();
		this.desktopMq.addEventListener('change', () => this.initSwipers());
		window.addEventListener('resize', this.onWindowResize);
	}

	private readonly onWindowResize = (): void => {
		if (this.resizeTimer !== null) {
			window.clearTimeout(this.resizeTimer);
		}
		this.resizeTimer = window.setTimeout(() => {
			this.resizeTimer = null;
			this.applyThumbsMetrics();
		}, 100);
	};

	private actionKey(action: GalleryAction): string {
		if (action.type === 'activateSlide') {
			return `activate:${action.index}`;
		}
		if (action.type === 'patchFirstSlide' && action.patchImage) {
			return `patch:${action.patchImage.src}`;
		}
		if (action.type === 'replaceAllSlides' && action.images) {
			return `replace:${action.images.map((i) => i.src).join('|')}`;
		}
		return 'restore';
	}

	private updateCounter(): void {
		if (!this.config.showCounter || !this.counterEl) {
			return;
		}
		const total = this.currentImages.length;
		if (total < 2) {
			this.counterEl.hidden = true;
			return;
		}
		this.counterEl.hidden = false;
		this.counterEl.textContent = this.config.i18n.counter
			.replace('%1$d', String((this.mainSwiper?.activeIndex ?? 0) + 1))
			.replace('%2$d', String(total));
	}

	private destroySwipers(): void {
		this.wrapNavCleanup?.();
		this.wrapNavCleanup = null;

		if (this.mainSwiper && this.mainClickHandler) {
			this.mainSwiper.off('click', this.mainClickHandler);
		}
		this.mainClickHandler = null;
		this.mainSwiper?.destroy(true, true);
		this.thumbsSwiper?.destroy(true, true);
		this.mainSwiper = null;
		this.thumbsSwiper = null;
	}

	private bindMainLightboxClick(): void {
		if (!this.mainSwiper) {
			return;
		}

		this.mainClickHandler = (swiper: SwiperInstance) => {
			const clickedIndex = swiper.clickedIndex;
			if (clickedIndex === undefined || clickedIndex < 0 || !this.currentImages[clickedIndex]) {
				return;
			}
			const slide = swiper.slides[clickedIndex] as HTMLElement | undefined;
			void this.lightbox.open(
				clickedIndex,
				this.currentImages,
				slide?.querySelector('img') ?? null,
				{
					productTitle: this.config.productTitle,
					attributeLabels: this.config.attributeLabels,
					variationsForm: this.form,
				},
			);
		};

		this.mainSwiper.on('click', this.mainClickHandler);
	}

	private resolveThumbLimit(): number {
		const raw = Number(this.config.thumbLimit);
		const limit = Number.isFinite(raw) ? Math.floor(raw) : THUMB_LIMIT_DEFAULT;
		return Math.max(THUMB_LIMIT_MIN, Math.min(THUMB_LIMIT_MAX, limit));
	}

	/** 按主图高度均分缩略图边长，并校正激活项位置。 */
	private applyThumbsMetrics(): void {
		if (!this.desktopMq.matches || this.currentImages.length < 2 || !this.thumbsSwiper) {
			return;
		}

		const height = this.mainEl.clientHeight;
		const colW = this.thumbsEl.clientWidth;
		if (height <= 0 || colW <= 0) {
			return;
		}

		const n = this.currentImages.length;
		const thumbLimit = this.resolveThumbLimit();
		const fitSize = (height - (n - 1) * THUMBS_GAP_PX) / n;
		const minSize = (height - (thumbLimit - 1) * THUMBS_GAP_PX) / thumbLimit;
		this.root.style.setProperty(
			'--ts-gallery-thumb-size',
			`${Math.min(colW, Math.max(minSize, fitSize))}px`,
		);
		this.thumbsSwiper.update();
		this.ensureActiveThumbCentered(0);
	}

	/**
	 * 激活项尽量垂直居中；translate 夹紧后首尾完整显示，避免半张裁切。
	 */
	private ensureActiveThumbCentered(speed = 300): void {
		const thumbs = this.thumbsSwiper;
		const main = this.mainSwiper;
		if (!thumbs || !main) {
			return;
		}

		const index = main.activeIndex;
		const slideOffset = thumbs.slidesGrid[index];
		const slideSize = thumbs.slidesSizesGrid[index];
		const viewSize = thumbs.height;
		if (slideOffset === undefined || !slideSize || viewSize <= 0) {
			return;
		}

		let target = -(slideOffset + slideSize / 2 - viewSize / 2);
		target = Math.max(thumbs.maxTranslate(), Math.min(thumbs.minTranslate(), target));
		if (Math.abs(thumbs.getTranslate() - target) < 0.5) {
			return;
		}
		void thumbs.translateTo(target, speed, false, true);
	}

	private onMainSlideChange(): void {
		this.updateCounter();
		this.ensureActiveThumbCentered();
	}

	private initSwipers(): void {
		this.destroySwipers();

		const hasMultiple = this.currentImages.length > 1;
		const useThumbs = this.desktopMq.matches && hasMultiple;

		if (useThumbs) {
			this.thumbsSwiper = new Swiper(this.thumbsEl, {
				modules: [A11y],
				direction: 'vertical',
				slidesPerView: 'auto',
				spaceBetween: THUMBS_GAP_PX,
				watchSlidesProgress: true,
				slideToClickedSlide: true,
			});
		}

		const mainModules = [A11y];
		if (useThumbs) {
			mainModules.push(Thumbs);
		} else if (hasMultiple) {
			mainModules.push(Pagination);
		}

		this.mainSwiper = new Swiper(this.mainEl, {
			modules: mainModules,
			spaceBetween: 0,
			slidesPerView: 1,
			...(useThumbs && this.thumbsSwiper ? { thumbs: { swiper: this.thumbsSwiper } } : {}),
			...(!useThumbs && hasMultiple
				? { pagination: { el: this.paginationEl, clickable: true } }
				: {}),
			a11y: {
				prevSlideMessage: this.config.i18n.prev,
				nextSlideMessage: this.config.i18n.next,
			},
			on: {
				slideChange: () => this.onMainSlideChange(),
			},
		});

		if (hasMultiple && this.navPrev && this.navNext) {
			this.wrapNavCleanup = bindSwiperWrapNavigation(this.mainSwiper, {
				prevEl: this.navPrev,
				nextEl: this.navNext,
				slideCount: this.currentImages.length,
				keyboardRoot: document,
				keyboardWithin: this.root,
			}).destroy;
		}

		this.toggleNavVisibility(hasMultiple);
		this.bindMainLightboxClick();
		this.updateCounter();

		if (useThumbs) {
			window.requestAnimationFrame(() => this.applyThumbsMetrics());
		}
	}

	private toggleNavVisibility(show: boolean): void {
		this.root.classList.toggle('product-gallery--multi', show);
		if (this.navPrev) {
			this.navPrev.hidden = !show;
		}
		if (this.navNext) {
			this.navNext.hidden = !show;
		}
	}

	rebuildGallery(images: GalleryImage[]): void {
		if (gallerySetsEqual(images, this.currentImages)) {
			return;
		}

		this.currentImages = [...images];
		this.mainWrapper.replaceChildren();
		this.thumbsWrapper.replaceChildren();

		images.forEach((img, index) => {
			this.mainWrapper.appendChild(createSlideElement(img, index, index === 0, false));
			this.thumbsWrapper.appendChild(createSlideElement(img, index, false, true));
		});

		this.initSwipers();
		this.mainSwiper?.slideTo(0, 0);
	}

	private ensureParentSlides(): void {
		if (gallerySetsEqual(this.currentImages, this.parentGallery)) {
			return;
		}
		this.rebuildGallery(this.parentGallery);
	}

	restoreParent(): void {
		this.rebuildGallery(this.parentGallery);
	}

	activateSlide(index: number): void {
		this.ensureParentSlides();
		const safeIndex = Math.max(0, Math.min(index, this.currentImages.length - 1));
		this.mainSwiper?.slideTo(safeIndex, 0);
		// 同索引时 slideChange 可能不触发，仍校正 thumbs / 计数。
		this.onMainSlideChange();
	}

	patchFirstSlide(patchImage: GalleryImage): void {
		this.ensureParentSlides();
		const firstSlide = this.mainWrapper.querySelector('.swiper-slide');
		const firstThumb = this.thumbsWrapper.querySelector('.swiper-slide');
		if (firstSlide) {
			applyImageToSlide(firstSlide as HTMLElement, patchImage, true, false);
		}
		if (firstThumb) {
			applyImageToSlide(firstThumb as HTMLElement, patchImage, false, true);
		}
		this.mainSwiper?.slideTo(0, 0);
		this.updateCounter();
	}

	applyAction(action: GalleryAction): void {
		const key = this.actionKey(action);
		if (key === this.lastActionKey) {
			return;
		}
		this.lastActionKey = key;

		switch (action.type) {
			case 'restoreParent':
				this.restoreParent();
				break;
			case 'activateSlide':
				if (typeof action.index === 'number') {
					this.activateSlide(action.index);
				}
				break;
			case 'patchFirstSlide':
				if (action.patchImage) {
					this.patchFirstSlide(action.patchImage);
				}
				break;
			case 'replaceAllSlides':
				if (action.images?.length) {
					this.rebuildGallery(action.images);
				}
				break;
		}
	}

	private syncFromForm(): void {
		if (!this.form) {
			return;
		}
		const variations = parseVariationsForm(this.form);
		const match = findMatchingVariation(this.form, variations);
		this.applyAction(resolveGalleryAction(match, this.parentGallery));
	}

	private bindVariationEvents(): void {
		if (!this.form) {
			return;
		}

		this.form.addEventListener('change', () => this.syncFromForm());
		this.form.addEventListener('reset', () => {
			this.lastActionKey = '';
			this.restoreParent();
		});
		this.syncFromForm();
	}
}

function bootstrap(): void {
	const root = document.querySelector<HTMLElement>('[data-ts-product-gallery]');
	if (!root) {
		return;
	}
	const config = getConfig(root);
	if (!config) {
		return;
	}
	new ProductGallery(root, config).init();
}

bootstrap();
