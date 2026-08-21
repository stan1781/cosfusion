/**
 * 归档 Hero 分类描述：超出两行时显示 More / Less。
 */

export {};

const ROOT_SELECTOR = '[data-ts-archive-desc]';
const BODY_SELECTOR = '.term-description, .page-description';
const TOGGLE_SELECTOR = '.wc-archive-hero__desc-toggle';
const CLAMPED_CLASS = 'is-clamped';
const EXPANDED_CLASS = 'is-expanded';

function getBody(root: HTMLElement): HTMLElement | null {
	return root.querySelector<HTMLElement>(BODY_SELECTOR);
}

function getToggle(root: HTMLElement): HTMLButtonElement | null {
	return root.querySelector<HTMLButtonElement>(TOGGLE_SELECTOR);
}

function setExpanded(root: HTMLElement, expanded: boolean): void {
	const toggle = getToggle(root);
	const more = toggle?.dataset.labelMore ?? 'More';
	const less = toggle?.dataset.labelLess ?? 'Less';

	root.classList.toggle(EXPANDED_CLASS, expanded);
	root.classList.toggle(CLAMPED_CLASS, !expanded);

	if (!toggle) {
		return;
	}

	toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
	toggle.textContent = expanded ? less : more;
}

function syncRoot(root: HTMLElement): void {
	const body = getBody(root);
	const toggle = getToggle(root);

	if (!body || !toggle) {
		return;
	}

	const wasExpanded = root.classList.contains(EXPANDED_CLASS);

	// 必须在 clamp 状态下测量，否则展开后 scrollHeight === clientHeight。
	root.classList.add(CLAMPED_CLASS);
	root.classList.remove(EXPANDED_CLASS);

	const overflows = body.scrollHeight > body.clientHeight + 1;

	if (!overflows) {
		root.classList.remove(CLAMPED_CLASS, EXPANDED_CLASS);
		toggle.hidden = true;
		toggle.setAttribute('aria-expanded', 'false');
		toggle.textContent = toggle.dataset.labelMore ?? 'More';
		return;
	}

	toggle.hidden = false;
	setExpanded(root, wasExpanded);
}

function onToggleClick(event: MouseEvent): void {
	const target = event.target;

	if (!(target instanceof Element)) {
		return;
	}

	const toggle = target.closest<HTMLButtonElement>(TOGGLE_SELECTOR);

	if (!toggle) {
		return;
	}

	const root = toggle.closest<HTMLElement>(ROOT_SELECTOR);

	if (!root || toggle.hidden) {
		return;
	}

	event.preventDefault();
	setExpanded(root, !root.classList.contains(EXPANDED_CLASS));
}

function syncAll(): void {
	document.querySelectorAll<HTMLElement>(ROOT_SELECTOR).forEach(syncRoot);
}

function initArchiveHeroDesc(): void {
	if (!document.querySelector(ROOT_SELECTOR)) {
		return;
	}

	syncAll();
	document.addEventListener('click', onToggleClick);

	let resizeFrame = 0;
	window.addEventListener('resize', () => {
		window.cancelAnimationFrame(resizeFrame);
		resizeFrame = window.requestAnimationFrame(syncAll);
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initArchiveHeroDesc, { once: true });
} else {
	initArchiveHeroDesc();
}
