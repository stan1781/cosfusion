/**
 * 内容区块编辑页 — 代码 / 预览 tab + iframe srcdoc（含区块内联 script）。
 */
(function () {
	'use strict';

	var config = window.tsContentBlockPreview || {};
	var DEBOUNCE_MS = 400;
	var timer = null;
	var activeTab = 'code';

	/**
	 * 避免 textarea 中的 </style> 提前关闭预览文档里的 style 块。
	 *
	 * @param {string} css
	 * @returns {string}
	 */
	function escapeStyleClose(css) {
		return String(css).replace(/<\/style/gi, '<\\/style');
	}

	/**
	 * 拼完整 HTML 文档；整页写入 srcdoc 以便执行 <script>。
	 *
	 * @param {string} html
	 * @param {string} css
	 * @returns {string}
	 */
	function buildSrcdoc(html, css) {
		var resetCss = config.resetCss || '';
		var tokenCss = config.tokenCss || '';

		return (
			'<!DOCTYPE html>\n' +
			'<html lang="en">\n' +
			'<head>\n' +
			'<meta charset="utf-8">\n' +
			'<meta name="viewport" content="width=device-width, initial-scale=1">\n' +
			'<style>' +
			escapeStyleClose(resetCss) +
			'\n' +
			escapeStyleClose(tokenCss) +
			'</style>\n' +
			'<style>' +
			escapeStyleClose(css) +
			'</style>\n' +
			'</head>\n' +
			'<body>\n' +
			'<div class="content-block content-block--preview" data-ts-block="preview">\n' +
			html +
			'\n</div>\n' +
			'</body>\n' +
			'</html>'
		);
	}

	function refresh() {
		var iframe = document.getElementById('ts-block-preview-frame');
		var htmlEl = document.getElementById('ts_block_html');
		var cssEl = document.getElementById('ts_block_css');

		if (!iframe || !htmlEl || !cssEl) {
			return;
		}

		iframe.srcdoc = buildSrcdoc(htmlEl.value, cssEl.value);
	}

	function scheduleRefresh() {
		if (activeTab !== 'preview') {
			return;
		}
		if (timer) {
			window.clearTimeout(timer);
		}
		timer = window.setTimeout(refresh, DEBOUNCE_MS);
	}

	/**
	 * @param {'code'|'preview'} tab
	 */
	function setTab(tab) {
		var root = document.querySelector('[data-ts-block-editor]');
		if (!root) {
			return;
		}

		activeTab = tab;

		root.querySelectorAll('[data-ts-block-tab]').forEach(function (btn) {
			var isActive = btn.getAttribute('data-ts-block-tab') === tab;
			btn.classList.toggle('is-active', isActive);
			btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
		});

		root.querySelectorAll('[data-ts-block-panel]').forEach(function (panel) {
			var show = panel.getAttribute('data-ts-block-panel') === tab;
			panel.hidden = !show;
		});

		if (tab === 'preview') {
			if (timer) {
				window.clearTimeout(timer);
				timer = null;
			}
			refresh();
		}
	}

	function bind() {
		var root = document.querySelector('[data-ts-block-editor]');
		var htmlEl = document.getElementById('ts_block_html');
		var cssEl = document.getElementById('ts_block_css');

		if (!root || !htmlEl || !cssEl) {
			return;
		}

		root.querySelectorAll('[data-ts-block-tab]').forEach(function (btn) {
			btn.addEventListener('click', function (event) {
				event.preventDefault();
				var tab = btn.getAttribute('data-ts-block-tab');
				if (tab === 'code' || tab === 'preview') {
					setTab(tab);
				}
			});
		});

		htmlEl.addEventListener('input', scheduleRefresh);
		cssEl.addEventListener('input', scheduleRefresh);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
})();
