type SearchItem = {
	id: number;
	title: string;
	url: string;
	priceHtml: string;
	imageUrl: string;
	imageAlt: string;
};

type SearchPayload = {
	query: string;
	found: number;
	items: SearchItem[];
	viewAllUrl: string;
};

type AjaxSearchConfig = {
	restUrl: string;
	ajaxUrl?: string;
	transport: 'rest' | 'ajax';
	nonce?: string;
	minLength: number;
	delay: number;
	maxResults: number;
	showLoading: boolean;
	i18n: {
		searching: string;
		minChars: string;
		noResults: string;
		error: string;
		viewAll: string;
	};
};

declare global {
	interface Window {
		tailwindscoreAjaxSearch?: AjaxSearchConfig;
	}
}

/** Browser timer id (`window.setTimeout`); not NodeJS.Timeout from @types/node. */
let searchTimeout: number | null = null;
let currentAbort: AbortController | null = null;

const RESULT_CACHE_LIMIT = 20;
const resultCache = new Map<string, SearchPayload>();

function getConfig(): AjaxSearchConfig | null {
	return window.tailwindscoreAjaxSearch ?? null;
}

function rememberCachedResult(query: string, payload: SearchPayload): void {
	resultCache.delete(query);
	resultCache.set(query, payload);

	while (resultCache.size > RESULT_CACHE_LIMIT) {
		const oldest = resultCache.keys().next().value;
		if (typeof oldest !== 'string') {
			break;
		}
		resultCache.delete(oldest);
	}
}

function getCachedResult(query: string): SearchPayload | undefined {
	return resultCache.get(query);
}

function escapeHtml(text: string): string {
	return text
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

function getField(form: HTMLFormElement): HTMLInputElement | null {
	return form.querySelector<HTMLInputElement>(
		'#ts-search-field, .ts-command-search-field__input, [type="search"]'
	);
}

function getSuggestPanel(form: HTMLFormElement): HTMLElement | null {
	return form.querySelector<HTMLElement>('.ts-search-suggest, #ts-search-suggest');
}

function showPanel(panel: HTMLElement): void {
	panel.hidden = false;
	const field = panel.closest('form')?.querySelector<HTMLInputElement>('#ts-search-field');
	if (field) {
		field.setAttribute('aria-expanded', 'true');
	}
}

function hidePanel(panel: HTMLElement): void {
	panel.hidden = true;
	const field = panel.closest('form')?.querySelector<HTMLInputElement>('#ts-search-field');
	if (field) {
		field.setAttribute('aria-expanded', 'false');
	}
}

function setPanelHtml(panel: HTMLElement, html: string): void {
	panel.innerHTML = html;
	if (html.trim() !== '') {
		showPanel(panel);
	} else {
		hidePanel(panel);
	}
}

function renderLoading(panel: HTMLElement, config: AjaxSearchConfig): void {
	setPanelHtml(
		panel,
		`<div class="ts-search-suggest__loading" role="status">
			<span class="ts-search-suggest__spinner" aria-hidden="true"></span>
			<span>${config.i18n.searching}</span>
		</div>`
	);
}

function renderSearchItem(item: SearchItem): string {
	const title = escapeHtml(item.title);
	const url = escapeHtml(item.url);
	const imageUrl = escapeHtml(item.imageUrl);
	const imageAlt = escapeHtml(item.imageAlt || item.title);

	return `<div class="ts-search-suggest__item" role="option">
		<a class="ts-search-suggest__link" href="${url}">
			<span class="ts-search-suggest__media">
				<img src="${imageUrl}" alt="${imageAlt}" width="60" height="60" loading="lazy" decoding="async">
			</span>
			<span class="ts-search-suggest__body">
				<span class="ts-search-suggest__title">${title}</span>
				<span class="ts-search-suggest__price">${item.priceHtml}</span>
			</span>
		</a>
	</div>`;
}

function renderSearchResults(payload: SearchPayload, config: AjaxSearchConfig): string {
	if (!payload.items.length) {
		return `<p class="ts-search-suggest__message">${config.i18n.noResults}</p>`;
	}

	const itemsHtml = payload.items.map((item) => renderSearchItem(item)).join('');
	const viewAllUrl = escapeHtml(payload.viewAllUrl || '');
	const viewAllText = escapeHtml(config.i18n.viewAll);

	return `${itemsHtml}<div class="ts-search-suggest__footer">
		<a class="ts-search-suggest__view-all" href="${viewAllUrl}">
			<span>${viewAllText}</span>
		</a>
	</div>`;
}

function bindForm(form: HTMLFormElement, config: AjaxSearchConfig): void {
	const field = getField(form);
	const panel = getSuggestPanel(form);

	if (!field || !panel) {
		return;
	}

	panel.addEventListener('mousedown', (event) => {
		event.preventDefault();
	});

	field.addEventListener('focus', () => {
		if (panel.innerHTML.trim() !== '') {
			showPanel(panel);
		}
	});

	field.addEventListener('blur', () => {
		window.setTimeout(() => {
			if (!panel.matches(':hover') && !panel.contains(document.activeElement)) {
				hidePanel(panel);
			}
		}, 200);
	});

	field.addEventListener('input', () => {
		const query = field.value.trim();

		if (searchTimeout) {
			clearTimeout(searchTimeout);
			searchTimeout = null;
		}

		if (query.length >= config.minLength) {
			searchTimeout = window.setTimeout(() => {
				void performSearch(panel, config, query);
			}, config.delay);
			return;
		}

		if (query.length > 0) {
			setPanelHtml(panel, `<p class="ts-search-suggest__message">${config.i18n.minChars}</p>`);
			return;
		}

		setPanelHtml(panel, '');
	});

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Node)) {
			return;
		}
		if (!form.contains(target)) {
			hidePanel(panel);
		}
	});
}

async function fetchSearchPayload(
	config: AjaxSearchConfig,
	query: string,
	signal: AbortSignal
): Promise<SearchPayload | null> {
	if (config.transport !== 'rest' || !config.restUrl) {
		return null;
	}

	const url = new URL(config.restUrl, window.location.origin);
	url.searchParams.set('q', query);

	const response = await fetch(url.toString(), {
		method: 'GET',
		credentials: 'same-origin',
		headers: {
			Accept: 'application/json',
		},
		signal,
	});

	if (!response.ok) {
		return null;
	}

	return (await response.json()) as SearchPayload;
}

async function performSearch(
	panel: HTMLElement,
	config: AjaxSearchConfig,
	query: string
): Promise<void> {
	const cached = getCachedResult(query);
	if (cached) {
		setPanelHtml(panel, renderSearchResults(cached, config));
		return;
	}

	if (currentAbort) {
		currentAbort.abort();
		currentAbort = null;
	}

	if (config.showLoading) {
		renderLoading(panel, config);
	}

	const abort = new AbortController();
	currentAbort = abort;

	try {
		const payload = await fetchSearchPayload(config, query, abort.signal);
		if (!payload) {
			setPanelHtml(panel, `<p class="ts-search-suggest__message">${config.i18n.error}</p>`);
			return;
		}

		if (!payload.items.length) {
			setPanelHtml(panel, `<p class="ts-search-suggest__message">${config.i18n.noResults}</p>`);
			return;
		}

		rememberCachedResult(query, payload);
		setPanelHtml(panel, renderSearchResults(payload, config));
	} catch (error) {
		if (error instanceof DOMException && error.name === 'AbortError') {
			return;
		}
		setPanelHtml(panel, `<p class="ts-search-suggest__message">${config.i18n.error}</p>`);
	} finally {
		if (currentAbort === abort) {
			currentAbort = null;
		}
	}
}

export function initAjaxLiveSearch(): void {
	const config = getConfig();
	if (!config) {
		return;
	}

	document.querySelectorAll<HTMLFormElement>('form[data-ts-ajax-search]').forEach((form) => {
		bindForm(form, config);
	});
}
