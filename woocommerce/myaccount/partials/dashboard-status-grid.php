<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var array<string, int> $counts 各状态订单数量 */
$counts      = isset( $counts ) && is_array( $counts ) ? $counts : array();
$current     = function_exists( 'tailwindscore_account_current_bucket' ) ? tailwindscore_account_current_bucket() : '';
$endpoint    = isset( $bucket_endpoint ) ? (string) $bucket_endpoint : 'dashboard';
$steps       = WC_Order_Process_Customer_View::get_customer_steps();
$bucket_keys = WC_Order_Process_Customer_View::get_bucket_keys();
$icon_map    = array(
	'confirmed' => 'clipboard',
	'packing'   => 'package',
	'shipping'  => 'truck',
	'completed' => 'circle-check',
);
?>
<nav class="ts-wc-account-status-grid" aria-label="<?php esc_attr_e( 'Order status overview', 'tailwindscore' ); ?>">
	<?php foreach ( $bucket_keys as $bucket ) : ?>
		<?php
		$step      = $steps[ $bucket ] ?? array( 'label' => $bucket );
		$count     = isset( $counts[ $bucket ] ) ? (int) $counts[ $bucket ] : 0;
		$is_active = $current === $bucket;
		$url       = function_exists( 'tailwindscore_account_bucket_url' )
			? tailwindscore_account_bucket_url( $bucket, $endpoint )
			: wc_get_account_endpoint_url( $endpoint );
		$icon      = $icon_map[ $bucket ] ?? 'circle-check';
		?>
		<a
			href="<?php echo esc_url( $url ); ?>"
			class="ts-wc-account-status-grid__tile<?php echo $is_active ? ' ts-wc-account-status-grid__tile--active' : ''; ?><?php echo 0 === $count ? ' ts-wc-account-status-grid__tile--empty' : ''; ?>"
			<?php echo $is_active ? 'aria-current="true"' : ''; ?>
		>
			<span class="ts-wc-account-status-grid__icon ts-wc-account-status-grid__icon--<?php echo esc_attr( $bucket ); ?>">
				<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( $icon ); ?></span>
			</span>
			<span class="ts-wc-account-status-grid__body">
				<span class="ts-wc-account-status-grid__count"><?php echo esc_html( (string) $count ); ?></span>
				<span class="ts-wc-account-status-grid__label"><?php echo esc_html( $step['label'] ); ?></span>
			</span>
		</a>
	<?php endforeach; ?>
</nav>
