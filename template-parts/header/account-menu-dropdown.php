<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$nav_icons        = tailwindscore_account_nav_icons();
$nav_short_labels = tailwindscore_account_nav_short_labels();

// Remove payment methods from account menu dropdown
add_filter( 'woocommerce_account_menu_items', function( $items ) {
	unset( $items['payment-methods'] );
	return $items;
} );
?>
<div
	id="ts-account-menu-panel"
	class="ts-account-menu"
	role="menu"
	aria-label="<?php esc_attr_e( 'Account menu', 'tailwindscore' ); ?>"
	hidden
	:hidden="!accountOpen"
>
	<ul class="ts-account-menu__list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$icon        = $nav_icons[ $endpoint ] ?? 'user';
			$short_label = $nav_short_labels[ $endpoint ] ?? $label;
			?>
			<li class="ts-account-menu__item" role="none">
				<a
					class="ts-account-menu__link"
					role="menuitem"
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					aria-label="<?php echo esc_attr( $label ); ?>"
				>
					<span class="ts-icon ts-account-menu__icon" aria-hidden="true"><?php echo tailwindscore_icon( $icon ); ?></span>
					<span class="ts-account-menu__label"><?php echo esc_html( $short_label ); ?></span>
					<?php if ( 'dashboard' === $endpoint || 'orders' === $endpoint ) : ?>
						<?php tailwindscore_render_account_order_badge(); ?>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
