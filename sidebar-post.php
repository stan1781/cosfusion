<?php
declare(strict_types=1);

/**
 * 文章页专用侧栏（桌面内联；&lt; lg 作 Drawer 面板）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! tailwindscore_has_post_sidebar() ) {
	return;
}

$panel_id    = 'ts-post-sidebar-panel';
$title_id    = 'ts-post-sidebar-title';
$close_label = __( 'Close sidebar', 'tailwindscore' );
?>
<aside
	id="<?php echo esc_attr( $panel_id ); ?>"
	class="sidebar content-sidebar content-sidebar--post"
	data-ts-post-sidebar-panel
	aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
>
	<div class="content-sidebar__header">
		<h2 id="<?php echo esc_attr( $title_id ); ?>" class="content-sidebar__title">
			<?php esc_html_e( 'Sidebar', 'tailwindscore' ); ?>
		</h2>
		<?php
		tailwindscore_render_close_button(
			$close_label,
			array(
				'class' => 'content-sidebar__close',
				'attrs' => array(
					'data-ts-post-sidebar-close' => '',
				),
			)
		);
		?>
	</div>
	<div class="sidebar__content stack">
		<?php dynamic_sidebar( 'sidebar-post' ); ?>
	</div>
</aside>
