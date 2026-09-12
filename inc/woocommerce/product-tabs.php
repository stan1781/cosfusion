<?php
declare(strict_types=1);

/**
 * 单品详情区块（桌面 Tab / 移动手风琴）与 Additional information 可见性。
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
 * 手风琴触发器右侧装饰箭头。
 */
function tailwindscore_product_detail_accordion_icon(): void {
	?>
	<span class="product-detail-accordion-icon" aria-hidden="true">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</span>
	<?php
}

/**
 * 渲染可折叠的商品描述（默认限高 + Show more / less）。
 *
 * @param string $description 已过 the_content 的 HTML。
 */
function tailwindscore_render_product_description_clamp( string $description ): void {
	$label_more = __( 'Show more', 'tailwindscore' );
	$label_less = __( 'Show less', 'tailwindscore' );
	?>
	<div class="product-detail-description" data-ts-product-description>
		<div class="product-detail-description__clip" data-ts-product-description-clip>
			<div class="product-detail-description__body" data-ts-product-description-body>
				<?php echo wp_kses_post( $description ); ?>
			</div>
			<span class="product-detail-description__fade" aria-hidden="true"></span>
		</div>
		<button
			type="button"
			class="product-detail-description__toggle"
			hidden
			aria-expanded="false"
			data-ts-product-description-toggle
			data-label-more="<?php echo esc_attr( $label_more ); ?>"
			data-label-less="<?php echo esc_attr( $label_less ); ?>"
		>
			<?php echo esc_html( $label_more ); ?>
		</button>
	</div>
	<?php
}

/**
 * 组装单品详情 Tab（桌面 Tab + 移动端手风琴共享）。
 *
 * @param WC_Product $product 当前商品。
 * @return array<string, array{title: string, priority: int, callback: callable}>
 */
function tailwindscore_get_product_detail_tabs( WC_Product $product ): array {
	$size_guide_config = function_exists( 'tailwindscore_size_guide_config' )
		? tailwindscore_size_guide_config( $product->get_id() )
		: null;
	$has_size_guide = $size_guide_config && ! empty( $size_guide_config['hasContent'] );

	$description     = apply_filters( 'the_content', $product->get_description() );
	$has_description = ! empty( trim( wp_strip_all_tags( $description ) ) );

	$tabs = array();

	if ( $has_description ) {
		$tabs['description'] = array(
			'title'    => __( 'Description', 'tailwindscore' ),
			'priority' => 10,
			'callback' => static function () use ( $description ): void {
				tailwindscore_render_product_description_clamp( $description );
			},
		);
	}

	if ( $has_size_guide ) {
		$tabs['size_guide'] = array(
			'title'    => __( 'Size Guide', 'tailwindscore' ),
			'priority' => 20,
			'callback' => static function (): void {
				echo '<div class="product-detail-size-guide-embed" data-ts-product-size-guide-embed></div>';
			},
		);
	}

	/**
	 * 过滤单品详情 Tab（不含评论；评论桌面独立行 / 移动端手风琴单独追加）。
	 *
	 * @param array<string, array{title: string, priority: int, callback: callable}> $tabs    Tab 列表。
	 * @param WC_Product                                                              $product 当前商品。
	 */
	$tabs = apply_filters( 'tailwindscore_product_detail_tabs', $tabs, $product );

	if ( ! is_array( $tabs ) ) {
		$tabs = array();
	}

	uasort(
		$tabs,
		static function ( $a, $b ): int {
			$pa = is_array( $a ) && isset( $a['priority'] ) ? (int) $a['priority'] : 10;
			$pb = is_array( $b ) && isset( $b['priority'] ) ? (int) $b['priority'] : 10;
			return $pa <=> $pb;
		}
	);

	return $tabs;
}

/**
 * 渲染自定义单品详情区块。
 * PC：Description / Size Guide（及 filter 扩展）为下划线 Tab；评价在下方。
 * 移动端：同一 Tab 列表 + 评价采用手风琴。
 */
function tailwindscore_render_product_detail_sections(): void {
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$tabs        = tailwindscore_get_product_detail_tabs( $product );
	$has_reviews = comments_open( $product->get_id() );
	$has_tabs    = ! empty( $tabs );

	?>
	<div class="product-detail-sections">
		<?php if ( $has_tabs ) : ?>
			<!-- 桌面布局：Tab + panels -->
			<div class="product-detail-row product-detail-row--desktop">
				<div class="product-detail-column product-detail-column--tabs">
					<div class="product-detail-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Product details', 'tailwindscore' ); ?>">
						<span class="product-detail-tabs__indicator" aria-hidden="true"></span>
						<?php
						$is_first = true;
						foreach ( $tabs as $tab_key => $tab ) :
							if ( ! is_array( $tab ) || empty( $tab['title'] ) || ! is_callable( $tab['callback'] ?? null ) ) {
								continue;
							}
							$tab_id   = 'product-detail-tab-' . sanitize_key( (string) $tab_key );
							$panel_id = 'product-detail-panel-' . sanitize_key( (string) $tab_key );
							?>
							<button
								type="button"
								class="product-detail-tabs__tab"
								role="tab"
								id="<?php echo esc_attr( $tab_id ); ?>"
								aria-controls="<?php echo esc_attr( $panel_id ); ?>"
								aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
								tabindex="<?php echo $is_first ? '0' : '-1'; ?>"
								data-ts-product-detail-tab
							>
								<?php echo esc_html( (string) $tab['title'] ); ?>
							</button>
							<?php
							$is_first = false;
						endforeach;
						?>
					</div>
					<div class="product-detail-panels">
						<?php
						$is_first = true;
						foreach ( $tabs as $tab_key => $tab ) :
							if ( ! is_array( $tab ) || empty( $tab['title'] ) || ! is_callable( $tab['callback'] ?? null ) ) {
								continue;
							}
							$tab_id   = 'product-detail-tab-' . sanitize_key( (string) $tab_key );
							$panel_id = 'product-detail-panel-' . sanitize_key( (string) $tab_key );
							?>
							<div
								class="product-detail-panel product-detail-content"
								role="tabpanel"
								id="<?php echo esc_attr( $panel_id ); ?>"
								aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
								<?php echo $is_first ? '' : 'hidden'; ?>
								data-ts-product-detail-panel
							>
								<?php call_user_func( $tab['callback'] ); ?>
							</div>
							<?php
							$is_first = false;
						endforeach;
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- 桌面布局：评价全宽 -->
		<?php if ( $has_reviews ) : ?>
			<div class="product-detail-row product-detail-row--desktop product-detail-row--full">
				<div class="product-detail-column product-detail-column--reviews">
					<?php comments_template(); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- 移动端布局：手风琴 -->
		<div class="product-detail-accordion">
			<?php
			$accordion_first = true;
			foreach ( $tabs as $tab_key => $tab ) :
				if ( ! is_array( $tab ) || empty( $tab['title'] ) || ! is_callable( $tab['callback'] ?? null ) ) {
					continue;
				}
				$expanded = $accordion_first;
				?>
				<div class="product-detail-accordion-item">
					<button
						type="button"
						class="product-detail-accordion-trigger"
						aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
						data-ts-accordion-trigger
					>
						<span class="product-detail-accordion-title"><?php echo esc_html( (string) $tab['title'] ); ?></span>
						<?php tailwindscore_product_detail_accordion_icon(); ?>
					</button>
					<div class="product-detail-accordion-content" data-ts-accordion-content<?php echo $expanded ? '' : ' hidden'; ?>>
						<div class="product-detail-accordion-body">
							<?php call_user_func( $tab['callback'] ); ?>
						</div>
					</div>
				</div>
				<?php
				$accordion_first = false;
			endforeach;
			?>

			<?php if ( $has_reviews ) : ?>
				<div class="product-detail-accordion-item">
					<button
						type="button"
						class="product-detail-accordion-trigger"
						aria-expanded="false"
						data-ts-accordion-trigger
					>
						<span class="product-detail-accordion-title"><?php esc_html_e( 'Reviews', 'tailwindscore' ); ?></span>
						<?php tailwindscore_product_detail_accordion_icon(); ?>
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
