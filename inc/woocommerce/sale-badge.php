<?php
declare(strict_types=1);

/**
 * 促销徽章：最大折扣百分比，无法算百分比时回退金额差。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 由原价 / 特价计算节省额。
 *
 * @return array{percent: ?int, amount: float}|null
 */
function tailwindscore_sale_savings_from_prices( float $regular, float $sale ): ?array {
	$amount = $regular - $sale;

	if ( $amount <= 0 ) {
		return null;
	}

	$percent = null;

	if ( $regular > 0 ) {
		$rounded = (int) round( ( $amount / $regular ) * 100 );

		if ( $rounded > 0 ) {
			$percent = $rounded;
		}
	}

	return array(
		'percent' => $percent,
		'amount'  => $amount,
	);
}

/**
 * 是否应优先采用候选节省额（更大百分比；同档比金额）。
 *
 * @param array{percent: ?int, amount: float} $candidate 候选。
 * @param array{percent: ?int, amount: float} $current   当前最佳。
 */
function tailwindscore_sale_savings_is_better( array $candidate, array $current ): bool {
	$candidate_percent = null !== $candidate['percent'] ? (int) $candidate['percent'] : -1;
	$current_percent   = null !== $current['percent'] ? (int) $current['percent'] : -1;

	if ( $candidate_percent !== $current_percent ) {
		return $candidate_percent > $current_percent;
	}

	return (float) $candidate['amount'] > (float) $current['amount'];
}

/**
 * 商品促销节省（可变取各促销变体最大折扣）。
 *
 * @return array{percent: ?int, amount: float}|null
 */
function tailwindscore_get_product_sale_savings( WC_Product $product ): ?array {
	if ( ! $product->is_on_sale() ) {
		return null;
	}

	$best = null;

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( (int) $child_id );

			if ( ! $variation instanceof WC_Product || ! $variation->is_on_sale() ) {
				continue;
			}

			$pair = tailwindscore_sale_savings_from_prices(
				(float) $variation->get_regular_price(),
				(float) $variation->get_sale_price()
			);

			if ( null === $pair ) {
				continue;
			}

			if ( null === $best || tailwindscore_sale_savings_is_better( $pair, $best ) ) {
				$best = $pair;
			}
		}

		return $best;
	}

	return tailwindscore_sale_savings_from_prices(
		(float) $product->get_regular_price(),
		(float) $product->get_sale_price()
	);
}

/**
 * 格式化徽章文案：优先 `-12%`，否则 `-$10`。
 *
 * @param array{percent: ?int, amount: float} $savings 节省数据。
 */
function tailwindscore_format_sale_badge_label( array $savings ): string {
	$percent = $savings['percent'] ?? null;

	if ( null !== $percent && (int) $percent > 0 ) {
		return '-' . (int) $percent . '%';
	}

	$amount = (float) ( $savings['amount'] ?? 0 );

	if ( $amount <= 0 || ! function_exists( 'wc_price' ) ) {
		return '';
	}

	$formatted = wp_strip_all_tags( wc_price( $amount ) );

	if ( '' === $formatted ) {
		return '';
	}

	return '-' . $formatted;
}

/**
 * 促销徽章 HTML（`.onsale`）；无可算折扣时返回空字符串。
 *
 * @param WC_Product $product     商品。
 * @param string     $extra_class 额外 class（空格分隔）。
 */
function tailwindscore_get_sale_badge_html( WC_Product $product, string $extra_class = '' ): string {
	$savings = tailwindscore_get_product_sale_savings( $product );

	if ( null === $savings ) {
		return '';
	}

	$label = tailwindscore_format_sale_badge_label( $savings );

	if ( '' === $label ) {
		return '';
	}

	$classes = trim( 'onsale ' . $extra_class );
	$html    = '<span class="' . esc_attr( $classes ) . '">' . esc_html( $label ) . '</span>';
	$post    = get_post( $product->get_id() );

	if ( ! $post instanceof WP_Post ) {
		return $html;
	}

	/**
	 * 与 Woo `woocommerce_sale_flash` 对齐，便于第三方覆盖或清空。
	 *
	 * @param string     $html    徽章 HTML。
	 * @param WP_Post    $post    商品文章。
	 * @param WC_Product $product 商品。
	 */
	return (string) apply_filters( 'woocommerce_sale_flash', $html, $post, $product );
}
