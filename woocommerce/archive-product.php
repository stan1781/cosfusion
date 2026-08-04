<?php
declare(strict_types=1);

/**
 * 展示商品归档的模板，包括作为主文章类型归档的商店首页。
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );

get_template_part( 'template-parts/woocommerce/archive/layout' );

do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
