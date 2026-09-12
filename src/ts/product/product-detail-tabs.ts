/**
 * 单品详情 Tab / 手风琴
 * - 桌面：下划线滑动 Tab
 * - 移动端：手风琴
 * - 描述限高 + 展开/收起
 * - 内嵌 Size Guide（复用 size-guide.ts 表格结构）
 */

import type { SizeGuideConfig } from './types';
import { renderTableHTML } from './size-guide';

const TAB_EASING = 'cubic-bezier(0.65, 0, 0.35, 1)';
const TAB_DURATION_MS = 400;
const TAB_TRANSITION = `transform ${TAB_DURATION_MS / 1000}s ${TAB_EASING}, width ${TAB_DURATION_MS / 1000}s ${TAB_EASING}`;

/**
 * 读取 Size Guide 配置
 */
function getSizeGuideConfig(): SizeGuideConfig | null {
	const cfg = window.tailwindscoreProduct?.sizeGuide;
	return cfg?.enabled ? cfg : null;
}

/**
 * 渲染内嵌 Size Guide 结构（与弹窗 body 一致）
 */
function renderSizeGuideContent(cfg: SizeGuideConfig, unit: 'cm' | 'inch', container: HTMLElement): void {
	let html = '';

	if (cfg.showUnitSwitcher && cfg.table) {
		html += '<div class="size-guide-dialog__header">';
		html += '<div class="size-guide-dialog__controls">';
		html += '<div class="size-guide-unit-toggle" data-ts-size-guide-unit-toggle-embed>';
		html += `<button type="button" class="size-guide-unit-btn ${unit === 'cm' ? 'is-active' : ''}" data-unit="cm">cm</button>`;
		html += `<button type="button" class="size-guide-unit-btn ${unit === 'inch' ? 'is-active' : ''}" data-unit="inch">inch</button>`;
		html += '</div>';
		html += '</div>';
		html += '</div>';
	}

	html += '<div class="size-guide-dialog__body" data-ts-size-guide-body-embed></div>';

	container.innerHTML = html;
}

/**
 * 按单位更新 Size Guide 正文
 */
function updateSizeGuideBody(cfg: SizeGuideConfig, unit: 'cm' | 'inch', bodyElement: HTMLElement): void {
	if (cfg.legacyHtml) {
		bodyElement.innerHTML = `<div class="size-guide-legacy">${cfg.legacyHtml}</div>`;
	} else if (cfg.table) {
		bodyElement.innerHTML = renderTableHTML(cfg.table, unit, false);
	}
}

/**
 * 初始化内嵌 Size Guide 与单位切换
 */
function initEmbeddedSizeGuide(): void {
	const cfg = getSizeGuideConfig();
	if (!cfg || !cfg.hasContent) {
		return;
	}

	const containers = document.querySelectorAll<HTMLElement>('[data-ts-product-size-guide-embed]');
	if (!containers.length) {
		return;
	}

	let currentUnit: 'cm' | 'inch' = cfg.defaultUnit === 'inch' ? 'inch' : 'cm';

	containers.forEach((container) => {
		renderSizeGuideContent(cfg, currentUnit, container);

		const bodyElement = container.querySelector<HTMLElement>('[data-ts-size-guide-body-embed]');
		if (!bodyElement) {
			return;
		}

		updateSizeGuideBody(cfg, currentUnit, bodyElement);

		if (cfg.showUnitSwitcher && cfg.table) {
			const unitToggle = container.querySelector('[data-ts-size-guide-unit-toggle-embed]');
			if (unitToggle) {
				unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((btn) => {
					btn.addEventListener('click', () => {
						const newUnit = btn.getAttribute('data-unit') as 'cm' | 'inch';
						if (!newUnit) {
							return;
						}

						currentUnit = newUnit;

						unitToggle.querySelectorAll<HTMLButtonElement>('.size-guide-unit-btn').forEach((b) => {
							b.classList.toggle('is-active', b.getAttribute('data-unit') === newUnit);
						});

						updateSizeGuideBody(cfg, newUnit, bodyElement);
					});
				});
			}
		}
	});
}

/**
 * 移动端手风琴
 */
function initAccordion(): void {
	const triggers = document.querySelectorAll<HTMLButtonElement>('[data-ts-accordion-trigger]');

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', () => {
			const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
			const content = trigger.nextElementSibling as HTMLElement | null;

			if (!content || !content.hasAttribute('data-ts-accordion-content')) {
				return;
			}

			if (isExpanded) {
				trigger.setAttribute('aria-expanded', 'false');
				content.setAttribute('hidden', '');
			} else {
				trigger.setAttribute('aria-expanded', 'true');
				content.removeAttribute('hidden');
			}
		});
	});
}

function prefersReducedMotion(): boolean {
	return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * 相对 tablist 测量目标 tab，返回指示器 left / width
 */
function measureTab(tablist: HTMLElement, tab: HTMLButtonElement): { left: number; width: number } {
	const listRect = tablist.getBoundingClientRect();
	const tabRect = tab.getBoundingClientRect();
	return {
		left: tabRect.left - listRect.left + tablist.scrollLeft,
		width: tabRect.width,
	};
}

/**
 * 将下划线指示器对齐到目标 Tab（transform + width）
 */
function moveIndicator(
	tablist: HTMLElement,
	indicator: HTMLElement,
	tab: HTMLButtonElement,
	animate: boolean,
): void {
	const { left, width } = measureTab(tablist, tab);
	const reduceMotion = prefersReducedMotion();
	const shouldAnimate = animate && !reduceMotion;

	if (!shouldAnimate) {
		indicator.style.transition = 'none';
	} else {
		indicator.style.transition = TAB_TRANSITION;
	}

	indicator.style.width = `${width}px`;
	indicator.style.transform = `translate3d(${left}px, 0, 0)`;

	if (!shouldAnimate) {
		void indicator.offsetWidth;
		if (!reduceMotion) {
			indicator.style.transition = TAB_TRANSITION;
		}
	}
}

/**
 * 激活指定桌面 Tab 与对应 panel
 */
function activateTab(
	tablist: HTMLElement,
	tabs: HTMLButtonElement[],
	panels: HTMLElement[],
	indicator: HTMLElement,
	target: HTMLButtonElement,
	animate: boolean,
): void {
	const panelId = target.getAttribute('aria-controls');

	tabs.forEach((tab) => {
		const selected = tab === target;
		tab.setAttribute('aria-selected', selected ? 'true' : 'false');
		tab.tabIndex = selected ? 0 : -1;
	});

	panels.forEach((panel) => {
		if (panelId && panel.id === panelId) {
			panel.removeAttribute('hidden');
		} else {
			panel.setAttribute('hidden', '');
		}
	});

	moveIndicator(tablist, indicator, target, animate);
}

/**
 * 桌面下划线 Tab：点击 / 键盘 / ResizeObserver
 */
function initDesktopTabs(): void {
	const tablist = document.querySelector<HTMLElement>('.product-detail-tabs');
	if (!tablist) {
		return;
	}

	const indicator = tablist.querySelector<HTMLElement>('.product-detail-tabs__indicator');
	const tabs = Array.from(tablist.querySelectorAll<HTMLButtonElement>('[data-ts-product-detail-tab]'));
	const panelsRoot = tablist.parentElement;
	const panels = panelsRoot
		? Array.from(panelsRoot.querySelectorAll<HTMLElement>('[data-ts-product-detail-panel]'))
		: [];

	if (!indicator || tabs.length === 0) {
		return;
	}

	let animatingUntil = 0;

	const active =
		tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') ?? tabs[0];

	// 双 rAF：等布局稳定后再无动画定位，避免 width=0 的首帧
	requestAnimationFrame(() => {
		requestAnimationFrame(() => {
			activateTab(tablist, tabs, panels, indicator, active, false);
			tablist.classList.add('is-ready');
		});
	});

	const selectTab = (target: HTMLButtonElement, animate: boolean): void => {
		if (animate) {
			animatingUntil = performance.now() + TAB_DURATION_MS + 50;
		}
		activateTab(tablist, tabs, panels, indicator, target, animate);
	};

	tabs.forEach((tab) => {
		tab.addEventListener('click', () => {
			if (tab.getAttribute('aria-selected') === 'true') {
				return;
			}
			selectTab(tab, true);
		});

		tab.addEventListener('keydown', (event) => {
			const key = event.key;
			if (key !== 'ArrowLeft' && key !== 'ArrowRight' && key !== 'Home' && key !== 'End') {
				return;
			}

			event.preventDefault();

			const index = tabs.indexOf(tab);
			let nextIndex = index;

			if (key === 'ArrowLeft') {
				nextIndex = (index - 1 + tabs.length) % tabs.length;
			} else if (key === 'ArrowRight') {
				nextIndex = (index + 1) % tabs.length;
			} else if (key === 'Home') {
				nextIndex = 0;
			} else if (key === 'End') {
				nextIndex = tabs.length - 1;
			}

			const next = tabs[nextIndex];
			if (!next) {
				return;
			}

			next.focus();
			selectTab(next, true);
		});
	});

	const syncIndicator = (): void => {
		if (performance.now() < animatingUntil) {
			return;
		}
		const current =
			tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') ?? tabs[0];
		if (current) {
			moveIndicator(tablist, indicator, current, false);
		}
	};

	if (typeof ResizeObserver !== 'undefined') {
		const ro = new ResizeObserver(syncIndicator);
		ro.observe(tablist);
	} else {
		window.addEventListener('resize', syncIndicator);
	}
}

/**
 * 描述限高：超出时显示 Show more / Show less，折叠态底部渐变
 */
function initDescriptionClamps(): void {
	const roots = document.querySelectorAll<HTMLElement>('[data-ts-product-description]');

	roots.forEach((root) => {
		const clip = root.querySelector<HTMLElement>('[data-ts-product-description-clip]');
		const body = root.querySelector<HTMLElement>('[data-ts-product-description-body]');
		const toggle = root.querySelector<HTMLButtonElement>('[data-ts-product-description-toggle]');
		if (!clip || !body || !toggle) {
			return;
		}

		const labelMore = toggle.dataset.labelMore || 'Show more';
		const labelLess = toggle.dataset.labelLess || 'Show less';

		const syncOverflow = (): void => {
			const expanded = root.classList.contains('is-expanded');
			if (expanded) {
				toggle.hidden = false;
				return;
			}

			// 临时态下比较 scrollHeight 与裁剪高度
			const overflows = body.scrollHeight > clip.clientHeight + 1;
			toggle.hidden = !overflows;
			root.classList.toggle('is-clampable', overflows);
		};

		toggle.addEventListener('click', () => {
			const willExpand = !root.classList.contains('is-expanded');
			root.classList.toggle('is-expanded', willExpand);
			toggle.setAttribute('aria-expanded', willExpand ? 'true' : 'false');
			toggle.textContent = willExpand ? labelLess : labelMore;
			syncOverflow();
		});

		syncOverflow();

		if (typeof ResizeObserver !== 'undefined') {
			const ro = new ResizeObserver(() => {
				if (!root.classList.contains('is-expanded')) {
					syncOverflow();
				}
			});
			ro.observe(body);
			ro.observe(clip);
		}
	});
}

/**
 * 初始化单品详情 Tab / 手风琴
 */
export function initProductDetailTabs(): void {
	if (!document.querySelector('.product-detail-sections')) {
		return;
	}

	initEmbeddedSizeGuide();
	initAccordion();
	initDesktopTabs();
	initDescriptionClamps();
}
