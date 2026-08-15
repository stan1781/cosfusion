/**
 * 吸顶头部：滚动压缩 + 实测高度同步到 --ts-site-header-height（供 sticky 侧栏 offset）。
 */
function syncSiteHeaderHeight(header: HTMLElement): void {
	document.documentElement.style.setProperty(
		'--ts-site-header-height',
		`${header.getBoundingClientRect().height}px`,
	);
}

function initSiteHeaderSticky(): void {
	const header = document.querySelector<HTMLElement>('.site-header');

	if (!header) {
		return;
	}

	const sentinel =
		document.querySelector<HTMLElement>('.site-header__scroll-sentinel') ?? header;

	const applyCompact = (compact: boolean): void => {
		header.classList.toggle('site-header--compact', compact);
		header.classList.toggle('site-header--scrolled', compact);
		syncSiteHeaderHeight(header);
	};

	syncSiteHeaderHeight(header);

	if (typeof ResizeObserver !== 'undefined') {
		new ResizeObserver(() => {
			syncSiteHeaderHeight(header);
		}).observe(header);
	} else {
		window.addEventListener(
			'resize',
			() => {
				syncSiteHeaderHeight(header);
			},
			{ passive: true },
		);
	}

	const observer = new IntersectionObserver(
		(entries) => {
			const entry = entries[0];

			if (!entry) {
				return;
			}

			applyCompact(!entry.isIntersecting);
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

export {};
