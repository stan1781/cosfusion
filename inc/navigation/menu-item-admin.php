<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 外观 > 菜单：CSS 类说明 + 导航缩略图字段。
 */

/**
 * 可写入「CSS Classes」的主题类说明（后台点选填入）。
 *
 * @return list<array{title: string, note: string, scope?: string, items: list<array{class: string, help: string}>}>
 */
function tailwindscore_nav_menu_css_class_guide_groups(): array {
	$groups = array(
		array(
			'title' => __( 'Top-level layout', 'tailwindscore' ),
			'note'  => __( 'Top-level items only. mega-menu is a full-width three-level Mega; add cols-2 / cols-3 for a compact Mega. cols-* alone makes a flat multi-column flyout.', 'tailwindscore' ),
			'scope' => 'top',
			'items' => array(
				array(
					'class' => 'mega-menu',
					'help'  => __( 'Mega menu (children = column titles, grandchildren = links)', 'tailwindscore' ),
				),
				array(
					'class' => 'cols-2',
					'help'  => __( '2-column submenu (desktop by default)', 'tailwindscore' ),
				),
				array(
					'class' => 'cols-3',
					'help'  => __( '3-column submenu (desktop by default; wins over cols-2 if both set)', 'tailwindscore' ),
				),
				array(
					'class' => 'cols-mobile',
					'help'  => __( 'Also apply multi-column layout in the mobile menu drawer', 'tailwindscore' ),
				),
			),
		),
		array(
			'title' => __( 'Badges', 'tailwindscore' ),
			'note'  => __( 'Any depth; only the first matching badge is shown.', 'tailwindscore' ),
			'scope' => 'any',
			'items' => array(
				array(
					'class' => 'hot',
					'help'  => __( 'Hot badge (red)', 'tailwindscore' ),
				),
				array(
					'class' => 'new',
					'help'  => __( 'New badge (green)', 'tailwindscore' ),
				),
				array(
					'class' => 'sale',
					'help'  => __( 'Sale badge (amber)', 'tailwindscore' ),
				),
			),
		),
	);

	/**
	 * @param list<array{title: string, note: string, scope?: string, items: list<array{class: string, help: string}>}> $groups 说明分组。
	 */
	return apply_filters( 'tailwindscore_nav_menu_css_class_guide_groups', $groups );
}

/**
 * 将缩略图 meta 挂到菜单项对象。
 *
 * @param object $item 菜单项。
 */
function tailwindscore_nav_menu_item_setup_thumbnail( object $item ): object {
	if ( empty( $item->ID ) ) {
		$item->ts_thumbnail_id = 0;

		return $item;
	}

	$item->ts_thumbnail_id = max(
		0,
		(int) get_post_meta( (int) $item->ID, tailwindscore_nav_item_thumbnail_meta_key(), true )
	);

	return $item;
}
add_filter( 'wp_setup_nav_menu_item', 'tailwindscore_nav_menu_item_setup_thumbnail' );

/**
 * 菜单编辑页加载媒体库。
 *
 * @param string $hook_suffix 当前 admin 页面 hook。
 */
function tailwindscore_nav_menu_item_admin_enqueue_scripts( string $hook_suffix ): void {
	if ( 'nav-menus.php' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_style(
		'common',
		'.ts-nav-item-thumb__preview img{display:block;width:60px;height:60px;margin:.5rem 0;object-fit:cover;border-radius:4px}.ts-nav-item-thumb__actions{display:flex;gap:.5rem;align-items:center}'
		. '.ts-nav-css-guide{margin:.5em 0 1em;padding:.75em 1em;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;clear:both}'
		. '.ts-nav-css-guide__heading{margin:.2em 0 .65em}'
		. '.ts-nav-css-guide__group+.ts-nav-css-guide__group{margin-top:.75em}'
		. '.ts-nav-css-guide__title{display:block;margin-bottom:.35em;font-weight:600}'
		. '.ts-nav-css-guide__chips{display:flex;flex-wrap:wrap;gap:.35em}'
		. '.ts-nav-css-guide__chip.is-active{background:#2271b1;border-color:#2271b1;color:#fff}'
		. '.ts-nav-css-guide__note{display:block;margin:.35em 0 0}'
	);
	wp_add_inline_script(
		'jquery-core',
		<<<'JS'
jQuery(function ($) {
	function tsNavThumbUpdatePreview($field, attachmentId, url) {
		var $preview = $field.find('.ts-nav-item-thumb__preview');
		var $img = $preview.find('img');

		if (attachmentId && url) {
			if (!$img.length) {
				$preview.html('<img src="" alt="" />');
				$img = $preview.find('img');
			}
			$img.attr('src', url);
			$preview.removeAttr('hidden');
		} else {
			$preview.empty().attr('hidden', 'hidden');
		}
	}

	$(document).on('click', '.ts-nav-item-thumb__select', function (event) {
		event.preventDefault();

		var $field = $(this).closest('.ts-nav-item-thumb');
		var $input = $field.find('.ts-nav-item-thumb__input');
		var frame = wp.media({
			title: $field.data('select-title') || 'Select image',
			button: { text: $field.data('select-title') || 'Select image' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$input.val(attachment.id);
			tsNavThumbUpdatePreview($field, attachment.id, thumbUrl);
		});

		frame.open();
	});

	$(document).on('click', '.ts-nav-item-thumb__remove', function (event) {
		event.preventDefault();

		var $field = $(this).closest('.ts-nav-item-thumb');
		$field.find('.ts-nav-item-thumb__input').val('');
		tsNavThumbUpdatePreview($field, 0, '');
	});

	function tsNavCssClassTokens(value) {
		return String(value || '').trim().split(/\s+/).filter(Boolean);
	}

	function tsNavSyncCssGuide($root) {
		$root.find('[data-ts-nav-css-guide]').each(function () {
			var $guide = $(this);
			var $input = $guide.closest('.menu-item-settings').find('.edit-menu-item-classes');
			var tokens = tsNavCssClassTokens($input.val());

			$guide.find('[data-ts-nav-class]').each(function () {
				var name = String($(this).attr('data-ts-nav-class') || '');
				$(this).toggleClass('is-active', tokens.indexOf(name) !== -1);
			});
		});
	}

	function tsNavPlaceCssGuides($root) {
		$root.find('.menu-item-settings').each(function () {
			var $settings = $(this);
			var $guide = $settings.children('[data-ts-nav-css-guide]');
			var $anchor = $settings.children('.field-xfn');

			if ($guide.length && $anchor.length) {
				$guide.insertAfter($anchor);
			}
		});
	}

	tsNavPlaceCssGuides($(document));
	tsNavSyncCssGuide($(document));

	$(document).on('menu-item-added', function (event, $markup) {
		var $root = $markup && $markup.jquery ? $markup : $(document);
		tsNavPlaceCssGuides($root);
		tsNavSyncCssGuide($root);
	});

	$(document).on('click', '[data-ts-nav-class]', function (event) {
		event.preventDefault();

		var name = String($(this).attr('data-ts-nav-class') || '');
		if (!name) {
			return;
		}

		var $settings = $(this).closest('.menu-item-settings');
		var $input = $settings.find('.edit-menu-item-classes');
		var tokens = tsNavCssClassTokens($input.val());
		var index = tokens.indexOf(name);

		if (index === -1) {
			tokens.push(name);
		} else {
			tokens.splice(index, 1);
		}

		$input.val(tokens.join(' ')).trigger('change');
		tsNavSyncCssGuide($settings);
	});

	$(document).on('input change', '.edit-menu-item-classes', function () {
		tsNavSyncCssGuide($(this).closest('.menu-item-settings'));
	});
});
JS
	);
}
add_action( 'admin_enqueue_scripts', 'tailwindscore_nav_menu_item_admin_enqueue_scripts' );

/**
 * 菜单项 CSS 类使用说明（紧挨核心「CSS Classes」字段）。
 *
 * @param int      $item_id 菜单项 post ID。
 * @param WP_Post  $item    菜单项 post。
 * @param int      $depth   深度。
 * @param stdClass $args    Walker 参数。
 */
function tailwindscore_nav_menu_item_css_class_guide_field( int $item_id, $item, int $depth, $args ): void {
	unset( $item_id, $item, $args );

	$is_top = 0 === $depth;
	$groups = array();

	foreach ( tailwindscore_nav_menu_css_class_guide_groups() as $group ) {
		$scope = isset( $group['scope'] ) ? (string) $group['scope'] : 'any';

		if ( 'top' === $scope && ! $is_top ) {
			continue;
		}

		$groups[] = $group;
	}

	if ( array() === $groups ) {
		return;
	}
	?>
	<div class="ts-nav-css-guide description description-wide" data-ts-nav-css-guide>
		<p class="ts-nav-css-guide__heading">
			<strong><?php esc_html_e( 'Theme CSS classes', 'tailwindscore' ); ?></strong>
			<span class="description">
				<?php esc_html_e( 'Click to add or remove from the CSS Classes field above (space-separated).', 'tailwindscore' ); ?>
			</span>
		</p>
		<?php foreach ( $groups as $group ) : ?>
			<?php
			$group_title = isset( $group['title'] ) ? (string) $group['title'] : '';
			$group_note  = isset( $group['note'] ) ? (string) $group['note'] : '';
			$group_items = isset( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : array();
			?>
			<div class="ts-nav-css-guide__group">
				<?php if ( '' !== $group_title ) : ?>
					<span class="ts-nav-css-guide__title"><?php echo esc_html( $group_title ); ?></span>
				<?php endif; ?>
				<span class="ts-nav-css-guide__chips">
					<?php foreach ( $group_items as $entry ) : ?>
						<?php
						$class_name = isset( $entry['class'] ) ? sanitize_html_class( (string) $entry['class'] ) : '';
						$help       = isset( $entry['help'] ) ? (string) $entry['help'] : '';

						if ( '' === $class_name ) {
							continue;
						}
						?>
						<button
							type="button"
							class="button ts-nav-css-guide__chip"
							data-ts-nav-class="<?php echo esc_attr( $class_name ); ?>"
							title="<?php echo esc_attr( $help ); ?>"
						>
							<?php echo esc_html( $class_name ); ?>
						</button>
					<?php endforeach; ?>
				</span>
				<?php if ( '' !== $group_note ) : ?>
					<span class="description ts-nav-css-guide__note"><?php echo esc_html( $group_note ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'tailwindscore_nav_menu_item_css_class_guide_field', 5, 4 );

/**
 * 菜单项自定义字段 UI。
 *
 * @param int      $item_id 菜单项 post ID。
 * @param WP_Post  $item    菜单项 post。
 * @param int      $depth   深度。
 * @param stdClass $args    Walker 参数。
 */
function tailwindscore_nav_menu_item_thumbnail_custom_field( int $item_id, $item, int $depth, $args ): void {
	unset( $item, $depth, $args );

	$attachment_id = (int) get_post_meta( $item_id, tailwindscore_nav_item_thumbnail_meta_key(), true );
	$preview_url   = '';

	if ( $attachment_id > 0 ) {
		$preview_url = (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
	}

	$hidden = '' === $preview_url ? ' hidden' : '';
	?>
	<p class="field-ts-nav-thumbnail description description-wide">
		<label for="edit-menu-item-ts-nav-thumbnail-<?php echo esc_attr( (string) $item_id ); ?>">
			<?php esc_html_e( 'Nav thumbnail', 'tailwindscore' ); ?>
		</label>
		<span class="description"><?php esc_html_e( 'Shown on submenu links; leave empty to use product category image when available.', 'tailwindscore' ); ?></span>
		<span
			class="ts-nav-item-thumb"
			data-select-title="<?php echo esc_attr__( 'Select nav thumbnail', 'tailwindscore' ); ?>"
		>
			<input
				type="hidden"
				class="ts-nav-item-thumb__input"
				id="edit-menu-item-ts-nav-thumbnail-<?php echo esc_attr( (string) $item_id ); ?>"
				name="menu-item-ts-nav-thumbnail[<?php echo esc_attr( (string) $item_id ); ?>]"
				value="<?php echo esc_attr( (string) $attachment_id ); ?>"
			/>
			<span class="ts-nav-item-thumb__preview"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( '' !== $preview_url ) : ?>
					<img src="<?php echo esc_url( $preview_url ); ?>" alt="" />
				<?php endif; ?>
			</span>
			<span class="ts-nav-item-thumb__actions">
				<button type="button" class="button ts-nav-item-thumb__select">
					<?php esc_html_e( 'Select image', 'tailwindscore' ); ?>
				</button>
				<button type="button" class="button-link ts-nav-item-thumb__remove">
					<?php esc_html_e( 'Remove', 'tailwindscore' ); ?>
				</button>
			</span>
		</span>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'tailwindscore_nav_menu_item_thumbnail_custom_field', 10, 4 );

/**
 * 保存菜单项缩略图 meta。
 *
 * @param int   $menu_id         菜单 term ID。
 * @param int   $menu_item_db_id 菜单项 post ID。
 * @param array $args            菜单项参数。
 */
function tailwindscore_nav_menu_item_save_thumbnail( int $menu_id, int $menu_item_db_id, array $args ): void {
	unset( $menu_id, $args );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$submitted = isset( $_POST['menu-item-ts-nav-thumbnail'] ) && is_array( $_POST['menu-item-ts-nav-thumbnail'] )
		? $_POST['menu-item-ts-nav-thumbnail']
		: array();

	if ( ! array_key_exists( $menu_item_db_id, $submitted ) ) {
		return;
	}

	$attachment_id = absint( $submitted[ $menu_item_db_id ] );

	if ( $attachment_id > 0 && 'attachment' !== get_post_type( $attachment_id ) ) {
		$attachment_id = 0;
	}

	if ( $attachment_id > 0 ) {
		update_post_meta( $menu_item_db_id, tailwindscore_nav_item_thumbnail_meta_key(), $attachment_id );
	} else {
		delete_post_meta( $menu_item_db_id, tailwindscore_nav_item_thumbnail_meta_key() );
	}
}
add_action( 'wp_update_nav_menu_item', 'tailwindscore_nav_menu_item_save_thumbnail', 10, 3 );
