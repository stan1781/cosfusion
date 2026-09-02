/**
 * 外观 → 菜单：子树折叠 + Bulk Select 批量改父级。
 *
 * 依赖：jQuery、nav-menu（window.wpNavMenu）、wp_localize_script → window.tsNavMenusTree。
 */
(function ($) {
	'use strict';

	var i18n = window.tsNavMenusTree || {};
	var api = window.wpNavMenu;

	/**
	 * @param {JQuery} $item
	 * @return {number}
	 */
	function menuItemDepth($item) {
		if (typeof $item.menuItemDepth === 'function') {
			return parseInt($item.menuItemDepth(), 10) || 0;
		}
		var match = ($item.attr('class') || '').match(/menu-item-depth-(\d+)/);
		return match ? parseInt(match[1], 10) : 0;
	}

	/**
	 * @param {JQuery} $item
	 * @return {JQuery}
	 */
	function childMenuItems($item) {
		if (typeof $item.childMenuItems === 'function') {
			return $item.childMenuItems();
		}
		var depth = menuItemDepth($item);
		var $next = $item.next('.menu-item');
		var $children = $();
		while ($next.length && menuItemDepth($next) > depth) {
			$children = $children.add($next);
			$next = $next.next('.menu-item');
		}
		return $children;
	}

	/**
	 * @param {JQuery} $item
	 * @return {string}
	 */
	function itemDbId($item) {
		var id = $item.find('> .menu-item-settings .menu-item-data-db-id').val();
		if (id) {
			return String(id);
		}
		var match = ($item.attr('id') || '').match(/menu-item-(\d+)/);
		return match ? match[1] : '';
	}

	/**
	 * @param {JQuery} $maybeAncestor 候选祖先。
	 * @param {JQuery} $item          候选后代。
	 * @return {boolean}
	 */
	function isAncestorOf($maybeAncestor, $item) {
		var ancestorId = itemDbId($maybeAncestor);
		if (!ancestorId || $maybeAncestor[0] === $item[0]) {
			return false;
		}
		var parentId = String($item.find('> .menu-item-settings .menu-item-data-parent-id').val() || '0');
		var guard = 0;
		while (parentId !== '0' && guard < 50) {
			if (parentId === ancestorId) {
				return true;
			}
			var $parent = $('#menu-item-' + parentId);
			if (!$parent.length) {
				break;
			}
			parentId = String($parent.find('> .menu-item-settings .menu-item-data-parent-id').val() || '0');
			guard += 1;
		}
		return false;
	}

	/**
	 * @param {JQuery} $item
	 * @return {boolean}
	 */
	function hasChildren($item) {
		return childMenuItems($item).length > 0;
	}

	/**
	 * 刷新某父项折叠态下的后代可见性。
	 *
	 * @param {JQuery} $item
	 */
	function applyCollapseVisibility($item) {
		var $children = childMenuItems($item);
		var collapsed = $item.hasClass('is-ts-subtree-collapsed');

		$children.each(function () {
			var $child = $(this);
			if (collapsed) {
				$child.addClass('is-ts-subtree-hidden');
			} else {
				// 仅当所有折叠祖先都已展开时才显示。
				var hiddenByAncestor = false;
				var parentId = String($child.find('> .menu-item-settings .menu-item-data-parent-id').val() || '0');
				var guard = 0;
				while (parentId !== '0' && guard < 50) {
					var $parent = $('#menu-item-' + parentId);
					if (!$parent.length) {
						break;
					}
					if ($parent.hasClass('is-ts-subtree-collapsed')) {
						hiddenByAncestor = true;
						break;
					}
					parentId = String($parent.find('> .menu-item-settings .menu-item-data-parent-id').val() || '0');
					guard += 1;
				}
				$child.toggleClass('is-ts-subtree-hidden', hiddenByAncestor);
			}
		});
	}

	/**
	 * 全树按当前折叠标记重算隐藏。
	 */
	function refreshAllVisibility() {
		$('#menu-to-edit > .menu-item').removeClass('is-ts-subtree-hidden');
		$('#menu-to-edit > .menu-item.is-ts-subtree-collapsed').each(function () {
			applyCollapseVisibility($(this));
		});
	}

	/**
	 * @param {JQuery} $item
	 * @param {boolean} collapsed
	 */
	function setCollapsed($item, collapsed) {
		if (!hasChildren($item)) {
			return;
		}
		$item.toggleClass('is-ts-subtree-collapsed', collapsed);
		var $btn = $item.find('> .menu-item-bar .item-title > .ts-nav-tree-toggle');
		var $icon = $btn.find('.dashicons');
		if (collapsed) {
			$icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
			$btn.attr('aria-expanded', 'false');
		} else {
			$icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
			$btn.attr('aria-expanded', 'true');
		}
		refreshAllVisibility();
	}

	/**
	 * 为有子项的菜单行挂折叠按钮与数量。
	 */
	function ensureCollapseControls() {
		$('#menu-to-edit > .menu-item').each(function () {
			var $item = $(this);
			var $children = childMenuItems($item);
			var $handle = $item.find('> .menu-item-bar .menu-item-handle');
			var $titleLabel = $handle.find('> .item-title').first();
			// 清理早期版本挂在 label 外的折叠钮。
			$handle.children('.ts-nav-tree-toggle').remove();
			// 折叠钮放在 label 内、checkbox 之前，保证同一行：toggle → checkbox → 标题。
			var $existing = $titleLabel.find('> .ts-nav-tree-toggle');

			if (!$children.length) {
				$existing.remove();
				$titleLabel.find('.ts-nav-tree-child-count').remove();
				$item.removeClass('is-ts-subtree-collapsed');
				return;
			}

			if (!$existing.length) {
				var $btn = $('<button type="button" class="ts-nav-tree-toggle" aria-expanded="true" />');
				$btn.attr('aria-label', i18n.collapseSubtree || 'Collapse submenu');
				$btn.append('<span class="dashicons dashicons-arrow-down" aria-hidden="true"></span>');
				$titleLabel.prepend($btn);
			}

			var $count = $titleLabel.find('.ts-nav-tree-child-count');
			var countText = '(' + $children.length + ')';
			if (!$count.length) {
				$titleLabel.find('.menu-item-title').after(
					$('<span class="ts-nav-tree-child-count" />').text(countText)
				);
			} else {
				$count.text(countText);
			}

			// 同步图标与 aria。
			var collapsed = $item.hasClass('is-ts-subtree-collapsed');
			var $btnNow = $titleLabel.find('> .ts-nav-tree-toggle');
			$btnNow.attr('aria-expanded', collapsed ? 'false' : 'true');
			$btnNow.attr(
				'aria-label',
				collapsed
					? i18n.expandSubtree || 'Expand submenu'
					: i18n.collapseSubtree || 'Collapse submenu'
			);
			var $icon = $btnNow.find('.dashicons');
			$icon
				.toggleClass('dashicons-arrow-right', collapsed)
				.toggleClass('dashicons-arrow-down', !collapsed);
		});

		refreshAllVisibility();
	}

	function collapseAll() {
		$('#menu-to-edit > .menu-item').each(function () {
			var $item = $(this);
			if (hasChildren($item)) {
				$item.addClass('is-ts-subtree-collapsed');
			}
		});
		ensureCollapseControls();
	}

	function expandAll() {
		$('#menu-to-edit > .menu-item').removeClass('is-ts-subtree-collapsed is-ts-subtree-hidden');
		ensureCollapseControls();
	}

	/**
	 * 构建 Parent 下拉选项（排除会成环的项在点击 Move 时再校验）。
	 *
	 * @param {JQuery} $select
	 */
	function rebuildParentOptions($select) {
		var current = $select.val() || '0';
		var html = '<option value="0">' + (i18n.noParent || 'No Parent') + '</option>';

		$('#menu-to-edit > .menu-item').each(function () {
			var $item = $(this);
			var id = itemDbId($item);
			var title = $.trim($item.find('> .menu-item-bar .menu-item-title').first().text());
			if (!id) {
				return;
			}
			html +=
				'<option value="' +
				id +
				'">' +
				$('<div />').text(title).html() +
				'</option>';
		});

		$select.html(html);
		if ($select.find('option[value="' + current + '"]').length) {
			$select.val(current);
		} else {
			$select.val('0');
		}
	}

	/**
	 * @param {JQuery} $parentSelect
	 * @param {JQuery} $positionSelect
	 */
	function rebuildPositionOptions($parentSelect, $positionSelect) {
		var parentId = String($parentSelect.val() || '0');
		var siblings = [];

		$('#menu-to-edit > .menu-item').each(function () {
			var $item = $(this);
			var itemParent = String(
				$item.find('> .menu-item-settings .menu-item-data-parent-id').val() || '0'
			);
			if (itemParent !== parentId) {
				return;
			}
			siblings.push({
				title: $.trim($item.find('> .menu-item-bar .menu-item-title').first().text()),
			});
		});

		var beforeTpl = i18n.beforeItem || 'Before: %s';
		var html =
			'<option value="end">' + (i18n.endOfParent || 'End of parent') + '</option>';

		$.each(siblings, function (index, sibling) {
			var label = beforeTpl.replace('%s', sibling.title || '#' + (index + 1));
			html +=
				'<option value="' +
				(index + 1) +
				'">' +
				$('<div />').text(label).html() +
				'</option>';
		});

		var prev = $positionSelect.val() || 'end';
		$positionSelect.html(html);
		if ($positionSelect.find('option[value="' + prev + '"]').length) {
			$positionSelect.val(prev);
		} else {
			$positionSelect.val('end');
		}
	}

	/**
	 * @return {JQuery[]}
	 */
	function getSelectedRoots() {
		var selected = [];
		var $checked = $('#menu-to-edit .menu-item-checkbox:checked');

		$checked.each(function () {
			selected.push($(this).closest('li.menu-item'));
		});

		var roots = [];
		$.each(selected, function (_, $item) {
			var hasSelectedAncestor = false;
			$.each(selected, function (__, $other) {
				if ($other[0] === $item[0]) {
					return;
				}
				if (isAncestorOf($other, $item)) {
					hasSelectedAncestor = true;
					return false;
				}
			});
			if (!hasSelectedAncestor) {
				roots.push($item);
			}
		});

		// 文档顺序（上→下），便于相对顺序保持。
		roots.sort(function (a, b) {
			return a.index() - b.index();
		});

		return roots;
	}

	/**
	 * @param {JQuery} $item
	 * @param {string} parentId
	 * @return {boolean}
	 */
	function wouldCreateCycle($item, parentId) {
		if (parentId === '0') {
			return false;
		}
		if (itemDbId($item) === parentId) {
			return true;
		}
		var $parent = $('#menu-item-' + parentId);
		if (!$parent.length) {
			return true;
		}
		return isAncestorOf($item, $parent) || $item[0] === $parent[0];
	}

	/**
	 * @param {JQuery} $item
	 * @param {string} parentId
	 */
	function moveItemToParent($item, parentId) {
		var $parentDropdown = $item.find('> .menu-item-settings .edit-menu-item-parent');
		if (!$parentDropdown.length || !api || typeof api.changeMenuParent !== 'function') {
			return false;
		}
		$parentDropdown.val(parentId);
		api.changeMenuParent($parentDropdown);
		return true;
	}

	/**
	 * @param {JQuery} $item
	 * @param {number} orderOneBased
	 */
	function moveItemToOrder($item, orderOneBased) {
		var $orderDropdown = $item.find('> .menu-item-settings .edit-menu-item-order');
		if (!$orderDropdown.length || !api || typeof api.changeMenuOrder !== 'function') {
			return;
		}
		if (!$orderDropdown.find('option[value="' + orderOneBased + '"]').length) {
			return;
		}
		$orderDropdown.val(String(orderOneBased));
		api.changeMenuOrder($orderDropdown);
	}

	/**
	 * @param {JQuery} $toolbar
	 */
	function runBulkMove($toolbar) {
		var $error = $toolbar.find('.ts-nav-tree-move__error');
		$error.removeClass('is-visible').text('');

		var parentId = String($toolbar.find('.ts-nav-tree-move__parent').val() || '0');
		var position = String($toolbar.find('.ts-nav-tree-move__position').val() || 'end');
		var roots = getSelectedRoots();

		if (!roots.length) {
			$error.text(i18n.noSelection || 'Select at least one menu item.').addClass('is-visible');
			return;
		}

		var invalid = false;
		$.each(roots, function (_, $item) {
			if (wouldCreateCycle($item, parentId)) {
				invalid = true;
				return false;
			}
		});

		if (invalid) {
			$error
				.text(i18n.invalidParent || 'Cannot move an item under itself or its descendant.')
				.addClass('is-visible');
			return;
		}

		$.each(roots, function (_, $item) {
			moveItemToParent($item, parentId);
		});

		if (position !== 'end') {
			var start = parseInt(position, 10);
			if (!isNaN(start) && start > 0) {
				// 再次按新文档顺序取同一批根（已在目标父下）。
				var moved = [];
				$.each(roots, function (_, $old) {
					var id = itemDbId($old);
					var $fresh = $('#menu-item-' + id);
					if ($fresh.length) {
						moved.push($fresh);
					}
				});
				moved.sort(function (a, b) {
					return a.index() - b.index();
				});
				$.each(moved, function (index, $item) {
					moveItemToOrder($item, start + index);
				});
			}
		}

		ensureCollapseControls();
		rebuildAllToolbars();

		if (api && typeof api.refreshAdvancedAccessibility === 'function') {
			api.refreshAdvancedAccessibility();
		}
	}

	/**
	 * @param {string} suffix top|bottom
	 * @return {JQuery}
	 */
	function buildToolbar(suffix) {
		var $toolbar = $('<div class="ts-nav-tree-toolbar" />');

		var $collapse = $('<div class="ts-nav-tree-toolbar__collapse" />');
		$collapse.append(
			$('<button type="button" class="button ts-nav-tree-collapse-all" />').text(
				i18n.collapseAll || 'Collapse all'
			)
		);
		$collapse.append(
			$('<button type="button" class="button ts-nav-tree-expand-all" />').text(
				i18n.expandAll || 'Expand all'
			)
		);
		$toolbar.append($collapse);

		var $move = $('<div class="ts-nav-tree-move" />');
		var $parentLabel = $('<label class="ts-nav-tree-move__label" />');
		$parentLabel.append(
			$('<span />').text(i18n.parent || 'Parent')
		);
		var $parentSelect = $(
			'<select class="ts-nav-tree-move__parent" id="ts-nav-tree-move-parent-' + suffix + '" />'
		);
		$parentLabel.append($parentSelect);
		$move.append($parentLabel);

		var $posLabel = $('<label class="ts-nav-tree-move__label" />');
		$posLabel.append($('<span />').text(i18n.position || 'Position'));
		var $posSelect = $(
			'<select class="ts-nav-tree-move__position" id="ts-nav-tree-move-position-' + suffix + '" />'
		);
		$posLabel.append($posSelect);
		$move.append($posLabel);

		$move.append(
			$('<button type="button" class="button button-secondary ts-nav-tree-move__button" />').text(
				i18n.moveSelected || 'Move selected'
			)
		);
		$move.append($('<p class="ts-nav-tree-move__error" role="alert" />'));
		$toolbar.append($move);

		rebuildParentOptions($parentSelect);
		rebuildPositionOptions($parentSelect, $posSelect);

		return $toolbar;
	}

	function rebuildAllToolbars() {
		$('.ts-nav-tree-toolbar').each(function () {
			var $toolbar = $(this);
			var $parent = $toolbar.find('.ts-nav-tree-move__parent');
			var $position = $toolbar.find('.ts-nav-tree-move__position');
			rebuildParentOptions($parent);
			rebuildPositionOptions($parent, $position);
		});
	}

	function injectToolbars() {
		['top', 'bottom'].forEach(function (suffix) {
			var $host = $('#nav-menu-bulk-actions-' + suffix);
			if (!$host.length || $host.find('.ts-nav-tree-toolbar').length) {
				return;
			}
			$host.append(buildToolbar(suffix));
		});
	}

	function bindEvents() {
		$(document).on('click', '.ts-nav-tree-toggle', function (event) {
			event.preventDefault();
			event.stopPropagation();
			var $item = $(this).closest('li.menu-item');
			setCollapsed($item, !$item.hasClass('is-ts-subtree-collapsed'));
			ensureCollapseControls();
		});

		$(document).on('click', '.ts-nav-tree-collapse-all', function (event) {
			event.preventDefault();
			collapseAll();
		});

		$(document).on('click', '.ts-nav-tree-expand-all', function (event) {
			event.preventDefault();
			expandAll();
		});

		$(document).on('click', '.ts-nav-tree-move__button', function (event) {
			event.preventDefault();
			runBulkMove($(this).closest('.ts-nav-tree-toolbar'));
		});

		$(document).on('change', '.ts-nav-tree-move__parent', function () {
			var $toolbar = $(this).closest('.ts-nav-tree-toolbar');
			rebuildPositionOptions($(this), $toolbar.find('.ts-nav-tree-move__position'));
		});

		$(document).on('menu-item-added', function () {
			ensureCollapseControls();
			rebuildAllToolbars();
		});

		// 拖拽结束后刷新折叠控件与下拉。
		$(document).on('sortstop', '#menu-to-edit', function () {
			ensureCollapseControls();
			rebuildAllToolbars();
		});
	}

	$(function () {
		if (!$('#menu-to-edit').length) {
			return;
		}
		injectToolbars();
		ensureCollapseControls();
		bindEvents();
	});
})(jQuery);
