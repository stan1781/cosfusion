<?php
declare(strict_types=1);

/**
 * 文章侧栏 Drawer 遮罩（&lt; lg）；面板本体在 sidebar-post.php。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! tailwindscore_has_post_sidebar() ) {
	return;
}
?>
<button
	type="button"
	class="post-sidebar-drawer__backdrop"
	data-ts-post-sidebar-backdrop
	data-ts-post-sidebar-close
	aria-label="<?php esc_attr_e( 'Close sidebar', 'tailwindscore' ); ?>"
	hidden
></button>
