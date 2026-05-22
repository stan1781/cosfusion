<?php
declare(strict_types=1);

/**
 * Product loop start — shared grid class for archive-adjacent loops (related, upsells, etc.).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

$columns = (string) wc_get_loop_prop( 'columns' );
if ( '' === $columns ) {
	$columns = '4';
}
?>
<ul class="products columns-<?php echo esc_attr( $columns ); ?> ts-wc-product-grid">
