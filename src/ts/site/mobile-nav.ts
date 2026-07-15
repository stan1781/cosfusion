export {};

import type { AlpineMagics } from '../types/alpine';

type MobileNavPanel = {
	id: string;
	title: string;
	url?: string;
};

type MobileNavConfig = {
	rootTitle?: string;
};

type MobileNavData = {
	rootTitle: string;
	panelStack: MobileNavPanel[];
	trackEl: HTMLElement | null;
	_clickBound: boolean;
	_onTrackTransitionEnd: ((event: TransitionEvent) => void) | null;
	readonly headerTitle: string;
	readonly headerUrl: string;
	readonly canGoBack: boolean;
	init(this: MobileNavState): void;
	bindNav(this: MobileNavState): void;
	handlePanelClick(this: MobileNavState, event: Event): void;
	openPanel(this: MobileNavState, panelId: string, title: string, url?: string): void;
	goBack(this: MobileNavState): void;
	reset(this: MobileNavState): void;
	syncTrack(this: MobileNavState, snap?: boolean): void;
	focusFirstLink(this: MobileNavState, panelId: string): void;
};

type MobileNavState = AlpineMagics & MobileNavData;

function getNavViewport(trackEl: HTMLElement): HTMLElement | null {
	return trackEl.parentElement instanceof HTMLElement ? trackEl.parentElement : null;
}

function applyTrackOffset(trackEl: HTMLElement, index: number): void {
	const viewport = getNavViewport(trackEl);

	if (!viewport) {
		return;
	}

	const offsetPx = index * viewport.clientWidth;
	trackEl.style.transform = `translate3d(-${offsetPx}px, 0, 0)`;
}

function syncPanelA11y(panels: HTMLElement[], activeIndex: number): void {
	panels.forEach((panel, panelIndex) => {
		const isActive = panelIndex === activeIndex;
		panel.toggleAttribute('inert', !isActive);
		panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
	});
}

document.addEventListener('alpine:init', () => {
	window.Alpine.data(
		'mobileNav',
		(config: MobileNavConfig = {}): MobileNavData => ({
			rootTitle: config.rootTitle ?? 'Menu',
			panelStack: [],
			trackEl: null,
			_clickBound: false,
			_onTrackTransitionEnd: null,

			init(this: MobileNavState) {
				this.panelStack = [{ id: 'root', title: this.rootTitle }];

				this.$nextTick(() => {
					this.bindNav();
					this.syncTrack(true);
				});
			},

			bindNav(this: MobileNavState) {
				this.trackEl =
					this.$el.querySelector<HTMLElement>('[data-ts-nav-track]') ?? null;

				if (!this._clickBound) {
					this.$el.addEventListener('click', (event: Event) => {
						this.handlePanelClick(event);
					});
					this._clickBound = true;
				}
			},

			handlePanelClick(this: MobileNavState, event: Event) {
				const target = event.target;

				if (!(target instanceof Element)) {
					return;
				}

				const trigger = target.closest<HTMLButtonElement>('[data-ts-nav-panel-trigger]');

				if (!trigger || !this.$el.contains(trigger)) {
					return;
				}

				event.preventDefault();

				const panelId = trigger.dataset.tsNavPanel ?? '';
				const title = trigger.dataset.tsNavTitle ?? '';
				const url = trigger.dataset.tsNavUrl ?? '';

				this.openPanel(panelId, title, url);
			},

			get headerTitle() {
				const current = this.panelStack[this.panelStack.length - 1];
				return current?.title ?? this.rootTitle;
			},

			get headerUrl() {
				const current = this.panelStack[this.panelStack.length - 1];
				return current?.url ?? '';
			},

			get canGoBack() {
				return this.panelStack.length > 1;
			},

			openPanel(this: MobileNavState, panelId: string, title: string, url?: string) {
				if (!panelId || panelId === 'root') {
					return;
				}

				if (!this.trackEl) {
					this.bindNav();
				}

				this.panelStack.push({ id: panelId, title, url });
				this.syncTrack();
			},

			goBack(this: MobileNavState) {
				if (this.panelStack.length <= 1) {
					return;
				}

				this.panelStack.pop();
				this.syncTrack();
			},

			reset(this: MobileNavState) {
				this.panelStack = [{ id: 'root', title: this.rootTitle }];
				this.syncTrack(true);
			},

			syncTrack(this: MobileNavState, snap = false) {
				if (!this.trackEl) {
					return;
				}

				const panels = Array.from(
					this.trackEl.querySelectorAll<HTMLElement>('.nav__panel[data-ts-nav-panel]')
				);
				const activeId = this.panelStack[this.panelStack.length - 1]?.id ?? 'root';
				const index = Math.max(
					0,
					panels.findIndex((panel) => panel.dataset.tsNavPanel === activeId)
				);

				if (this._onTrackTransitionEnd) {
					this.trackEl.removeEventListener('transitionend', this._onTrackTransitionEnd);
					this._onTrackTransitionEnd = null;
				}

				if (snap) {
					this.trackEl.classList.add('nav__track--instant');
				}

				applyTrackOffset(this.trackEl, index);

				const triggers = this.$el.querySelectorAll<HTMLButtonElement>(
					'[data-ts-nav-panel-trigger]'
				);
				triggers.forEach((trigger) => {
					const isExpanded =
						this.panelStack.length > 1 &&
						this.panelStack[this.panelStack.length - 1]?.id === trigger.dataset.tsNavPanel;
					trigger.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
				});

				if (snap) {
					void this.trackEl.offsetWidth;
					this.trackEl.classList.remove('nav__track--instant');
					syncPanelA11y(panels, index);
					return;
				}

				this._onTrackTransitionEnd = (event: TransitionEvent) => {
					if (event.target !== this.trackEl || event.propertyName !== 'transform') {
						return;
					}

					if (this._onTrackTransitionEnd) {
						this.trackEl?.removeEventListener('transitionend', this._onTrackTransitionEnd);
						this._onTrackTransitionEnd = null;
					}

					syncPanelA11y(panels, index);
					this.focusFirstLink(activeId);
				};

				this.trackEl.addEventListener('transitionend', this._onTrackTransitionEnd);
			},

			focusFirstLink(this: MobileNavState, panelId: string) {
				window.requestAnimationFrame(() => {
					const panel = this.trackEl?.querySelector<HTMLElement>(
						`.nav__panel[data-ts-nav-panel="${panelId}"]`
					);
					const link = panel?.querySelector<HTMLElement>(
						'a.nav__link, a.nav__mega-link, a.nav__mega-heading, button[data-ts-nav-panel-trigger]'
					);

					link?.focus();
				});
			},
		})
	);
});
