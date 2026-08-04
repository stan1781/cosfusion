import Swiper from 'swiper';
import { A11y, Autoplay } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';

import 'swiper/css';

const DEFAULT_AUTOPLAY_DELAY = 4000;

function parseDelay(value: string | undefined): number {
	const parsed = Number.parseInt(value ?? '', 10);

	if (Number.isNaN(parsed)) {
		return DEFAULT_AUTOPLAY_DELAY;
	}

	return Math.max(2500, Math.min(8000, parsed));
}

function prefersReducedMotion(): boolean {
	return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function initHeaderBarRoot(root: HTMLElement): void {
	if (root.dataset.tsHeaderBarReady === '1') {
		return;
	}

	const swiperEl = root.querySelector<HTMLElement>('.site-header-bar__swiper');
	const slides = swiperEl?.querySelectorAll<HTMLElement>(':scope > .swiper-wrapper > .swiper-slide');

	if (!swiperEl || !slides || slides.length <= 1) {
		root.dataset.tsHeaderBarReady = '1';
		return;
	}

	const autoplayDelay = parseDelay(root.dataset.tsAutoplayDelay);
	const reduceMotion = prefersReducedMotion();
	const modules = reduceMotion ? [A11y] : [A11y, Autoplay];

	let swiper: SwiperInstance | null = null;

	try {
		swiper = new Swiper(swiperEl, {
			modules,
			direction: 'vertical',
			slidesPerView: 1,
			spaceBetween: 0,
			rewind: true,
			allowTouchMove: true,
			...(reduceMotion
				? {}
				: {
						autoplay: {
							delay: autoplayDelay,
							disableOnInteraction: false,
							pauseOnMouseEnter: true,
						},
					}),
			a11y: {
				enabled: true,
			},
		});
	} catch {
		root.dataset.tsHeaderBarReady = '1';
		return;
	}

	root.dataset.tsHeaderBarReady = '1';

	root.addEventListener(
		'ts:header-bar-destroy',
		() => {
			swiper?.destroy(true, true);
			swiper = null;
			delete root.dataset.tsHeaderBarReady;
		},
		{ once: true },
	);
}

export function initHeaderBar(): void {
	document.querySelectorAll<HTMLElement>('[data-ts-header-bar]').forEach(initHeaderBarRoot);
}
