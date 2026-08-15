/**
 * 内容区块编辑页 — 代码 / 预览 tab + iframe srcdoc（含区块内联 script）。
 */
(function () {
	'use strict';

	var config = window.tsContentBlockPreview || {};
	var codeEditorConfig = window.tsContentBlockCodeEditor || null;
	var DEBOUNCE_MS = 400;
	var timer = null;
	var activeTab = 'code';

	var htmlEditor = null;
	var cssEditor = null;

	/**
	 * 初始化 CodeMirror 编辑器；不可用时保持原生 textarea。
	 */
	function initCodeEditors() {
		if (!codeEditorConfig || typeof wp === 'undefined' || typeof wp.CodeMirror === 'undefined') {
			return;
		}

		var htmlEl = document.getElementById('ts_block_html');
		var cssEl = document.getElementById('ts_block_css');

		if (htmlEl) {
			var htmlSettings = JSON.parse(JSON.stringify(codeEditorConfig));
			htmlSettings.codemirror.mode = 'text/html';
			htmlEditor = wp.CodeMirror.fromTextArea(htmlEl, htmlSettings.codemirror);
		}

		if (cssEl) {
			var cssSettings = JSON.parse(JSON.stringify(codeEditorConfig));
			cssSettings.codemirror.mode = 'css';
			cssEditor = wp.CodeMirror.fromTextArea(cssEl, cssSettings.codemirror);
		}
	}

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

	function getHtmlValue() {
		if (htmlEditor) {
			return htmlEditor.getValue();
		}
		var htmlEl = document.getElementById('ts_block_html');
		return htmlEl ? htmlEl.value : '';
	}

	function getCssValue() {
		if (cssEditor) {
			return cssEditor.getValue();
		}
		var cssEl = document.getElementById('ts_block_css');
		return cssEl ? cssEl.value : '';
	}

	function refresh() {
		var iframe = document.getElementById('ts-block-preview-frame');

		if (!iframe) {
			return;
		}

		iframe.srcdoc = buildSrcdoc(getHtmlValue(), getCssValue());
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

		if (!root) {
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

		if (htmlEditor) {
			htmlEditor.on('change', scheduleRefresh);
		} else {
			var htmlEl = document.getElementById('ts_block_html');
			if (htmlEl) {
				htmlEl.addEventListener('input', scheduleRefresh);
			}
		}

		if (cssEditor) {
			cssEditor.on('change', scheduleRefresh);
		} else {
			var cssEl = document.getElementById('ts_block_css');
			if (cssEl) {
				cssEl.addEventListener('input', scheduleRefresh);
			}
		}
	}

	function boot() {
		initCodeEditors();
		bind();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
