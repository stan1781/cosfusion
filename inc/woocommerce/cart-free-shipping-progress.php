<?php
declare(strict_types=1);

/**
 * 购物车 / Drawer 免运进度条。
 *
 * 门槛读取条件免运 Minimum order amount（见 shipping-rules.php）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 是否在 Drawer / 购物车页显示免运进度。
 */
function tailwindscore_cart_drawer_free_shipping_progress_is_enabled(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	$enabled = (bool) tailwindscore_mod( 'cart_drawer_free_shipping_progress_enable', true );

	/**
	 * 过滤是否显示免运进度条。
	 *
	 * @param bool $enabled 主题设置值。
	 */
	return (bool) apply_filters( 'tailwindscore_cart_drawer_show_free_shipping_progress', $enabled );
}

/**
 * @return array{progress: string, achieved: string}
 */
function tailwindscore_cart_free_shipping_progress_message_templates(): array {
	return array(
		'progress' => (string) tailwindscore_mod(
			'cart_drawer_free_shipping_message_progress',
			'Spend {threshold} for free shipping — only {remaining} to go!'
		),
		'achieved' => (string) tailwindscore_mod(
			'cart_drawer_free_shipping_message_achieved',
			"You've unlocked free shipping!"
		),
	);
}

/**
 * @param array<string, mixed> $package
 */
function tailwindscore_resolve_cart_free_shipping_progress_message( string $template, array $package ): string {
	$remaining    = tailwindscore_free_shipping_remaining_amount( $package );
	$replacements = array(
		'{threshold}' => tailwindscore_free_shipping_threshold_label( $package ),
		'{remaining}' => tailwindscore_format_price_plain( $remaining ),
	);

	$resolved = preg_replace( '/\s+/u', ' ', trim( strtr( $template, $replacements ) ) );

	return is_string( $resolved ) ? $resolved : trim( strtr( $template, $replacements ) );
}

/**
 * @return array{qualified: bool, percent: int, message: string}|null
 */
function tailwindscore_get_cart_free_shipping_progress(): ?array {
	if ( ! tailwindscore_cart_drawer_free_shipping_progress_is_enabled() ) {
		return null;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return null;
	}

	$package  = tailwindscore_get_cart_shipping_package();
	$instance = tailwindscore_get_free_shipping_threshold_instance( $package );

	if ( ! $instance || (float) $instance->min_amount <= 0 ) {
		return null;
	}

	$threshold = tailwindscore_free_shipping_threshold( $package );

	if ( $threshold <= 0 ) {
		return null;
	}

	$subtotal  = tailwindscore_cart_subtotal_for_free_shipping( $instance );
	$qualified = tailwindscore_cart_meets_free_shipping_threshold( $package );
	$percent   = $qualified ? 100 : min( 100, (int) round( ( $subtotal / $threshold ) * 100 ) );

	$templates = tailwindscore_cart_free_shipping_progress_message_templates();
	$template  = $qualified ? $templates['achieved'] : $templates['progress'];
	$message   = tailwindscore_resolve_cart_free_shipping_progress_message( $template, $package );

	$data = array(
		'qualified' => $qualified,
		'percent'   => $percent,
		'message'   => $message,
	);

	/**
	 * @param array{qualified: bool, percent: int, message: string} $data
	 * @param array<string, mixed>                                  $package
	 */
	$data = apply_filters( 'tailwindscore_cart_free_shipping_progress', $data, $package );

	if ( ! is_array( $data ) || ! isset( $data['qualified'], $data['percent'], $data['message'] ) ) {
		return null;
	}

	return array(
		'qualified' => (bool) $data['qualified'],
		'percent'   => (int) $data['percent'],
		'message'   => (string) $data['message'],
	);
}

function tailwindscore_render_cart_free_shipping_progress(): void {
	$progress = tailwindscore_get_cart_free_shipping_progress();

	if ( null === $progress ) {
		return;
	}

	tailwindscore_load_woocommerce_template_part(
		'cart-free-shipping-progress',
		array(
			'progress' => $progress,
		)
	);
}

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_cart_free_shipping_progress_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'cart_drawer_free_shipping_progress_enable',
			'label'       => esc_html__( '在迷你购物车中显示免运进度', 'tailwindscore' ),
			'description' => esc_html__( '同时作用于购物车抽屉与购物车页侧栏。门槛读取 WooCommerce 条件免运的 Minimum order amount。', 'tailwindscore' ),
			'section'     => 'tailwindscore_cart_settings',
			'default'     => true,
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'cart_drawer_free_shipping_message_progress',
			'label'           => esc_html__( '免运进度文案（未达标）', 'tailwindscore' ),
			'description'     => esc_html__( '可用占位符：{threshold}（门槛金额）、{remaining}（还差金额）。', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => 'Spend {threshold} for free shipping — only {remaining} to go!',
			'priority'        => 30,
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_drawer_free_shipping_progress_enable', true );
			},
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'cart_drawer_free_shipping_message_achieved',
			'label'           => esc_html__( '免运进度文案（已达标）', 'tailwindscore' ),
			'description'     => esc_html__( '可用占位符：{threshold}、{remaining}。', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => "You've unlocked free shipping!",
			'priority'        => 40,
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_drawer_free_shipping_progress_enable', true );
			},
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_cart_free_shipping_progress_kirki_register', 15, 2 );
