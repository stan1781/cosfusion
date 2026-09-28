/**
 * 主题级单例 Tooltip：委托 `[data-ts-tooltip]`，hover / focus 显示。
 */

const ATTR = 'data-ts-tooltip';
const SELECTOR = `[${ATTR}]`;

let tipEl: HTMLElement | null = null;
let activeEl: Element | null = null;
let hideTimer = 0;

function ensureTip(): HTMLElement {
	if (tipEl && document.body.contains(tipEl)) {
		return tipEl;
	}

	tipEl = document.createElement('div');
	tipEl.className = 'ts-tooltip';
	tipEl.setAttribute('role', 'tooltip');
	tipEl.hidden = true;
	document.body.append(tipEl);
	return tipEl;
}

function clearHideTimer(): void {
	if (hideTimer) {
		window.clearTimeout(hideTimer);
		hideTimer = 0;
	}
}

function hideTip(): void {
	clearHideTimer();
	const tip = tipEl;
	if (!tip) {
		return;
	}
	tip.hidden = true;
	tip.classList.remove('is-visible');
	tip.textContent = '';
	activeEl = null;
}

function positionTip(trigger: Element, tip: HTMLElement): void {
	const rect = trigger.getBoundingClientRect();
	const tipRect = tip.getBoundingClientRect();
	const gap = 8;
	let top = rect.top - tipRect.height - gap;
	let left = rect.left + rect.width / 2 - tipRect.width / 2;

	if (top < 8) {
		top = rect.bottom + gap;
		tip.dataset.placement = 'bottom';
	} else {
		tip.dataset.placement = 'top';
	}

	const maxLeft = window.innerWidth - tipRect.width - 8;
	left = Math.max(8, Math.min(left, maxLeft));

	tip.style.top = `${Math.round(top)}px`;
	tip.style.left = `${Math.round(left)}px`;
}

function showTip(trigger: Element): void {
	const text = trigger.getAttribute(ATTR)?.trim() ?? '';
	if ('' === text) {
		hideTip();
		return;
	}

	clearHideTimer();
	const tip = ensureTip();
	activeEl = trigger;
	tip.textContent = text;
	tip.hidden = false;
	// 先测量再定位
	positionTip(trigger, tip);
	tip.classList.add('is-visible');
}

function findTrigger(target: EventTarget | null): Element | null {
	if (!(target instanceof Element)) {
		return null;
	}
	return target.closest(SELECTOR);
}

function onPointerOver(event: Event): void {
	const trigger = findTrigger(event.target);
	if (!trigger) {
		return;
	}
	showTip(trigger);
}

function onPointerOut(event: PointerEvent): void {
	const trigger = findTrigger(event.target);
	if (!trigger || trigger !== activeEl) {
		return;
	}
	const related = event.relatedTarget;
	if (related instanceof Node && trigger.contains(related)) {
		return;
	}
	hideTimer = window.setTimeout(hideTip, 80);
}

function onFocusIn(event: Event): void {
	const trigger = findTrigger(event.target);
	if (trigger) {
		showTip(trigger);
	}
}

function onFocusOut(event: FocusEvent): void {
	const trigger = findTrigger(event.target);
	if (!trigger || trigger !== activeEl) {
		return;
	}
	const related = event.relatedTarget;
	if (related instanceof Node && trigger.contains(related)) {
		return;
	}
	hideTip();
}

function onScrollOrResize(): void {
	if (activeEl) {
		hideTip();
	}
}

function onKeydown(event: KeyboardEvent): void {
	if (event.key === 'Escape') {
		hideTip();
	}
}

let bound = false;

export function initTooltip(): void {
	if (bound) {
		return;
	}
	bound = true;

	document.addEventListener('pointerover', onPointerOver, true);
	document.addEventListener('pointerout', onPointerOut, true);
	document.addEventListener('focusin', onFocusIn, true);
	document.addEventListener('focusout', onFocusOut, true);
	document.addEventListener('keydown', onKeydown, true);
	window.addEventListener('scroll', onScrollOrResize, true);
	window.addEventListener('resize', onScrollOrResize);
}
