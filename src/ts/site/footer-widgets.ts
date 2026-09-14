/**
 * 页脚列区菜单 widget：&lt; lg 可折叠（默认收起），≥ lg 常开。
 */

import { TS_MEDIA_UP } from '../breakpoints';

const CONTENT_SELECTOR = '.site-footer__content';
const WIDGET_SELECTOR = '.widget';
const MENU_SELECTOR = '.menu';
const TITLE_SELECTOR = '.widget-title, .site-footer__title';

const CHEVRON_DOWN_SVG =
	'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.5 8.25L12 15.75L4.5 8.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

type FooterAccordionItem = {
	trigger: HTMLButtonElement;
	panel: HTMLElement;
};

const items: FooterAccordionItem[] = [];
let mq: MediaQueryList | null = null;
let uid = 0;

function isDesktop(): boolean {
	return Boolean(mq?.matches);
}

function setExpanded(item: FooterAccordionItem, expanded: boolean): void {
	item.trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');

	if (expanded) {
		item.panel.removeAttribute('hidden');
	} else {
		item.panel.setAttribute('hidden', '');
	}
}

function applyLayoutMode(): void {
	const desktop = isDesktop();

	items.forEach((item) => {
		if (desktop) {
			setExpanded(item, true);
			item.trigger.disabled = true;
			return;
		}

		item.trigger.disabled = false;
		setExpanded(item, false);
	});
}

function enhanceWidget(widget: HTMLElement): void {
	if (widget.hasAttribute('data-ts-footer-accordion')) {
		return;
	}

	const menu = widget.querySelector<HTMLElement>(MENU_SELECTOR);

	if (!menu) {
		return;
	}

	const title = widget.querySelector<HTMLElement>(TITLE_SELECTOR);

	if (!title || title.closest('button')) {
		return;
	}

	uid += 1;
	const panelId = `ts-footer-accordion-${uid}`;

	const panel = document.createElement('div');
	panel.className = 'site-footer__accordion-panel';
	panel.id = panelId;

	let sibling: ChildNode | null = title.nextSibling;

	while (sibling) {
		const next = sibling.nextSibling;
		panel.appendChild(sibling);
		sibling = next;
	}

	widget.appendChild(panel);

	const trigger = document.createElement('button');
	trigger.type = 'button';
	trigger.className = 'site-footer__accordion-trigger';
	trigger.setAttribute('aria-expanded', 'false');
	trigger.setAttribute('aria-controls', panelId);

	const label = document.createElement('span');
	label.className = 'site-footer__title';
	label.textContent = title.textContent?.trim() ?? '';

	const icon = document.createElement('span');
	icon.className = 'site-footer__accordion-icon';
	icon.setAttribute('aria-hidden', 'true');
	icon.innerHTML = CHEVRON_DOWN_SVG;

	trigger.append(label, icon);
	title.replaceWith(trigger);

	widget.setAttribute('data-ts-footer-accordion', '');
	widget.classList.add('site-footer__widget--accordion');

	const item: FooterAccordionItem = { trigger, panel };
	items.push(item);

	trigger.addEventListener('click', () => {
		if (isDesktop() || trigger.disabled) {
			return;
		}

		const expanded = trigger.getAttribute('aria-expanded') === 'true';
		setExpanded(item, !expanded);
	});
}

function initFooterWidgets(): void {
	const content = document.querySelector<HTMLElement>(CONTENT_SELECTOR);

	if (!content) {
		return;
	}

	content.querySelectorAll<HTMLElement>(WIDGET_SELECTOR).forEach(enhanceWidget);

	if (items.length === 0) {
		return;
	}

	mq = window.matchMedia(TS_MEDIA_UP.lg);
	applyLayoutMode();

	const onChange = (): void => {
		applyLayoutMode();
	};

	if (typeof mq.addEventListener === 'function') {
		mq.addEventListener('change', onChange);
	} else {
		mq.addListener(onChange);
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initFooterWidgets);
} else {
	initFooterWidgets();
}

export {};
