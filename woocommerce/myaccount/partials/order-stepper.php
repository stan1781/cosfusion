<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var string $variant 变体：mini|full */
$variant = isset( $variant ) ? (string) $variant : 'mini';
/** @var WC_Order|null $order 订单对象，可为 null */
$order = isset( $order ) && $order instanceof WC_Order ? $order : null;

if ( ! $order || ! class_exists( 'WC_Order_Process_Customer_View' ) ) {
	return;
}

$bucket       = WC_Order_Process_Customer_View::get_bucket_for_order( $order );
$current_idx  = WC_Order_Process_Customer_View::get_customer_step_index( $bucket );
$steps        = WC_Order_Process_Customer_View::get_customer_steps();
$bucket_keys  = WC_Order_Process_Customer_View::get_bucket_keys();
$is_full      = 'full' === $variant;
?>
<ol class="wc-account-stepper wc-account-stepper--<?php echo esc_attr( $variant ); ?>" aria-label="<?php esc_attr_e( 'Order progress', 'tailwindscore' ); ?>">
	<?php foreach ( $bucket_keys as $index => $step_key ) : ?>
		<?php
		$step_info    = $steps[ $step_key ] ?? array( 'label' => $step_key, 'description' => '' );
		$is_completed = $index < $current_idx;
		$is_current   = $index === $current_idx;
		$state_class  = $is_completed ? 'is-completed' : ( $is_current ? 'is-current' : 'is-upcoming' );
		?>
		<li class="wc-account-stepper__step <?php echo esc_attr( $state_class ); ?>">
			<span class="wc-account-stepper__marker" aria-hidden="true">
				<?php echo $is_completed ? '&#10003;' : esc_html( (string) ( $index + 1 ) ); ?>
			</span>
			<span class="wc-account-stepper__label">
				<span class="wc-account-stepper__title"><?php echo esc_html( $step_info['label'] ); ?></span>
				<?php if ( $is_full && $is_current && ! empty( $step_info['description'] ) ) : ?>
					<span class="wc-account-stepper__description"><?php echo esc_html( $step_info['description'] ); ?></span>
				<?php endif; ?>
			</span>
		</li>
	<?php endforeach; ?>
</ol>
