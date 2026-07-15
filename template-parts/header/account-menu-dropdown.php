<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$nav_icons        = tailwindscore_account_nav_icons();
$nav_short_labels = tailwindscore_account_nav_short_labels();

// 从账户菜单下拉中移除支付方式
add_filter( 'woocommerce_account_menu_items', function( $items ) {
	unset( $items['payment-methods'] );
	return $items;
} );
?>
<div
	id="ts-account-menu-panel"
	class="account-menu"
	role="menu"
	aria-label="<?php esc_attr_e( 'Account menu', 'tailwindscore' ); ?>"
	hidden
	:hidden="!accountOpen"
>
	<ul class="account-menu__list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$icon        = $nav_icons[ $endpoint ] ?? 'user';
			$short_label = $nav_short_labels[ $endpoint ] ?? $label;
			?>
			<li class="account-menu__item" role="none">
				<a
					class="account-menu__link"
					role="menuitem"
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					aria-label="<?php echo esc_attr( $label ); ?>"
				>
					<span class="icon account-menu__icon" aria-hidden="true"><?php echo tailwindscore_icon( $icon ); ?></span>
					<span class="account-menu__label"><?php echo esc_html( $short_label ); ?></span>
					<?php if ( 'dashboard' === $endpoint || 'orders' === $endpoint ) : ?>
						<?php tailwindscore_render_account_order_badge(); ?>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
