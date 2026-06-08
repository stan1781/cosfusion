export interface TailwindscoreHomeHeroConfig {
	autoplay: boolean;
	autoplayDelay: number;
}

export interface TailwindscoreHomeProductsConfig {
	slidesMobile: number;
	slidesDesktop: number;
	slidesWide: number;
	spaceBetween: number;
	breakpoint: number;
	breakpointWide: number;
}

export interface TailwindscoreHomeConfig {
	hero: TailwindscoreHomeHeroConfig;
	products: TailwindscoreHomeProductsConfig;
}
