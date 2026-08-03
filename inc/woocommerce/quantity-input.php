<?php
declare(strict_types=1);

/**
 * Shared quantity stepper markup and WooCommerce class hooks.
 */
function tailwindscore_quantity_input_classes( array $classes ): array {
	if ( ! in_array( 'ts-quantity__input', $classes, true ) ) {
		$classes[] = 'ts-quantity__input';
	}

	return $classes;
}
add_filter( 'woocommerce_quantity_input_classes', 'tailwindscore_quantity_input_classes' );

/**
 * Render the theme quantity stepper.
 *
 * @param array<string, mixed> $args Template arguments for template-parts/components/quantity-input.php.
 */
function tailwindscore_render_quantity_input( array $args = array() ): void {
	get_template_part( 'template-parts/components/quantity-input', null, $args );
}
