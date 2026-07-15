<?php
declare(strict_types=1);

/**
 * 商品循环开头 — 归档相邻循环（相关商品、追加销售等）共用的网格类。
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
