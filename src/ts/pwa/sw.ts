/**
 * TailwindScore PWA Level 1 service worker.
 * PHP prepends self.__TAILWINDSCORE_PWA__ before this file when serving /ts-sw.js.
 *
 * 注意：与主站 DOM typings 并存时不用 webworker lib，避免 self 冲突。
 */

type ScPwaBootstrap = {
	cacheVersion: string;
	distPathPrefix: string;
	precache: string[];
	extensions: string[];
};

type ScServiceWorker = {
	__TAILWINDSCORE_PWA__?: ScPwaBootstrap;
	skipWaiting: () => Promise<void>;
	clients: { claim: () => Promise<void> };
	location: { origin: string };
	addEventListener: (type: string, listener: (event: ScSwEvent) => void) => void;
};

type ScSwEvent = {
	waitUntil: (promise: Promise<void>) => void;
	respondWith: (response: Promise<Response> | Response) => void;
	request: Request;
};

const sw = self as unknown as ScServiceWorker;

const boot: ScPwaBootstrap = sw.__TAILWINDSCORE_PWA__ ?? {
	cacheVersion: '0',
	distPathPrefix: '/wp-content/themes/starcore/assets/dist/',
	precache: [],
	extensions: ['.js', '.css', '.woff2', '.woff', '.ttf'],
};

const CACHE_NAME = `ts-static-v${boot.cacheVersion}`;

/** 单资源 / 整批 precache 上限，避免手机弱网把 SW 卡在 installing / activating。 */
const PRECACHE_ITEM_MS = 3000;
const PRECACHE_TOTAL_MS = 8000;

function isCacheableStatic(url: URL): boolean {
	if (!url.pathname.startsWith(boot.distPathPrefix)) {
		return false;
	}

	const lower = url.pathname.toLowerCase();
	return boot.extensions.some((ext) => lower.endsWith(ext));
}

function withTimeout<T>(promise: Promise<T>, ms: number): Promise<T | undefined> {
	return new Promise((resolve) => {
		const timer = setTimeout(() => resolve(undefined), ms);
		promise
			.then((value) => {
				clearTimeout(timer);
				resolve(value);
			})
			.catch(() => {
				clearTimeout(timer);
				resolve(undefined);
			});
	});
}

async function fetchForPrecache(url: string): Promise<Response | undefined> {
	const controller = new AbortController();
	const timer = setTimeout(() => controller.abort(), PRECACHE_ITEM_MS);

	try {
		const response = await fetch(url, {
			credentials: 'same-origin',
			cache: 'reload',
			signal: controller.signal,
		});
		return response.ok ? response : undefined;
	} catch {
		return undefined;
	} finally {
		clearTimeout(timer);
	}
}

/**
 * 尽力 precache；单项与整批均有超时。失败不抛错。
 * 不在 install 里调用，避免 Android WebAPK「安装中」被挂起。
 */
async function precacheAssets(): Promise<void> {
	const urls = boot.precache.filter((u) => typeof u === 'string' && u.length > 0);
	if (urls.length === 0) {
		return;
	}

	await withTimeout(
		(async () => {
			const cache = await caches.open(CACHE_NAME);
			await Promise.all(
				urls.map(async (url) => {
					const response = await fetchForPrecache(url);
					if (response) {
						try {
							await cache.put(url, response);
						} catch {
							// ignore put failures
						}
					}
				}),
			);
		})(),
		PRECACHE_TOTAL_MS,
	);
}

sw.addEventListener('install', (event) => {
	// 安装阶段只 skipWaiting，不阻塞 precache（手机 Chrome / WebAPK 依赖 install 尽快完成）。
	event.waitUntil(sw.skipWaiting());
});

sw.addEventListener('activate', (event) => {
	event.waitUntil(
		(async () => {
			const keys = await caches.keys();
			await Promise.all(
				keys
					.filter((key) => key.startsWith('ts-static-v') && key !== CACHE_NAME)
					.map((key) => caches.delete(key)),
			);
			await sw.clients.claim();
			await precacheAssets();
		})(),
	);
});

sw.addEventListener('fetch', (event) => {
	const request = event.request;
	if (request.method !== 'GET') {
		return;
	}

	// 文档导航一律走网络。
	if (request.mode === 'navigate') {
		return;
	}

	let url: URL;
	try {
		url = new URL(request.url);
	} catch {
		return;
	}

	if (url.origin !== sw.location.origin) {
		return;
	}

	if (!isCacheableStatic(url)) {
		return;
	}

	event.respondWith(
		(async () => {
			const cache = await caches.open(CACHE_NAME);
			const cached = await cache.match(request);
			if (cached) {
				return cached;
			}

			const response = await fetch(request);
			if (response.ok) {
				await cache.put(request, response.clone());
			}
			return response;
		})(),
	);
});
