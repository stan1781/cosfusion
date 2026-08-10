<?php
declare(strict_types=1);

/**
 * 移动端：打开文章侧栏 Drawer。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! tailwindscore_has_post_sidebar() ) {
	return;
}

$panel_id = 'ts-post-sidebar-panel';
?>
<button
	type="button"
	class="post-sidebar-toggle btn btn-outline-primary"
	data-ts-post-sidebar-toggle
	aria-expanded="false"
	aria-controls="<?php echo esc_attr( $panel_id ); ?>"
>
	<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="post-sidebar-toggle__label"><?php esc_html_e( 'Sidebar', 'tailwindscore' ); ?></span>
</button>
