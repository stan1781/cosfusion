declare module 'photoswipe' {
	const PhotoSwipe: new (
		element: HTMLElement,
		uiClass: unknown,
		items: Array<{ src: string; w: number; h: number; title?: string }>,
		options: Record<string, unknown>,
	) => {
		init: () => void;
		destroy: () => void;
		listen: (event: string, callback: () => void) => void;
	};

	export default PhotoSwipe;
}

declare module 'photoswipe/dist/photoswipe-ui-default' {
	const PhotoSwipeUI_Default: unknown;
	export default PhotoSwipeUI_Default;
}

declare module 'photoswipe/dist/photoswipe.css';
declare module 'photoswipe/dist/default-skin/default-skin.css';
declare module '*.scss';
