/**
 * Toggle loading overlay on elements with .ts-loading-host (see components/_loading.scss).
 */
export function markLoadingHost(element: HTMLElement): void {
	element.classList.add('ts-loading-host');
}

export function setLoadingHost(element: HTMLElement | null | undefined, isLoading: boolean): void {
	if (!element) {
		return;
	}

	markLoadingHost(element);
	element.classList.toggle('is-loading', isLoading);
	element.setAttribute('aria-busy', isLoading ? 'true' : 'false');
}

export function setLoadingHosts(elements: Iterable<HTMLElement>, isLoading: boolean): void {
	for (const element of elements) {
		setLoadingHost(element, isLoading);
	}
}
