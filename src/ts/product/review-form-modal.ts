let reviewFormReturnFocus: HTMLElement | null = null;

function getReviewFormModal(): HTMLElement | null {
	return document.querySelector<HTMLElement>('[data-ts-review-form-modal]');
}

export function isReviewFormModalOpen(): boolean {
	const modal = getReviewFormModal();
	return modal !== null && !modal.hasAttribute('hidden');
}

function initReviewFormRating(modal: HTMLElement): void {
	const ratingWrap = modal.querySelector<HTMLElement>('.comment-form-rating');
	const select = modal.querySelector<HTMLSelectElement>('#rating');

	if (!ratingWrap || !select || ratingWrap.querySelector('.review-form-rating__stars')) {
		return;
	}

	const stars = document.createElement('div');
	stars.className = 'review-form-rating__stars';
	stars.setAttribute('role', 'group');
	stars.setAttribute('aria-labelledby', 'comment-form-rating-label');

	for (let value = 1; value <= 5; value += 1) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'review-form-rating__star';
		button.dataset.ratingValue = String(value);
		button.setAttribute('aria-label', `${value} star${value === 1 ? '' : 's'}`);
		button.textContent = '★';
		stars.append(button);
	}

	const syncStars = (value: string): void => {
		const numeric = Number.parseInt(value, 10) || 0;
		stars.querySelectorAll<HTMLButtonElement>('.review-form-rating__star').forEach((star) => {
			const starValue = Number.parseInt(star.dataset.ratingValue ?? '0', 10);
			star.classList.toggle('is-active', starValue <= numeric);
			star.setAttribute('aria-pressed', starValue === numeric ? 'true' : 'false');
		});
	};

	stars.addEventListener('click', (event) => {
		const target = (event.target as HTMLElement).closest<HTMLButtonElement>('.review-form-rating__star');
		if (!target) {
			return;
		}
		select.value = target.dataset.ratingValue ?? '';
		select.dispatchEvent(new Event('change', { bubbles: true }));
		syncStars(select.value);
	});

	select.classList.add('review-form-rating__select');
	ratingWrap.append(stars);
	syncStars(select.value);
	select.addEventListener('change', () => syncStars(select.value));
}

export function openReviewFormModal(trigger?: HTMLElement): void {
	const modal = getReviewFormModal();

	if (!modal || isReviewFormModalOpen()) {
		return;
	}

	reviewFormReturnFocus =
		trigger ?? (document.activeElement instanceof HTMLElement ? document.activeElement : null);

	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('review-form-modal-open');
	initReviewFormRating(modal);

	window.requestAnimationFrame(() => {
		const focusTarget =
			modal.querySelector<HTMLElement>('.review-form-rating__star') ??
			modal.querySelector<HTMLElement>('#rating, #comment, #author, input:not([type="hidden"])') ??
			modal.querySelector<HTMLElement>('button, a, input, select, textarea');

		focusTarget?.focus();
	});
}

export function closeReviewFormModal(): void {
	const modal = getReviewFormModal();

	if (!modal || modal.hasAttribute('hidden')) {
		return;
	}

	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('review-form-modal-open');

	if (reviewFormReturnFocus?.isConnected) {
		reviewFormReturnFocus.focus();
	}

	reviewFormReturnFocus = null;
}

export function initReviewFormModal(): void {
	const modal = getReviewFormModal();

	if (!modal) {
		return;
	}

	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		const openTrigger = target.closest<HTMLElement>('[data-ts-review-form-open]');

		if (openTrigger) {
			event.preventDefault();
			openReviewFormModal(openTrigger);
			return;
		}

		if (target.closest('[data-ts-review-form-close]')) {
			event.preventDefault();
			closeReviewFormModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isReviewFormModalOpen()) {
			event.preventDefault();
			closeReviewFormModal();
		}
	});
}
