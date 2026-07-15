declare module 'alpinejs' {
	type AlpineComponentFactory = (...args: never[]) => Record<string, unknown>;

	const Alpine: {
		data(name: string, callback: AlpineComponentFactory): void;
		$data(element: Element): unknown;
		start(): void;
	};

	export default Alpine;
}
