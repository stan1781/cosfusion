<?php
declare(strict_types=1);

/**
 * My Account Dashboard
 *
 * @package TailwindScore
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Order_Process_Customer_View' ) ) {
	?>
	<p>
		<?php
		printf(
			/* translators: 1: user display name 2: logout url */
			wp_kses(
				__( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ),
				array( 'a' => array( 'href' => array() ) )
			),
			'<strong>' . esc_html( wp_get_current_user()->display_name ) . '</strong>',
			esc_url( wc_logout_url() )
		);
		?>
	</p>
	<?php
	do_action( 'woocommerce_account_dashboard' );
	return;
}

$user_id   = get_current_user_id();
$bucket    = tailwindscore_account_current_bucket();
$data      = WC_Order_Process_Customer_View::get_dashboard_data( $user_id, array( 'active_limit' => 5 ) );
$counts    = $data['counts'];
$drafts    = $data['draft_orders'];

if ( '' !== $bucket ) {
	$filtered = WC_Order_Process_Customer_View::get_orders_for_bucket( $user_id, $bucket, 5, 1 );
	$orders   = $filtered['orders'];
} else {
	$orders = $data['active_orders'];
}

wc_get_template( 'myaccount/partials/dashboard-hero.php' );

wc_get_template(
	'myaccount/partials/dashboard-draft-alert.php',
	array( 'draft_orders' => $drafts )
);

wc_get_template(
	'myaccount/partials/dashboard-status-grid.php',
	array(
		'counts'          => $counts,
		'bucket_endpoint' => 'dashboard',
	)
);

wc_get_template(
	'myaccount/partials/dashboard-order-feed.php',
	array( 'orders' => $orders )
);
?>
<nav class="ts-wc-account-shortcuts" aria-label="<?php esc_attr_e( 'Account shortcuts', 'tailwindscore' ); ?>">
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Addresses', 'tailwindscore' ); ?></a>
	<span aria-hidden="true">&middot;</span>
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php esc_html_e( 'Account details', 'tailwindscore' ); ?></a>
</nav>
<?php
do_action( 'woocommerce_account_dashboard' );

do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
