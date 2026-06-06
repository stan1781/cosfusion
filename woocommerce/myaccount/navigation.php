<?php
declare(strict_types=1);

/**
 * My Account navigation
 *
 * @package TailwindScore
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$nav_icons        = tailwindscore_account_nav_icons();
$nav_short_labels = tailwindscore_account_nav_short_labels();

// Remove payment methods from account navigation
add_filter( 'woocommerce_account_menu_items', function( $items ) {
	unset( $items['payment-methods'] );
	return $items;
} );

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>">
	<ul class="ts-wc-account-nav__list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$icon        = $nav_icons[ $endpoint ] ?? 'user';
			$short_label = $nav_short_labels[ $endpoint ] ?? $label;
			?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a
					class="ts-wc-account-nav__link"
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					<?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>
					aria-label="<?php echo esc_attr( $label ); ?>"
				>
					<span class="ts-icon ts-wc-account-nav__icon" aria-hidden="true"><?php echo tailwindscore_icon( $icon ); ?></span>
					<span class="ts-wc-account-nav__label ts-wc-account-nav__label--full"><?php echo esc_html( $label ); ?></span>
					<span class="ts-wc-account-nav__label ts-wc-account-nav__label--short" aria-hidden="true"><?php echo esc_html( $short_label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
