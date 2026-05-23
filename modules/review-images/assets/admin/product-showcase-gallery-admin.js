(function ($) {
	'use strict';

	function parseIds(value) {
		if (!value) return [];
		return value.split(',').map(function (s) { return parseInt(s.trim(), 10); }).filter(function (n) { return !isNaN(n) && n > 0; });
	}

	function stringifyIds(ids) {
		ids = ids.filter(function (n, i, arr) { return arr.indexOf(n) === i; });
		return ids.join(',');
	}

    function renderItem(id, thumbUrl) {
        var $wrap = $('<div class="wc-showcase-image-item"/>').attr('data-id', id).css({ position: 'relative', width: '80px', height: '80px' });
        var $img = $('<img/>').attr('src', thumbUrl).css({ width: '100%', height: '100%', objectFit: 'cover', border: '1px solid #ddd', borderRadius: '4px' });
        var $del = $('<button type="button" class="button-link delete-showcase-image"/>').text('×').attr('title', '移除').css({ position: 'absolute', top: '2px', right: '4px', color: '#a94442', background: 'rgba(255,255,255,0.8)', borderRadius: '50%', width: '20px', height: '20px', lineHeight: '20px', padding: '0' });
        $wrap.append($img).append($del);
		return $wrap;
	}

	$(document).on('click', '#wc-product-showcase-gallery-admin-add', function (e) {
		e.preventDefault();
		var $idsInput = $('#wc-product-showcase-gallery-admin-ids');
		var currentIds = parseIds($idsInput.val());
        var frame = wp.media({
			title: '选择图片',
			button: { text: '选择' },
			multiple: true,
			library: { type: 'image' }
		});
		frame.on('select', function () {
			var selection = frame.state().get('selection');
			selection.each(function (attachment) {
				attachment = attachment.toJSON();
				if (!attachment || !attachment.id) return;
				var id = parseInt(attachment.id, 10);
				if (currentIds.indexOf(id) === -1) {
					currentIds.push(id);
					var thumb = (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) ? attachment.sizes.thumbnail.url : (attachment.url || '');
                    $('#wc-product-showcase-gallery-admin-list').append(renderItem(id, thumb));
				}
			});
			$idsInput.val(stringifyIds(currentIds));
		});
		frame.open();
	});

	$(document).on('click', '.delete-showcase-image', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.wc-showcase-image-item');
		var id = parseInt($item.attr('data-id'), 10);
		if (!id) return;
		var $idsInput = $('#wc-product-showcase-gallery-admin-ids');
		var ids = parseIds($idsInput.val());
		ids = ids.filter(function (n) { return n !== id; });
		$idsInput.val(stringifyIds(ids));
		$item.remove();
	});

})(jQuery);

