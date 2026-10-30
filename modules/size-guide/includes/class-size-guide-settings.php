<?php
declare(strict_types=1);

/**
 * 尺码指南设置与前台配置 payload。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 尺码指南模块设置。
 */
final class Tailwindscore_Size_Guide_Settings {

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) tailwindscore_mod( 'size_guide_enabled', true );
	}

	/**
	 * @return string conditional|always
	 */
	public static function get_display_mode(): string {
		$plugin = get_option( 'wc_enhanced_tools_size_guide_display_mode', 'conditional' );
		$mode   = (string) tailwindscore_mod( 'size_guide_display_mode', $plugin );
		return in_array( $mode, array( 'conditional', 'always' ), true ) ? $mode : 'conditional';
	}

	/**
	 * @return bool
	 */
	public static function inline_enabled(): bool {
		return (bool) tailwindscore_mod( 'size_guide_inline_enabled', true );
	}

	/**
	 * 解析商品的绑定属性。
	 *
	 * @param int   $product_id 商品 ID。
	 * @param array $resolved   插件解析的尺码指南。
	 */
	public static function resolve_bind_attribute( int $product_id, array $resolved ): string {
		if (! empty( $resolved['bindAttribute'] ) ) {
			return (string) $resolved['bindAttribute'];
		}
		if ( function_exists( 'tailwindscore_is_size_variation_attribute' ) && class_exists( 'Tailwindscore_Variation_Settings' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( 'variable' ) ) {
				foreach ( array_keys( $product->get_variation_attributes() ) as $attr ) {
					if ( tailwindscore_is_size_variation_attribute( $attr ) ) {
						return sanitize_title( $attr );
					}
				}
			}
		}
		return 'pa_size';
	}

	/**
	 * 构建 tailwindscoreProduct.sizeGuide 配置。
	 *
	 * @param int|null $product_id 商品 ID。
	 * @return array<string, mixed>|null
	 */
	public static function get_frontend_config( ?int $product_id = null ): ?array {
		if ( ! self::is_enabled() || ! class_exists( 'Product_Size_Guide' ) ) {
			return null;
		}

		$product_id = (int) ( $product_id ?? 0 );
		if ( $product_id < 1 && function_exists( 'is_product' ) && is_product() ) {
			$product_id = (int) get_queried_object_id();
		}
		if ( $product_id < 1 && function_exists( 'wc_get_product' ) ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				$product_id = $product->get_id();
			}
		}

		if ( $product_id < 1 ) {
			return null;
		}

			$resolved = Product_Size_Guide::resolve( $product_id );
			if ( $resolved['source'] === 'empty' ) {
				return null;
			}

			// 仅有表头、无 rows 时不算有内容（避免空 Tab / 空弹窗）。
			$data            = $resolved['data'] ?? null;
			$has_table_rows  = is_array( $data ) && ! empty( $data['rows'] ) && is_array( $data['rows'] );
			$has_content     = $has_table_rows || ! empty( $resolved['legacyHtml'] );

		$config = array(
			'enabled'          => true,
			'displayMode'      => self::get_display_mode(),
			'hasContent'       => $has_content,
			'showUnitSwitcher' => ! empty( $resolved['showUnitSwitcher'] ) && ! empty( $resolved['data'] ),
			'defaultUnit'      => $resolved['defaultUnit'] ?? 'cm',
			'bindAttribute'    => self::resolve_bind_attribute( (int) $product_id, $resolved ),
			'inlineEnabled'    => self::inline_enabled(),
			'table'            => $resolved['data'] ?? null,
			'rowsBySizeKey'    => $resolved['rowsBySizeKey'] ?? array(),
			'legacyHtml'       => ! empty( $resolved['legacyHtml'] ) ? wp_kses_post( (string) $resolved['legacyHtml'] ) : null,
			'i18n'             => array(
				'title'       => __( 'Size Guide', 'tailwindscore' ),
				'cm'          => __( 'cm', 'tailwindscore' ),
				'inch'        => __( 'inch', 'tailwindscore' ),
				'close'       => __( 'Close', 'tailwindscore' ),
				'viewGuide'   => __( 'Size Guide', 'tailwindscore' ),
				'noRow'       => __( 'Select a size to see measurements.', 'tailwindscore' ),
			),
		);

		return $config;
	}
}

/**
 * @param int|null $product_id 商品 ID。
 * @return array<string, mixed>|null
 */
function tailwindscore_size_guide_config( ?int $product_id = null ): ?array {
	return Tailwindscore_Size_Guide_Settings::get_frontend_config( $product_id );
}

/**
 * 输出 QV / cart-edit 用的 sizeGuide JSON（供选码 inline；无 table 或未开 inline 则跳过）。
 *
 * @param int $product_id 商品 ID。
 */
function tailwindscore_print_size_guide_config_script( int $product_id ): void {
	$product_id = absint( $product_id );
	if ( $product_id < 1 ) {
		return;
	}

	$cfg = tailwindscore_size_guide_config( $product_id );
	if ( null === $cfg || empty( $cfg['inlineEnabled'] ) || empty( $cfg['table'] ) ) {
		return;
	}

	$json = wp_json_encode( $cfg );
	if ( ! is_string( $json ) || '' === $json ) {
		return;
	}

	printf(
		'<script type="application/json" data-ts-size-guide-config>%s</script>',
		$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON payload for TS.
	);
}
