declare module 'alpinejs' {
	const Alpine: {
		data(name: string, callback: () => Record<string, unknown>): void;
		start(): void;
	};

	export default Alpine;
}
