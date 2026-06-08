import Swiper from 'swiper';
import { A11y, Autoplay, Pagination } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import type { SwiperOptions } from 'swiper/types';

import 'swiper/css';
import 'swiper/css/pagination';

import { bindSwiperWrapNavigation } from '../swiper/wrap-navigation';
import type { TailwindscoreHomeConfig } from './types';

const DEFAULT_CONFIG: TailwindscoreHomeConfig = {
	hero: {
		autoplay: false,
		autoplayDelay: 5000,
	},
	products: {
		slidesMobile: 2.5,
		slidesDesktop: 4,
		spaceBetween: 24,
		breakpoint: 768,
	},
};

function getConfig(): TailwindscoreHomeConfig {
	const fromWindow = window.tailwindscoreHome;

	if (!fromWindow) {
		return DEFAULT_CONFIG;
	}

	return {
		hero: { ...DEFAULT_CONFIG.hero, ...fromWindow.hero },
		products: { ...DEFAULT_CONFIG.products, ...fromWindow.products },
	};
}

function initHeroSwiper(config: TailwindscoreHomeConfig): void {
	const root = document.querySelector<HTMLElement>('[data-ts-home-hero]');

	if (!root) {
		return;
	}

	const slideCount = root.querySelectorAll('.swiper-slide').length;

	if (slideCount <= 1) {
		root.classList.add('ts-home-hero__swiper--single');
		return;
	}

	const pagination = root.querySelector<HTMLElement>('.swiper-pagination');

	if (!pagination) {
		return;
	}

	const modules: SwiperOptions['modules'] = [Pagination, A11y];
	const options: SwiperOptions = {
		modules,
		slidesPerView: 1,
		watchOverflow: true,
		pagination: {
			el: pagination,
			clickable: true,
		},
	};

	if (config.hero.autoplay) {
		modules.push(Autoplay);
		options.autoplay = {
			delay: config.hero.autoplayDelay,
			disableOnInteraction: false,
		};
	}

	new Swiper(root, options);
}

function unwrapProductsList(root: HTMLElement, list: HTMLElement): void {
	const parent = list.parentElement;

	if (!parent?.classList.contains('woocommerce')) {
		return;
	}

	root.insertBefore(list, parent);
	parent.remove();
}

function initProductsSwiper(config: TailwindscoreHomeConfig): void {
	const root = document.querySelector<HTMLElement>('[data-ts-home-products]');
	const section = root?.closest<HTMLElement>('.ts-home-products');
	const content = root?.closest<HTMLElement>('.ts-home-products__content');
	const list = root?.querySelector<HTMLElement>('ul.products');
	const controls = content?.querySelector<HTMLElement>('[data-ts-home-products-controls]');
	const navControls = section?.querySelector<HTMLElement>('[data-ts-home-products-nav]');
	const pagination = content?.querySelector<HTMLElement>('[data-ts-home-products-pagination]');
	const prevEl = section?.querySelector<HTMLButtonElement>('[data-ts-home-products-prev]');
	const nextEl = section?.querySelector<HTMLButtonElement>('[data-ts-home-products-next]');

	if (!root || !list || !controls || !navControls || !pagination || !prevEl || !nextEl) {
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

	const { slidesMobile, slidesDesktop, spaceBetween, breakpoint } = config.products;
	const slideCount = items.length;

	const mountSwiper = (): void => {
		let swiper: SwiperInstance | null = null;
		let wrapNav: ReturnType<typeof bindSwiperWrapNavigation> | null = null;

		try {
			swiper = new Swiper(root, {
				modules: [Pagination, A11y],
				slidesPerView: slidesMobile,
				spaceBetween,
				watchOverflow: true,
				observer: true,
				observeParents: true,
				breakpoints: {
					[breakpoint]: {
						slidesPerView: slidesDesktop,
						spaceBetween,
					},
				},
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

	// Wait for layout after switching the product list off CSS grid.
	requestAnimationFrame(() => {
		requestAnimationFrame(mountSwiper);
	});
}

function bootHomeSwipers(): void {
	const config = getConfig();
	initHeroSwiper(config);
	initProductsSwiper(config);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', bootHomeSwipers);
} else {
	bootHomeSwipers();
}
