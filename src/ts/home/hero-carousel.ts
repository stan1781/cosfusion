import Swiper from 'swiper';
import { A11y, Autoplay, Pagination } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';

import 'swiper/css';
import 'swiper/css/pagination';

import { TS_BREAKPOINT_MD_REM } from '../breakpoints';
import { parseGap, parseSlideCount } from '../swiper/parse';
import { bindSwiperWrapNavigation } from '../swiper/wrap-navigation';

const DEFAULT_COLUMNS = 1;
const DEFAULT_GAP = 0;
const DEFAULT_AUTOPLAY_DELAY = 5000;
const DEFAULT_LAYOUT = 'full';
const MD_BREAKPOINT_PX = TS_BREAKPOINT_MD_REM * 16;

type HeroLayout = 'full' | 'peek';

function parseLayout(value: string | undefined): HeroLayout {
	if (value === 'peek') {
		return 'peek';
	}

	return DEFAULT_LAYOUT;
}

function parseDelay(value: string | undefined): number {
	const parsed = Number.parseInt(value ?? '', 10);

	if (Number.isNaN(parsed)) {
		return DEFAULT_AUTOPLAY_DELAY;
	}

	return Math.max(2000, Math.min(10000, parsed));
}

function initHeroCarousel(section: HTMLElement): void {
	if (section.dataset.tsHeroReady === '1') {
		return;
	}

	const root = section.querySelector<HTMLElement>('.hero-carousel__swiper');
	const pagination = section.querySelector<HTMLElement>('[data-ts-pagination]');
	const navGroup = section.querySelector<HTMLElement>('[data-ts-hero-carousel-nav]');
	const prevEl = section.querySelector<HTMLButtonElement>('[data-ts-hero-carousel-prev]');
	const nextEl = section.querySelector<HTMLButtonElement>('[data-ts-hero-carousel-next]');
	const slides = root?.querySelectorAll<HTMLElement>(':scope > .swiper-wrapper > .swiper-slide');

	if (!root || !pagination || !slides || slides.length === 0) {
		return;
	}

	const layout = parseLayout(section.dataset.tsLayout);
	const isFull = layout === 'full';
	const isPeek = layout === 'peek';
	const slideCount = slides.length;
	const slidesMobile = isFull
		? 1
		: parseSlideCount(section.dataset.tsColumnsMobile, DEFAULT_COLUMNS, 4);
	const slidesDesktop = isFull
		? 1
		: parseSlideCount(section.dataset.tsColumnsDesktop, DEFAULT_COLUMNS, 4);
	const spaceBetween = parseGap(section.dataset.tsGap, DEFAULT_GAP);
	const autoplayEnabled = section.dataset.tsAutoplay === '1';
	const autoplayDelay = parseDelay(section.dataset.tsAutoplayDelay);

	if (slideCount <= 1) {
		pagination.hidden = true;
		if (navGroup) {
			navGroup.hidden = true;
		}
		section.dataset.tsHeroReady = '1';
		return;
	}

	const modules = [Pagination, A11y];
	if (autoplayEnabled) {
		modules.push(Autoplay);
	}

	let swiper: SwiperInstance | null = null;
	let wrapNav: ReturnType<typeof bindSwiperWrapNavigation> | null = null;

	try {
		swiper = new Swiper(root, {
			modules,
			slidesPerView: slidesMobile,
			spaceBetween,
			// peek：居中激活卡，两侧露出邻张（首尾仍会贴边，无三倍轨无限循环）
			centeredSlides: isPeek,
			watchOverflow: true,
			observer: true,
			observeParents: true,
			loop: false,
			rewind: true,
			breakpoints: {
				[MD_BREAKPOINT_PX]: {
					slidesPerView: slidesDesktop,
					spaceBetween,
					centeredSlides: isPeek,
				},
			},
			pagination: {
				el: pagination,
				clickable: true,
			},
			...(autoplayEnabled
				? {
						autoplay: {
							delay: autoplayDelay,
							disableOnInteraction: false,
							pauseOnMouseEnter: true,
						},
					}
				: {}),
			a11y: {
				enabled: true,
			},
		});

		wrapNav = bindSwiperWrapNavigation(swiper, {
			prevEl,
			nextEl,
			slideCount,
			keyboardRoot: section,
			keyboardWithin: section,
		});

		if (navGroup) {
			navGroup.hidden = false;
		}

		section.dataset.tsHeroReady = '1';
	} catch (error) {
		wrapNav?.destroy();
		swiper?.destroy(true, true);
		console.error(error);
	}
}

export function initHeroCarousels(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>('[data-ts-hero-carousel]').forEach(initHeroCarousel);
}
