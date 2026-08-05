<?php
declare(strict_types=1);

/**
 * 免运进度条标记（Drawer / 购物车页共用）。
 *
 * @var array{progress?: array{qualified?: bool, percent?: int, message?: string}} $args
 */

defined( 'ABSPATH' ) || exit;

$progress = isset( $args['progress'] ) && is_array( $args['progress'] ) ? $args['progress'] : null;

if ( null === $progress || ! isset( $progress['percent'], $progress['message'] ) ) {
	return;
}

$percent = max( 0, min( 100, (int) $progress['percent'] ) );
$message = (string) $progress['message'];
?>
<div
	class="cart-free-shipping-progress"
	data-ts-cart-free-shipping-progress
	<?php echo ! empty( $progress['qualified'] ) ? ' data-qualified="true"' : ''; ?>
>
	<p class="cart-free-shipping-progress__message">
		<span class="cart-free-shipping-progress__icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'gift' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<span class="cart-free-shipping-progress__text"><?php echo esc_html( $message ); ?></span>
	</p>
	<div class="cart-free-shipping-progress__track">
		<div
			class="cart-free-shipping-progress__bar"
			role="progressbar"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo esc_attr( (string) $percent ); ?>"
			aria-label="<?php esc_attr_e( 'Free shipping progress', 'tailwindscore' ); ?>"
		>
			<div class="cart-free-shipping-progress__fill" style="width: <?php echo esc_attr( (string) $percent ); ?>%;"></div>
		</div>
		<span class="cart-free-shipping-progress__percent"><?php echo esc_html( (string) $percent ); ?>%</span>
	</div>
</div>
