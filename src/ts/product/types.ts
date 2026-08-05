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
		price_html?: string;
		display_price?: number;
		display_regular_price?: number;
		variation_is_visible?: boolean;
		variation_is_active?: boolean;
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

export interface SizeGuideColumn {
	key: string;
	label: string;
	type: 'size' | 'length' | 'text';
}

export interface SizeGuideRow {
	sizeKey: string;
	sizeLabel: string;
	values: Record<string, number | string | null>;
}

export interface SizeGuideTable {
	columns: SizeGuideColumn[];
	rows: SizeGuideRow[];
}

export interface SizeGuideConfig {
	enabled: boolean;
	displayMode: 'conditional' | 'always';
	hasContent: boolean;
	showUnitSwitcher: boolean;
	defaultUnit: 'cm' | 'inch';
	bindAttribute: string;
	inlineEnabled: boolean;
	table: SizeGuideTable | null;
	rowsBySizeKey: Record<string, SizeGuideRow>;
	legacyHtml: string | null;
	i18n: {
		title: string;
		cm: string;
		inch: string;
		close: string;
		viewGuide: string;
		noRow: string;
	};
}

export type VariationSettings = {
	enabled: boolean | number | string;
	colorAttributes: string[];
	sizeAttributes: string[];
	colorSelectorDefaultMode: 'grid' | 'list';
	colorSelectorShape: string;
	gridShowText: boolean;
	priceSelector: string;
};

	export type VariationsI18n = {
		list: string;
		grid: string;
		switchToList: string;
		switchToGrid: string;
		selectAttribute: string;
	};

/** Merged single-product runtime config (gallery + modules). */
export type TailwindscoreProductPageConfig = Partial<TailwindscoreProductGalleryConfig> & {
	variations?: VariationSettings;
	variationsI18n?: VariationsI18n;
	reviewImages?: TailwindscoreReviewImagesConfig;
	reviewImagesI18n?: TailwindscoreReviewImagesI18n;
	sizeGuide?: SizeGuideConfig;
};
