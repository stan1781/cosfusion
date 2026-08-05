<?php
declare(strict_types=1);

/**
 * 单品 Tab（Additional information 可见性）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 是否渲染 Additional information 单品 Tab。
 */
function tailwindscore_product_show_additional_information_tab(): bool {
	return (bool) tailwindscore_mod( 'product_show_additional_information', false );
}

/**
 * @param array<string, array<string, mixed>> $tabs 单品 Tab 列表。
 * @return array<string, array<string, mixed>>
 */
function tailwindscore_filter_product_tabs( array $tabs ): array {
	if ( tailwindscore_product_show_additional_information_tab() ) {
		return $tabs;
	}

	unset( $tabs['additional_information'] );

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'tailwindscore_filter_product_tabs', 98 );

/**
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_product_tabs_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_tabs',
		array(
			'title'       => esc_html__( '产品选项卡', 'tailwindscore' ),
			'description' => esc_html__( '控制商品摘要下方显示哪些选项卡。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 66,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_show_additional_information',
			'label'       => esc_html__( '显示「附加信息」选项卡', 'tailwindscore' ),
			'description' => esc_html__( '以表格展示商品属性。默认隐藏，多数店铺较少使用。', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_tabs',
			'default'     => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_product_tabs_kirki_register', 10, 2 );

/**
 * 移除 WooCommerce 默认 Tab（下方使用自定义详情布局）。
 */
function tailwindscore_remove_default_product_tabs(): void {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
}
add_action( 'wp', 'tailwindscore_remove_default_product_tabs', 99 );

/**
 * 渲染自定义单品详情区块。
 * PC：双栏（描述 + 尺码指南），评价在下方。
 * 移动端：描述、尺码指南、评价采用手风琴样式。
 */
function tailwindscore_render_product_detail_sections(): void {
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$size_guide_config = function_exists( 'tailwindscore_size_guide_config' )
		? tailwindscore_size_guide_config( $product->get_id() )
		: null;
	$has_size_guide = $size_guide_config && ! empty( $size_guide_config['hasContent'] );

	$description     = apply_filters( 'the_content', $product->get_description() );
	$has_description = ! empty( trim( strip_tags( $description ) ) );

	$has_reviews = comments_open( $product->get_id() );

	?>
	<div class="product-detail-sections">
		<!-- 桌面布局：第一行 - 双栏 -->
		<div class="product-detail-row product-detail-row--desktop">
			<?php if ( $has_description ) : ?>
				<div class="product-detail-column product-detail-column--description">
					<h2 class="product-detail-heading"><?php esc_html_e( 'Description', 'tailwindscore' ); ?></h2>
					<div class="product-detail-content">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_size_guide ) : ?>
				<div class="product-detail-column product-detail-column--size-guide">
					<h2 class="product-detail-heading"><?php esc_html_e( 'Size Guide', 'tailwindscore' ); ?></h2>
					<div class="product-detail-content">
						<div class="product-detail-size-guide-embed" data-ts-product-size-guide-embed></div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- 桌面布局：第二行 - 评价 -->
		<?php if ( $has_reviews ) : ?>
			<div class="product-detail-row product-detail-row--desktop product-detail-row--full">
				<div class="product-detail-column product-detail-column--reviews">
					<?php comments_template(); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- 移动端布局：手风琴 -->
		<div class="product-detail-accordion">
			<?php if ( $has_description ) : ?>
				<div class="product-detail-accordion-item">
					<button
						type="button"
						class="product-detail-accordion-trigger"
						aria-expanded="true"
						data-ts-accordion-trigger
					>
						<span class="product-detail-accordion-title"><?php esc_html_e( 'Description', 'tailwindscore' ); ?></span>
						<span class="product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="product-detail-accordion-content" data-ts-accordion-content>
						<div class="product-detail-accordion-body">
							<?php echo wp_kses_post( $description ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_size_guide ) : ?>
				<div class="product-detail-accordion-item">
					<button
						type="button"
						class="product-detail-accordion-trigger"
						aria-expanded="false"
						data-ts-accordion-trigger
					>
						<span class="product-detail-accordion-title"><?php esc_html_e( 'Size Guide', 'tailwindscore' ); ?></span>
						<span class="product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="product-detail-accordion-content" data-ts-accordion-content hidden>
						<div class="product-detail-accordion-body">
							<div class="product-detail-size-guide-embed" data-ts-product-size-guide-embed></div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_reviews ) : ?>
				<div class="product-detail-accordion-item">
					<button
						type="button"
						class="product-detail-accordion-trigger"
						aria-expanded="false"
						data-ts-accordion-trigger
					>
						<span class="product-detail-accordion-title"><?php esc_html_e( 'Reviews', 'tailwindscore' ); ?></span>
						<span class="product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="product-detail-accordion-content" data-ts-accordion-content hidden>
						<div class="product-detail-accordion-body">
							<?php comments_template(); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/*
		 * 评价表单弹窗只渲染一次，挂在桌面行 / 手风琴之外。
		 * comments_template() 会分别嵌进桌面（小屏 display:none）与手风琴（overflow:hidden），
		 * 若弹窗跟在评论模板里，手机端 querySelector 会打开不可见副本。
		 */
		if ( $has_reviews ) {
			get_template_part( 'template-parts/woocommerce/review-form-modal' );
		}
		?>
	</div>
	<?php
}
add_action( 'woocommerce_after_single_product_summary', 'tailwindscore_render_product_detail_sections', 10 );
