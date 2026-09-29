<?php
declare(strict_types=1);

/**
 * 主题 PHP 统一加载入口。禁止在 functions.php 继续堆叠 require。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/navigation/nav.php';
require_once __DIR__ . '/navigation/mobile-shell.php';
require_once __DIR__ . '/navigation/header-bar.php';
require_once __DIR__ . '/navigation/footer-nav.php';
require_once __DIR__ . '/enqueue.php';
require_once __DIR__ . '/performance.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/contact/whatsapp.php';
require_once __DIR__ . '/template-tags.php';
require_once __DIR__ . '/blog/queries.php';
require_once __DIR__ . '/blog/widgets.php';
require_once __DIR__ . '/woocommerce/setup.php';
require_once __DIR__ . '/woocommerce/hooks.php';
require_once __DIR__ . '/woocommerce/archive-hero.php';
require_once __DIR__ . '/woocommerce/archive/attribute-filters.php';
require_once __DIR__ . '/woocommerce/archive/category-repository.php';
require_once __DIR__ . '/woocommerce/archive/filter-registry.php';
require_once __DIR__ . '/woocommerce/archive/context.php';
require_once __DIR__ . '/woocommerce/archive/ordering.php';
require_once __DIR__ . '/woocommerce/helpers.php';
require_once __DIR__ . '/woocommerce/product-image-aspect.php';
require_once __DIR__ . '/woocommerce/product-tabs.php';
require_once __DIR__ . '/woocommerce/product-assurances.php';
require_once __DIR__ . '/woocommerce/sale-badge.php';
require_once __DIR__ . '/woocommerce/loop.php';
require_once __DIR__ . '/woocommerce/loop-variant-swatches.php';
require_once __DIR__ . '/woocommerce/price-display.php';
require_once __DIR__ . '/woocommerce/cart-view.php';
require_once __DIR__ . '/woocommerce/cart-config.php';
require_once __DIR__ . '/woocommerce/product-carousel.php';
require_once __DIR__ . '/woocommerce/recently-viewed.php';
require_once __DIR__ . '/woocommerce/product-suggestions.php';
require_once __DIR__ . '/woocommerce/cart-product-suggestions.php';
require_once __DIR__ . '/woocommerce/product-cross-sells.php';
require_once __DIR__ . '/not-found.php';
require_once __DIR__ . '/woocommerce/shipping-rules.php';
require_once __DIR__ . '/woocommerce/cart-free-shipping-progress.php';
require_once __DIR__ . '/woocommerce/store-api.php';
require_once __DIR__ . '/woocommerce/store-api-cache.php';
require_once __DIR__ . '/woocommerce/cart-item-edit.php';
require_once __DIR__ . '/woocommerce/checkout-layout.php';
require_once __DIR__ . '/woocommerce/checkout-legal-modals.php';
require_once __DIR__ . '/woocommerce/checkout-region-restrictions.php';
require_once __DIR__ . '/woocommerce/checkout.php';
require_once __DIR__ . '/woocommerce/thankyou.php';
require_once __DIR__ . '/woocommerce/account-header.php';
require_once __DIR__ . '/woocommerce/account-nav.php';
require_once __DIR__ . '/woocommerce/ajax-search.php';
require_once __DIR__ . '/woocommerce/search-sku-redirect.php';
require_once __DIR__ . '/woocommerce/checkout-coupon.php';
require_once __DIR__ . '/woocommerce/order-device-os.php';

require_once __DIR__ . '/woocommerce/reviews.php';
require_once __DIR__ . '/config/kirki-config.php';
require_once __DIR__ . '/config/kirki/import-export.php';
require_once __DIR__ . '/config/kirki/home.php';
require_once __DIR__ . '/config/kirki/contact.php';
require_once __DIR__ . '/config/kirki/pwa.php';
require_once __DIR__ . '/pwa.php';
require_once __DIR__ . '/home/hero-carousel.php';
require_once __DIR__ . '/home/purge-legacy-homepage-mods.php';
require_once __DIR__ . '/page-templates.php';

require_once get_template_directory() . '/modules/typography/module.php';
require_once get_template_directory() . '/modules/gallery/module.php';
require_once get_template_directory() . '/modules/variations/module.php';
require_once get_template_directory() . '/modules/product-groups/module.php';
require_once get_template_directory() . '/modules/size-guide/module.php';
require_once get_template_directory() . '/modules/related-products/module.php';
require_once get_template_directory() . '/modules/quick-view/module.php';
require_once get_template_directory() . '/modules/review-images/module.php';
require_once get_template_directory() . '/modules/account-auth/module.php';
require_once get_template_directory() . '/modules/wishlist/module.php';
require_once get_template_directory() . '/modules/content-blocks/module.php';

require_once __DIR__ . '/integrations/wc-order-process-manager.php';
require_once __DIR__ . '/integrations/wc-mailnest.php';
require_once __DIR__ . '/integrations/wp-enhanced-tools.php';
require_once __DIR__ . '/integrations/paypal-pinterest-cart.php';

if ( is_admin() ) {
	require_once __DIR__ . '/admin/shortcode-help.php';
}
