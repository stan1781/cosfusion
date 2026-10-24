/**
 * Customizer：一点应用字体推荐组合。
 */
(function (api) {
	'use strict';

	if (!api) {
		return;
	}

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		var button = target.closest('[data-ts-font-preset]');
		if (!(button instanceof HTMLElement)) {
			return;
		}

		var heading = button.getAttribute('data-ts-heading') || '';
		var body = button.getAttribute('data-ts-body') || '';
		if ('' === heading || '' === body) {
			return;
		}

		event.preventDefault();

		var headingSetting = api('typography_heading_font');
		var bodySetting = api('typography_body_font');
		if (!headingSetting || !bodySetting) {
			return;
		}

		headingSetting.set(heading);
		bodySetting.set(body);
	});
})(wp.customize);
