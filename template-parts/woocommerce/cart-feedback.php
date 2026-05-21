<?php
declare(strict_types=1);

$surface = isset( $args['surface'] ) ? sanitize_key( (string) $args['surface'] ) : 'drawer';

if ( ! in_array( $surface, array( 'drawer', 'inline', 'page' ), true ) ) {
	$surface = 'drawer';
}

$modifier = 'drawer' === $surface ? '' : ' ts-cart-feedback--' . $surface;
?>
<div
	class="ts-cart-feedback<?php echo esc_attr( $modifier ); ?>"
	data-ts-cart-feedback
	data-ts-cart-feedback-surface="<?php echo esc_attr( $surface ); ?>"
	role="status"
	aria-live="polite"
	hidden
></div>
