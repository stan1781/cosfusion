export interface TailwindscoreHomeHeroConfig {
	autoplay: boolean;
	autoplayDelay: number;
}

export interface TailwindscoreHomeProductsConfig {
	slidesMobile: number;
	slidesDesktop: number;
	spaceBetween: number;
	breakpoint: number;
}

export interface TailwindscoreHomeConfig {
	hero: TailwindscoreHomeHeroConfig;
	products: TailwindscoreHomeProductsConfig;
}
