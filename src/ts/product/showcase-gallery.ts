const DEFAULT_THUMB = 64;
const DEFAULT_GAP = 8;
const DEFAULT_ROWS = 2;

function layoutShowcaseStrip(strip: HTMLElement): void {
	const thumbSize = Number.parseInt(strip.dataset.showcaseThumb ?? String(DEFAULT_THUMB), 10) || DEFAULT_THUMB;
	const gap = Number.parseInt(strip.dataset.showcaseGap ?? String(DEFAULT_GAP), 10) || DEFAULT_GAP;
	const maxRows = Number.parseInt(strip.dataset.showcaseRows ?? String(DEFAULT_ROWS), 10) || DEFAULT_ROWS;
	const moreLabelTemplate = strip.dataset.showcaseMoreLabel ?? '+%d more';

	const thumbs = Array.from(
		strip.querySelectorAll<HTMLElement>('.showcase-gallery__item:not(.showcase-gallery__more)'),
	);
	const moreBtn = strip.querySelector<HTMLElement>('.showcase-gallery__more');
	const moreLabel = moreBtn?.querySelector<HTMLElement>('.showcase-gallery__more-label');

	if (thumbs.length === 0) {
		moreBtn?.classList.add('hidden');
		return;
	}

	const width = strip.clientWidth;
	if (width <= 0) {
		return;
	}

	const cols = Math.max(1, Math.floor((width + gap) / (thumbSize + gap)));
	const capacity = cols * maxRows;
	const needsMore = thumbs.length > capacity;
	const maxVisible = needsMore ? Math.max(1, capacity - 1) : thumbs.length;
	const hiddenCount = thumbs.length - maxVisible;

	thumbs.forEach((item, index) => {
		item.classList.toggle('hidden', index >= maxVisible);
	});

	if (!moreBtn || !moreLabel) {
		return;
	}

	if (needsMore && hiddenCount > 0) {
		moreBtn.classList.remove('hidden');
		moreLabel.textContent = moreLabelTemplate.replace('%d', String(hiddenCount));
		moreBtn.dataset.imageIndex = String(maxVisible);
	} else {
		moreBtn.classList.add('hidden');
	}
}

function observeShowcaseStrip(strip: HTMLElement): void {
	layoutShowcaseStrip(strip);

	const resizeObserver = new ResizeObserver(() => {
		layoutShowcaseStrip(strip);
	});
	resizeObserver.observe(strip);
}

export function layoutShowcaseGalleries(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>('.showcase-gallery__strip').forEach((strip) => {
		layoutShowcaseStrip(strip);
	});
}

export function initShowcaseGallery(): void {
	const strips = document.querySelectorAll<HTMLElement>('.showcase-gallery__strip');
	if (strips.length === 0) {
		return;
	}

	strips.forEach(observeShowcaseStrip);

	document.querySelectorAll<HTMLElement>('.woocommerce-tabs a[href="#tab-reviews"]').forEach((tab) => {
		tab.addEventListener('click', () => {
			window.requestAnimationFrame(() => {
				layoutShowcaseGalleries();
			});
		});
	});

	window.addEventListener('resize', () => {
		layoutShowcaseGalleries();
	});
}
