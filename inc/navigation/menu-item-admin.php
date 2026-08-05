<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 外观 > 菜单：菜单项导航缩略图字段。
 */

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
});
JS
	);
}
add_action( 'admin_enqueue_scripts', 'tailwindscore_nav_menu_item_admin_enqueue_scripts' );

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
