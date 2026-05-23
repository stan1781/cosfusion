export interface GalleryImage {
	id: number;
	src: string;
	thumbnail: string;
	gallery?: string;
	gallery_thumbnail_src?: string;
	alt?: string;
	srcset?: string;
	sizes?: string;
	thumb_srcset?: string;
	thumb_sizes?: string;
	gallery_srcset?: string;
	gallery_sizes?: string;
	width?: number;
	height?: number;
	is_main?: boolean;
}

export interface TailwindscoreProductGalleryConfig {
	productId: number;
	productTitle?: string;
	attributeLabels?: Record<string, string>;
	parent: GalleryImage[];
	layout: {
		desktopThumbs: string;
		mobileThumbs: boolean;
	};
	showCounter: boolean;
	thumbLimit: number;
	i18n: {
		prev: string;
		next: string;
		counter: string;
	};
}

export interface WcVariationRow {
	variation_id: number;
	attributes: Record<string, string>;
	image?: {
		src?: string;
		full_src?: string;
		gallery_thumbnail_src?: string;
		srcset?: string;
		sizes?: string;
		alt?: string;
		src_w?: number;
		src_h?: number;
		full_srcset?: string;
		full_src_sizes?: string;
	};
	image_id?: number;
	variation_images?: GalleryImage[];
}

export type GalleryActionType =
	| 'restoreParent'
	| 'activateSlide'
	| 'patchFirstSlide'
	| 'replaceAllSlides';

export interface GalleryAction {
	type: GalleryActionType;
	index?: number;
	images?: GalleryImage[];
	patchImage?: GalleryImage;
}

export interface TailwindscoreReviewImagesIcons {
	close?: string;
}

export interface TailwindscoreReviewImagesConfig {
	enabled?: boolean;
	maxImages?: number;
	maxFileSize?: number;
	ajaxUrl?: string;
	icons?: TailwindscoreReviewImagesIcons;
}

export interface TailwindscoreReviewImagesI18n {
	uploading?: string;
	uploadFailed?: string;
	maxImagesReached?: string;
	invalidFileType?: string;
	fileTooLarge?: string;
	remove?: string;
	addPhotos?: string;
	selectImages?: string;
	dragHere?: string;
	closeLightbox?: string;
	previousImage?: string;
	nextImage?: string;
	verifiedOwner?: string;
}

declare global {
	interface Window {
		tailwindscoreProduct?: TailwindscoreProductGalleryConfig & {
			variations?: unknown;
			variationsI18n?: Record<string, string>;
			reviewImages?: TailwindscoreReviewImagesConfig;
			reviewImagesI18n?: TailwindscoreReviewImagesI18n;
		};
	}
}
