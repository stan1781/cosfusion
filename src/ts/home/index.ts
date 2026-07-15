import Swiper from 'swiper';
import { A11y, Autoplay, Pagination } from 'swiper/modules';
import type { SwiperOptions } from 'swiper/types';

import 'swiper/css';
import 'swiper/css/pagination';

import type { TailwindscoreHomeConfig } from './types';

const DEFAULT_CONFIG: TailwindscoreHomeConfig = {
	hero: {
		autoplay: false,
		autoplayDelay: 5000,
	},
};

function getConfig(): TailwindscoreHomeConfig {
	const fromWindow = window.tailwindscoreHome;

	if (!fromWindow) {
		return DEFAULT_CONFIG;
	}

	return {
		hero: { ...DEFAULT_CONFIG.hero, ...fromWindow.hero },
	};
}

function initHeroSwiper(config: TailwindscoreHomeConfig): void {
	const root = document.querySelector<HTMLElement>('[data-ts-home-hero]');

	if (!root) {
		return;
	}

	const slideCount = root.querySelectorAll('.swiper-slide').length;

	if (slideCount <= 1) {
		root.classList.add('home-hero__swiper--single');
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

function bootHomeSwipers(): void {
	const config = getConfig();
	initHeroSwiper(config);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', bootHomeSwipers);
} else {
	bootHomeSwipers();
}
