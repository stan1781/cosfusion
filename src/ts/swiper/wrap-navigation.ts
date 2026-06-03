import type { Swiper as SwiperInstance } from 'swiper';

export interface WrapNavigationOptions {
	prevEl: HTMLElement | null;
	nextEl: HTMLElement | null;
	slideCount: number;
	/** Listener target for ArrowLeft/ArrowRight (e.g. dialog or document). */
	keyboardRoot?: HTMLElement | Document | null;
	/** When set, keyboard wrap only runs if focus or event target is inside this subtree. */
	keyboardWithin?: HTMLElement | null;
}

export interface WrapNavigationHandle {
	destroy: () => void;
}

function isKeyboardEventInScope(event: KeyboardEvent, within: HTMLElement | null | undefined): boolean {
	if (!within) {
		return true;
	}
	const target = event.target;
	if (target instanceof Node && within.contains(target)) {
		return true;
	}
	const active = document.activeElement;
	return active instanceof Node && within.contains(active);
}

function isTypingTarget(target: EventTarget | null): boolean {
	if (!(target instanceof HTMLElement)) {
		return false;
	}
	const tag = target.tagName;
	return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable;
}

export function wrapSlidePrev(swiper: SwiperInstance, slideCount: number): void {
	if (slideCount <= 1) {
		return;
	}
	if (swiper.isBeginning) {
		swiper.slideTo(slideCount - 1);
		return;
	}
	swiper.slidePrev();
}

export function wrapSlideNext(swiper: SwiperInstance, slideCount: number): void {
	if (slideCount <= 1) {
		return;
	}
	if (swiper.isEnd) {
		swiper.slideTo(0);
		return;
	}
	swiper.slideNext();
}

/**
 * Custom prev/next controls: at first/last slide, jump to the opposite end (no Swiper loop clones).
 */
export function bindSwiperWrapNavigation(
	swiper: SwiperInstance,
	options: WrapNavigationOptions,
): WrapNavigationHandle {
	const { prevEl, nextEl, slideCount, keyboardRoot, keyboardWithin } = options;

	if (slideCount <= 1) {
		return { destroy: () => {} };
	}

	const onPrevClick = (event: Event) => {
		event.preventDefault();
		wrapSlidePrev(swiper, slideCount);
	};

	const onNextClick = (event: Event) => {
		event.preventDefault();
		wrapSlideNext(swiper, slideCount);
	};

	prevEl?.addEventListener('click', onPrevClick);
	nextEl?.addEventListener('click', onNextClick);

	const keyRoot = keyboardRoot ?? swiper.el;
	const onKeyDown: EventListener = (event) => {
		if (!(event instanceof KeyboardEvent)) {
			return;
		}
		if (!isKeyboardEventInScope(event, keyboardWithin)) {
			return;
		}
		if (isTypingTarget(event.target)) {
			return;
		}
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			wrapSlidePrev(swiper, slideCount);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			wrapSlideNext(swiper, slideCount);
		}
	};

	keyRoot.addEventListener('keydown', onKeyDown);

	return {
		destroy: () => {
			prevEl?.removeEventListener('click', onPrevClick);
			nextEl?.removeEventListener('click', onNextClick);
			keyRoot.removeEventListener('keydown', onKeyDown);
		},
	};
}
