import type { GalleryAction, GalleryImage, WcVariationRow } from './types';

export {
	findMatchingVariation,
	getChosenAttributes,
	parseVariationsForm,
} from './variation-engine';

export function normalizeBaseSrc(src: string | undefined): string {
	if (!src) {
		return '';
	}
	return src.split('?')[0].split('#')[0];
}

export function wcVariationToGalleryImage(variation: WcVariationRow): GalleryImage | null {
	if (!variation.image?.src) {
		return null;
	}
	const thumb =
		variation.image.gallery_thumbnail_src || variation.image.src;
	const gallery = variation.image.full_src || variation.image.src;
	return {
		id: variation.image_id ?? 0,
		src: variation.image.src,
		thumbnail: thumb,
		gallery,
		gallery_thumbnail_src: thumb,
		alt: variation.image.alt ?? '',
		srcset: variation.image.srcset ?? '',
		sizes: variation.image.sizes ?? '',
		thumb_srcset: variation.image.srcset ?? '',
		thumb_sizes: variation.image.sizes ?? '',
		gallery_srcset: variation.image.full_srcset ?? '',
		gallery_sizes: variation.image.full_src_sizes ?? '',
		width: variation.image.src_w,
		height: variation.image.src_h,
		is_main: true,
	};
}

export function findSlideIndexInParent(
	parent: GalleryImage[],
	variation: WcVariationRow,
): number {
	if (variation.image_id) {
		const byId = parent.findIndex((img) => img.id === variation.image_id);
		if (byId >= 0) {
			return byId;
		}
	}
	const wcImage = variation.image;
	if (!wcImage) {
		return -1;
	}
	const needles = [
		normalizeBaseSrc(wcImage.gallery_thumbnail_src),
		normalizeBaseSrc(wcImage.src),
		normalizeBaseSrc(wcImage.full_src),
	].filter(Boolean);

	for (let i = 0; i < parent.length; i++) {
		const img = parent[i];
		const hay = [
			normalizeBaseSrc(img.gallery_thumbnail_src),
			normalizeBaseSrc(img.thumbnail),
			normalizeBaseSrc(img.src),
			normalizeBaseSrc(img.gallery),
		];
		if (needles.some((n) => hay.includes(n))) {
			return i;
		}
	}
	return -1;
}

export function resolveGalleryAction(
	variation: WcVariationRow | null,
	parent: GalleryImage[],
): GalleryAction {
	if (!variation) {
		return { type: 'restoreParent' };
	}

	const custom = variation.variation_images;
	if (custom && custom.length > 0) {
		return { type: 'replaceAllSlides', images: custom };
	}

	const patchImage = wcVariationToGalleryImage(variation);
	if (!patchImage) {
		return { type: 'restoreParent' };
	}

	const index = findSlideIndexInParent(parent, variation);
	if (index >= 0) {
		return { type: 'activateSlide', index };
	}

	return { type: 'patchFirstSlide', patchImage };
}

export function gallerySetsEqual(a: GalleryImage[], b: GalleryImage[]): boolean {
	if (a.length !== b.length) {
		return false;
	}
	for (let i = 0; i < a.length; i++) {
		if (normalizeBaseSrc(a[i].src) !== normalizeBaseSrc(b[i].src)) {
			return false;
		}
	}
	return true;
}
