import Swiper from 'swiper';
import { A11y, Pagination } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import type { SwiperOptions } from 'swiper/types';

import 'swiper/css';
import 'swiper/css/pagination';

import { TS_BREAKPOINT_MD_REM, TS_BREAKPOINT_XL_REM } from '../breakpoints';
import { bindSwiperWrapNavigation } from '../swiper/wrap-navigation';

const DEFAULT_COLUMNS_MOBILE = 2.5;
const DEFAULT_COLUMNS_DESKTOP = 4;
const DEFAULT_GAP = 16;
const MD_BREAKPOINT_PX = TS_BREAKPOINT_MD_REM * 16;
const XL_BREAKPOINT_PX = TS_BREAKPOINT_XL_REM * 16;

function parseSlideCount(value: string | undefined, fallback: number): number {
	const parsed = Number.parseFloat(value ?? '');

	if (Number.isNaN(parsed)) {
		return fallback;
	}

	return Math.max(1, Math.min(6, Math.round(parsed * 2) / 2));
}

function parseGap(value: string | undefined): number {
	const parsed = Number.parseInt(value ?? '', 10);

	if (Number.isNaN(parsed)) {
		return DEFAULT_GAP;
	}

	return Math.max(0, Math.min(48, parsed));
}

function unwrapProductsList(root: HTMLElement, list: HTMLElement): void {
	const parent = list.parentElement;

	// 短代码输出时 ul 外仍有一层 .woocommerce；主题模板已把 class 放在 swiper root 上则跳过。
	if (!parent?.classList.contains('woocommerce') || parent === root) {
		return;
	}

	root.classList.add('woocommerce');
	root.insertBefore(list, parent);
	parent.remove();
}

function initProductCarousel(section: HTMLElement): void {
	const root = section.querySelector<HTMLElement>('.product-carousel__swiper');
	const content = section.querySelector<HTMLElement>('.product-carousel__content');
	const list = root?.querySelector<HTMLElement>('ul.products');
	const controls = content?.querySelector<HTMLElement>('[data-ts-product-carousel-controls]');
	const navControls = section.querySelector<HTMLElement>('[data-ts-product-carousel-nav]');
	const pagination = content?.querySelector<HTMLElement>('[data-ts-product-carousel-pagination]');
	const prevEl = section.querySelector<HTMLButtonElement>('[data-ts-product-carousel-prev]');
	const nextEl = section.querySelector<HTMLButtonElement>('[data-ts-product-carousel-next]');

	if (!root || !list || !controls || !navControls || !pagination || !prevEl || !nextEl) {
		return;
	}

	if (section.dataset.tsProductCarouselReady === '1') {
		return;
	}

	const items = list.querySelectorAll<HTMLElement>(':scope > li');

	if (items.length <= 1) {
		return;
	}

	unwrapProductsList(root, list);

	list.classList.add('swiper-wrapper');
	items.forEach((item) => {
		item.classList.add('swiper-slide');
	});

	const slidesMobile = parseSlideCount(section.dataset.tsColumnsMobile, DEFAULT_COLUMNS_MOBILE);
	const slidesDesktop = parseSlideCount(section.dataset.tsColumnsDesktop, DEFAULT_COLUMNS_DESKTOP);
	const wideRaw = section.dataset.tsColumnsWide;
	const slidesWide =
		wideRaw !== undefined && wideRaw !== ''
			? parseSlideCount(wideRaw, slidesDesktop)
			: null;
	const spaceBetween = parseGap(section.dataset.tsGap);
	const slideCount = items.length;

	const mountSwiper = (): void => {
		let swiper: SwiperInstance | null = null;
		let wrapNav: ReturnType<typeof bindSwiperWrapNavigation> | null = null;

		try {
			const breakpoints: NonNullable<SwiperOptions['breakpoints']> = {
				[MD_BREAKPOINT_PX]: {
					slidesPerView: slidesDesktop,
					spaceBetween,
				},
			};

			if (slidesWide !== null) {
				breakpoints[XL_BREAKPOINT_PX] = {
					slidesPerView: slidesWide,
					spaceBetween,
				};
			}

			swiper = new Swiper(root, {
				modules: [Pagination, A11y],
				slidesPerView: slidesMobile,
				spaceBetween,
				watchOverflow: true,
				observer: true,
				observeParents: true,
				breakpoints,
				pagination: {
					el: pagination,
					clickable: true,
				},
			});

			wrapNav = bindSwiperWrapNavigation(swiper, {
				prevEl,
				nextEl,
				slideCount,
				keyboardRoot: content,
				keyboardWithin: content,
			});

			controls.hidden = false;
			navControls.hidden = false;
			section.dataset.tsProductCarouselReady = '1';
		} catch (error) {
			list.classList.remove('swiper-wrapper');
			items.forEach((item) => {
				item.classList.remove('swiper-slide');
			});
			wrapNav?.destroy();
			swiper?.destroy(true, true);
			console.error(error);
		}
	};

	requestAnimationFrame(() => {
		requestAnimationFrame(mountSwiper);
	});
}

export function initProductCarousels(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>('[data-ts-product-carousel]').forEach(initProductCarousel);
}
