<?php
declare(strict_types=1);

/**
 * 从认证界面返回公开站点的逃生链接。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="account-auth__escape-links" aria-label="<?php esc_attr_e( 'Back to site', 'tailwindscore' ); ?>">
	<a class="account-auth__escape-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php esc_html_e( 'Home', 'tailwindscore' ); ?>
	</a>
	<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
		<?php
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( $shop_url ) :
			?>
		<a class="account-auth__escape-link" href="<?php echo esc_url( $shop_url ); ?>">
			<?php esc_html_e( 'Shop', 'tailwindscore' ); ?>
		</a>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	$posts_page = get_option( 'page_for_posts' );
	if ( $posts_page ) :
		?>
		<a class="account-auth__escape-link" href="<?php echo esc_url( get_permalink( (int) $posts_page ) ); ?>">
			<?php esc_html_e( 'Blog', 'tailwindscore' ); ?>
		</a>
	<?php endif; ?>
</nav>
