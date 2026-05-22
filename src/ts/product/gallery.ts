import Swiper from 'swiper';
import { A11y, Navigation, Pagination, Thumbs } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/thumbs';

import { createLightboxController, type GalleryLightboxController } from './gallery-photoswipe';
import {
	findMatchingVariation,
	gallerySetsEqual,
	parseVariationsForm,
	resolveGalleryAction,
} from './gallery-variations';
import type { GalleryAction, GalleryImage, TailwindscoreProductGalleryConfig, WcVariationRow } from './types';

const DESKTOP_MQ = '(min-width: 1024px)';

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
		};
	}

	return fromAttr;
}

function slideImageUrls(img: GalleryImage, forThumb = false): {
	src: string;
	srcset: string;
	sizes: string;
} {
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

function createSlideElement(img: GalleryImage, index: number, eager: boolean): HTMLElement {
	const slide = document.createElement('div');
	slide.className = 'swiper-slide';
	slide.dataset.slideIndex = String(index);

	const { src, srcset, sizes } = slideImageUrls(img, false);
	const el = document.createElement('img');
	el.src = src;
	if (srcset) {
		el.setAttribute('srcset', srcset);
	}
	if (sizes) {
		el.setAttribute('sizes', sizes);
	}
	el.alt = img.alt || '';
	el.decoding = 'async';
	el.loading = eager ? 'eager' : 'lazy';
	if (eager) {
		el.setAttribute('fetchpriority', 'high');
	}
	slide.appendChild(el);
	return slide;
}

function applyImageToSlide(slide: HTMLElement, img: GalleryImage, eager = false): void {
	const el = slide.querySelector('img');
	if (!el) {
		return;
	}
	const { src, srcset, sizes } = slideImageUrls(img, false);
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
	el.loading = eager ? 'eager' : 'lazy';
}

class ProductGallery {
	private readonly root: HTMLElement;
	private readonly config: TailwindscoreProductGalleryConfig;
	private readonly parentGallery: GalleryImage[];
	private readonly mainWrapper: HTMLElement;
	private readonly thumbsWrapper: HTMLElement;
	private readonly paginationEl: HTMLElement;
	private readonly counterEl: HTMLElement;
	private readonly navPrev: HTMLButtonElement | null;
	private readonly navNext: HTMLButtonElement | null;
	private readonly form: HTMLFormElement | null;

	private mainSwiper: SwiperInstance | null = null;
	private thumbsSwiper: SwiperInstance | null = null;
	private readonly lightbox: GalleryLightboxController;
	private mainClickHandler: ((swiper: SwiperInstance) => void) | null = null;
	private desktopMq: MediaQueryList;
	private currentImages: GalleryImage[] = [];
	private mode: 'parent' | 'custom' = 'parent';
	private patchedFirst = false;
	private lastActionKey = '';

	constructor(root: HTMLElement, config: TailwindscoreProductGalleryConfig) {
		this.root = root;
		this.config = config;
		this.parentGallery = [...config.parent];
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
		const hasSsrSlides = this.mainWrapper.querySelector('.swiper-slide') !== null;
		if (hasSsrSlides) {
			this.currentImages = [...this.parentGallery];
			this.mode = 'parent';
			this.initSwipers();
		} else {
			this.rebuildGallery(this.parentGallery);
		}
		this.bindVariationEvents();
		this.desktopMq.addEventListener('change', () => this.refreshSwiperMode());
	}

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
		const current = (this.mainSwiper?.activeIndex ?? 0) + 1;
		this.counterEl.hidden = false;
		this.counterEl.textContent = this.config.i18n.counter
			.replace('%1$d', String(current))
			.replace('%2$d', String(total));
	}

	private destroySwipers(): void {
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
			if (clickedIndex === undefined || clickedIndex < 0) {
				return;
			}

			if (!this.currentImages[clickedIndex]) {
				return;
			}

			const slide = swiper.slides[clickedIndex] as HTMLElement | undefined;
			const img = slide?.querySelector('img') ?? null;
			void this.lightbox.open(clickedIndex, this.currentImages, img, {
				productTitle: this.config.productTitle,
				attributeLabels: this.config.attributeLabels,
				variationsForm: this.form,
			});
		};

		this.mainSwiper.on('click', this.mainClickHandler);
	}

	private isDesktopThumbs(): boolean {
		return this.desktopMq.matches;
	}

	private initSwipers(): void {
		this.destroySwipers();

		const useThumbs = this.isDesktopThumbs() && this.currentImages.length > 1;

		if (useThumbs) {
			this.thumbsSwiper = new Swiper(this.root.querySelector('[data-ts-gallery-thumbs]') as HTMLElement, {
				modules: [A11y],
				direction: 'vertical',
				slidesPerView: 'auto',
				spaceBetween: 8,
				watchSlidesProgress: true,
				freeMode: true,
				slideToClickedSlide: true,
			});
		}

		const hasMultiple = this.currentImages.length > 1;
		const mainModules = [A11y, Navigation];
		if (useThumbs) {
			mainModules.push(Thumbs);
		}
		if (!useThumbs && hasMultiple) {
			mainModules.push(Pagination);
		}

		this.mainSwiper = new Swiper(this.root.querySelector('[data-ts-gallery-main]') as HTMLElement, {
			modules: mainModules,
			spaceBetween: 0,
			slidesPerView: 1,
			...(useThumbs && this.thumbsSwiper ? { thumbs: { swiper: this.thumbsSwiper } } : {}),
			...(hasMultiple && this.navPrev && this.navNext
				? {
						navigation: {
							prevEl: this.navPrev,
							nextEl: this.navNext,
						},
					}
				: {}),
			...(!useThumbs && hasMultiple
				? {
						pagination: {
							el: this.paginationEl,
							clickable: true,
						},
					}
				: {}),
			a11y: {
				prevSlideMessage: this.config.i18n.prev,
				nextSlideMessage: this.config.i18n.next,
			},
			on: {
				slideChange: () => this.updateCounter(),
			},
		});

		this.toggleNavVisibility(hasMultiple);
		this.bindMainLightboxClick();

		this.updateCounter();
	}

	private toggleNavVisibility(show: boolean): void {
		this.root.classList.toggle('ts-product-gallery--multi', show);
		if (this.navPrev) {
			this.navPrev.hidden = !show;
		}
		if (this.navNext) {
			this.navNext.hidden = !show;
		}
	}

	private refreshSwiperMode(): void {
		this.initSwipers();
	}

	rebuildGallery(images: GalleryImage[]): void {
		if (gallerySetsEqual(images, this.currentImages)) {
			return;
		}

		this.currentImages = [...images];
		this.mode = gallerySetsEqual(images, this.parentGallery) ? 'parent' : 'custom';
		this.patchedFirst = false;

		this.mainWrapper.replaceChildren();
		this.thumbsWrapper.replaceChildren();

		images.forEach((img, index) => {
			this.mainWrapper.appendChild(createSlideElement(img, index, index === 0));
			this.thumbsWrapper.appendChild(createSlideElement(img, index, false));
		});

		this.initSwipers();
		this.mainSwiper?.slideTo(0, 0);
		this.toggleNavVisibility(images.length > 1);
	}

	private ensureParentSlides(): void {
		if (this.mode === 'parent' && gallerySetsEqual(this.currentImages, this.parentGallery)) {
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
		this.thumbsSwiper?.slideTo(safeIndex, 0);
		this.patchedFirst = false;
		this.updateCounter();
	}

	patchFirstSlide(patchImage: GalleryImage): void {
		this.ensureParentSlides();
		const firstSlide = this.mainWrapper.querySelector('.swiper-slide');
		const firstThumb = this.thumbsWrapper.querySelector('.swiper-slide');
		if (firstSlide) {
			applyImageToSlide(firstSlide as HTMLElement, patchImage, true);
		}
		if (firstThumb) {
			applyImageToSlide(firstThumb as HTMLElement, patchImage, false);
		}
		this.patchedFirst = true;
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
		const action = resolveGalleryAction(match, this.parentGallery);
		this.applyAction(action);
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

		const jq = (window as Window & { jQuery?: (sel: Element) => { on: (ev: string, fn: (...args: unknown[]) => void) => void } }).jQuery;
		if (jq) {
			jq(this.form).on('show_variation', (_event: unknown, variation: unknown) => {
				const action = resolveGalleryAction(variation as WcVariationRow, this.parentGallery);
				this.applyAction(action);
			});
			jq(this.form).on('hide_variation reset_data', () => {
				this.lastActionKey = '';
				this.restoreParent();
			});
		}

		// Initial state if attributes pre-selected from URL.
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
	const gallery = new ProductGallery(root, config);
	gallery.init();
}

bootstrap();
