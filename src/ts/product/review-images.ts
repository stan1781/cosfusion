import { bindReviewLightbox } from './review-lightbox';
import { initReviewFormModal } from './review-form-modal';
import { initShowcaseGallery } from './showcase-gallery';
import type { TailwindscoreReviewImagesConfig, TailwindscoreReviewImagesI18n } from './types';

interface UploadedImage {
	attachment_id: number;
	url: string;
	thumbnail_url: string;
}

interface ReviewImagesClientConfig {
	reviewImages?: TailwindscoreReviewImagesConfig;
	reviewImagesI18n?: TailwindscoreReviewImagesI18n;
}

function isReviewImagesEnabled(reviewCfg: TailwindscoreReviewImagesConfig | undefined): boolean {
	if (!reviewCfg || reviewCfg.enabled === undefined) {
		return true;
	}
	const enabled = reviewCfg.enabled as boolean | number | string;
	return enabled !== false && enabled !== 0 && enabled !== '0' && enabled !== '';
}

function parseConfigFromWrapper(): ReviewImagesClientConfig | null {
	const wrapper = document.querySelector('#review-images-upload-wrapper');
	const raw = wrapper?.getAttribute('data-tailwindscore-review-images-config');
	if (!raw) {
		return null;
	}
	try {
		return JSON.parse(raw) as ReviewImagesClientConfig;
	} catch {
		return null;
	}
}

function getConfig(): ReviewImagesClientConfig {
	const fromDom = parseConfigFromWrapper();
	if (fromDom) {
		return fromDom;
	}
	return window.tailwindscoreProduct ?? {};
}

function getNonce(): string {
	return document.querySelector<HTMLInputElement>('#review-images-nonce')?.value ?? '';
}

function getHiddenField(): HTMLInputElement | null {
	return document.querySelector<HTMLInputElement>('#review-images-uploaded');
}

function showAlert(message: string, type: 'info' | 'error' = 'error'): void {
	const container = document.querySelector('#review-images-alerts');
	if (!container) {
		return;
	}
	container.innerHTML = `<p class="review-images-alert review-images-alert--${type}" role="alert">${message}</p>`;
	window.setTimeout(() => {
		container.innerHTML = '';
	}, 4000);
}

function initReviewImagesUpload(): void {
	void initReviewImagesUploadAsync();
}

async function initReviewImagesUploadAsync(): Promise<void> {
	const cfg = getConfig();
	const reviewCfg = cfg.reviewImages;
	const i18n = cfg.reviewImagesI18n ?? {};

	if (!isReviewImagesEnabled(reviewCfg) || !document.querySelector('#review-images-upload-wrapper')) {
		return;
	}

	const maxImages = reviewCfg?.maxImages ?? 6;
	const maxFileSize = reviewCfg?.maxFileSize ?? 5242880;
	const ajaxUrl = reviewCfg?.ajaxUrl ?? '/wp-admin/admin-ajax.php';

	try {
		await fetch(ajaxUrl, {
			method: 'POST',
			body: new URLSearchParams({
				action: 'reset_staged_review_images',
				nonce: getNonce(),
				staged_ids: '[]',
			}),
			credentials: 'same-origin',
		});
	} catch {
		// Non-blocking: upload limit still enforced per request.
	}

	const uploaded: number[] = [];
	const cache: UploadedImage[] = [];
	let uploadChain: Promise<void> = Promise.resolve();
	let isSubmitting = false;

	const countEl = document.querySelector('#review-images-count');
	const previewEl = document.querySelector('#review-images-preview');
	const progressEl = document.querySelector('#review-images-progress');
	const progressFill = progressEl?.querySelector<HTMLElement>('.review-images-progress__fill');
	const uploadToolbar = document.querySelector('#review-images-toolbar');
	const uploadWrapper = document.querySelector('#review-images-upload-wrapper');
	const commentForm = document.querySelector('#commentform');

	function syncHidden(): void {
		const field = getHiddenField();
		if (field) {
			field.value = JSON.stringify(uploaded.slice(0, maxImages));
		}
		if (countEl) {
			countEl.textContent = String(Math.min(uploaded.length, maxImages));
		}
	}

	function canAcceptAnotherUpload(): boolean {
		return uploaded.length < maxImages;
	}

	function updateUploadState(): void {
		const atLimit = uploaded.length >= maxImages;
		uploadWrapper?.classList.toggle('is-full', atLimit);
		const fileInputEl = document.querySelector<HTMLInputElement>('#review-images-input');
		const selectLabel = document.querySelector<HTMLLabelElement>('#review-images-select-label');
		if (fileInputEl) {
			fileInputEl.disabled = atLimit;
		}
		if (selectLabel) {
			selectLabel.setAttribute('aria-disabled', atLimit ? 'true' : 'false');
		}
	}

	function renderPreview(): void {
		if (!previewEl) {
			return;
		}
		previewEl.innerHTML = '';
		if (uploaded.length === 0) {
			previewEl.classList.add('hidden');
			return;
		}
		previewEl.classList.remove('hidden');
		uploaded.forEach((id, index) => {
			const data = cache[index];
			if (!data) {
				return;
			}
			const item = document.createElement('div');
			item.className = 'review-images-preview__item';
			const removeLabel = i18n.remove ?? 'Remove';
			item.innerHTML = `
				<img src="${data.thumbnail_url}" alt="">
				<button type="button" class="review-images-preview__remove review-image-remove-btn" data-index="${index}" aria-label="${removeLabel}">×</button>
			`;
			previewEl.append(item);
		});
	}

	async function uploadFile(file: File): Promise<void> {
		if (uploaded.length >= maxImages) {
			return;
		}

		const formData = new FormData();
		formData.append('action', 'upload_review_image');
		formData.append('image', file);
		formData.append('nonce', getNonce());
		formData.append('staged_ids', JSON.stringify(uploaded));

		progressEl?.classList.remove('hidden');
		progressEl?.setAttribute('aria-hidden', 'false');
		uploadToolbar?.classList.add('hidden');
		if (progressFill) {
			progressFill.style.width = '50%';
		}

		try {
			const response = await fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' });
			const json = (await response.json()) as { success?: boolean; data?: UploadedImage & { message?: string } };
			if (!json.success || !json.data?.attachment_id) {
				showAlert(json.data?.message ?? i18n.uploadFailed ?? 'Upload failed');
				return;
			}

			if (uploaded.length >= maxImages) {
				const body = new URLSearchParams({
					action: 'delete_review_image',
					nonce: getNonce(),
					attachment_id: String(json.data.attachment_id),
				});
				await fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
				showAlert(i18n.maxImagesReached ?? 'Max images reached', 'info');
				return;
			}

			uploaded.push(json.data.attachment_id);
			cache.push(json.data);
			syncHidden();
			renderPreview();
			updateUploadState();
		} catch {
			showAlert(i18n.uploadFailed ?? 'Upload failed');
		} finally {
			progressEl?.classList.add('hidden');
			progressEl?.setAttribute('aria-hidden', 'true');
			uploadToolbar?.classList.remove('hidden');
			if (progressFill) {
				progressFill.style.width = '0%';
			}
		}
	}

	function queueUpload(file: File): void {
		if (!canAcceptAnotherUpload()) {
			showAlert(i18n.maxImagesReached ?? 'Max images reached', 'info');
			return;
		}

		uploadChain = uploadChain
			.then(async () => {
				if (uploaded.length >= maxImages) {
					return;
				}
				await uploadFile(file);
			});
	}

	function handleFiles(fileList: FileList | null): void {
		if (!fileList?.length) {
			return;
		}

		const files = Array.from(fileList);
		const allowedCount = Math.min(files.length, Math.max(0, maxImages - uploaded.length));

		if (allowedCount < files.length) {
			showAlert(i18n.maxImagesReached ?? 'Max images reached', 'info');
		}

		files.slice(0, allowedCount).forEach((file) => {
			if (file.size > maxFileSize) {
				showAlert(i18n.fileTooLarge ?? 'File too large');
				return;
			}
			if (!/^image\/(jpeg|jpg|png|webp)$/i.test(file.type)) {
				showAlert(i18n.invalidFileType ?? 'Invalid file type');
				return;
			}
			queueUpload(file);
		});
	}

	async function removeImage(index: number): Promise<void> {
		const id = uploaded[index];
		if (id) {
			const body = new URLSearchParams({
				action: 'delete_review_image',
				nonce: getNonce(),
				attachment_id: String(id),
			});
			await fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		}
		uploaded.splice(index, 1);
		cache.splice(index, 1);
		syncHidden();
		renderPreview();
		updateUploadState();
	}

	document.addEventListener('click', (event) => {
		const btn = (event.target as HTMLElement).closest<HTMLButtonElement>('.review-image-remove-btn');
		if (!btn) {
			return;
		}
		event.preventDefault();
		const index = Number.parseInt(btn.dataset.index ?? '-1', 10);
		if (index >= 0) {
			void removeImage(index);
		}
	});

	document.addEventListener('change', (event) => {
		const input = event.target;
		if (!(input instanceof HTMLInputElement) || input.id !== 'review-images-input') {
			return;
		}
		handleFiles(input.files);
		input.value = '';
	});

	commentForm?.addEventListener('submit', () => {
		isSubmitting = true;
		while (uploaded.length > maxImages) {
			uploaded.pop();
			cache.pop();
		}
		syncHidden();
	});

	updateUploadState();

	const sendCleanup = (): void => {
		if (isSubmitting || uploaded.length === 0) {
			return;
		}
		const payload = new URLSearchParams({
			action: 'cleanup_review_images',
			nonce: getNonce(),
			ids: JSON.stringify(uploaded),
		});
		try {
			navigator.sendBeacon(ajaxUrl, payload);
		} catch {
			void fetch(ajaxUrl, { method: 'POST', body: payload, credentials: 'same-origin' });
		}
	};

	document.addEventListener('visibilitychange', () => {
		if (document.visibilityState === 'hidden') {
			sendCleanup();
		}
	});
	window.addEventListener('beforeunload', sendCleanup);
}

export function shouldInitReviewImages(): boolean {
	if (
		document.querySelector(
			'.showcase-gallery__strip, #review-images-upload-wrapper[data-tailwindscore-review-images-config], .review-image-thumb, [data-ts-review-form-open], [data-ts-review-form-modal]',
		)
	) {
		return true;
	}
	const reviewCfg = window.tailwindscoreProduct?.reviewImages;
	return isReviewImagesEnabled(reviewCfg);
}

export function initReviewImages(): void {
	const cfg = getConfig();
	const i18n: TailwindscoreReviewImagesI18n = cfg.reviewImagesI18n ?? {};

	initShowcaseGallery();
	bindReviewLightbox(i18n);
	initReviewFormModal();
	initReviewImagesUpload();
}
