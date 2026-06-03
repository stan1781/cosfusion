export {};

/**
 * Compress sticky site header after the announcement bar scrolls away.
 */
function initSiteHeaderSticky(): void {
	const header = document.querySelector<HTMLElement>('.ts-site-header');

	if (!header) {
		return;
	}

	const sentinel =
		document.querySelector<HTMLElement>('.ts-site-header__scroll-sentinel') ?? header;

	const observer = new IntersectionObserver(
		(entries) => {
			const entry = entries[0];

			if (!entry) {
				return;
			}

			const compact = !entry.isIntersecting;
			header.classList.toggle('ts-site-header--compact', compact);
			header.classList.toggle('ts-site-header--scrolled', compact);
		},
		{
			root: null,
			threshold: 0,
		},
	);

	observer.observe(sentinel);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initSiteHeaderSticky);
} else {
	initSiteHeaderSticky();
}
