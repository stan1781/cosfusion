<?php
declare(strict_types=1);

/**
 * Kirki：联络方式（WhatsApp；挂在「设计」panel 下）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_kirki_register_contact( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$panel   = $targets['design'] ?? 'tailwindscore_design';
	$section = $targets['contact'] ?? 'tailwindscore_contact_whatsapp';

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( '联络方式', 'tailwindscore' ),
			'description' => esc_html__( '全站 WhatsApp 等联络设定。用于订单成功页等；页头 WhatsApp 入口本轮未启用。', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'text',
			'settings'    => 'contact_whatsapp_number',
			'label'       => esc_html__( 'WhatsApp 号码', 'tailwindscore' ),
			'description' => esc_html__( 'E.164 数字，不含 + 或空格，例如 15551234567。全站 WhatsApp 链接共用。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'text',
			'settings'    => 'contact_whatsapp_display',
			'label'       => esc_html__( '展示用号码（选填）', 'tailwindscore' ),
			'description' => esc_html__( '例如 +1 555 123 4567；留空则由上方号码自动加上 + 前缀。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'contact_whatsapp_message_default',
			'label'       => esc_html__( '通用预填消息', 'tailwindscore' ),
			'description' => esc_html__( '一般 WhatsApp 链接使用（前台默认英文）。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => __( 'Hi, I have a question about my order.', 'tailwindscore' ),
			'priority'    => 30,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'contact_whatsapp_message_order',
			'label'       => esc_html__( '订单场景预填消息', 'tailwindscore' ),
			'description' => esc_html__( '订单成功页等场景使用；支持 {order_number}、{order_total}。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => __( 'Hi, I just placed order #{order_number} and would like to confirm the details.', 'tailwindscore' ),
			'priority'    => 40,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_kirki_register_contact', 10, 2 );
