<?php
declare(strict_types=1);

/**
 * Single product tabs (Additional information visibility).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Additional information product tab should render.
 */
function tailwindscore_product_show_additional_information_tab(): bool {
	return (bool) tailwindscore_mod( 'product_show_additional_information', false );
}

/**
 * @param array<string, array<string, mixed>> $tabs Product tabs.
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
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_product_tabs_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_tabs',
		array(
			'title'       => esc_html__( 'Product tabs', 'tailwindscore' ),
			'description' => esc_html__( 'Control which tabs appear below the product summary.', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 66,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_show_additional_information',
			'label'       => esc_html__( 'Show Additional information tab', 'tailwindscore' ),
			'description' => esc_html__( 'Displays product attributes in a table. Hidden by default because most stores rarely use it.', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_tabs',
			'default'     => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_product_tabs_kirki_register', 10, 2 );

/**
 * Remove default WooCommerce tabs (custom detail layout below).
 */
function tailwindscore_remove_default_product_tabs(): void {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
}
add_action( 'wp', 'tailwindscore_remove_default_product_tabs', 99 );

/**
 * Render custom product detail sections.
 * PC: Two columns (description + size guide), reviews below.
 * Mobile: Accordion style for description, size guide, and reviews.
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
	<div class="ts-product-detail-sections">
		<!-- Desktop Layout: First Row - Two Columns -->
		<div class="ts-product-detail-row ts-product-detail-row--desktop">
			<?php if ( $has_description ) : ?>
				<div class="ts-product-detail-column ts-product-detail-column--description">
					<h2 class="ts-product-detail-heading"><?php esc_html_e( 'Description', 'tailwindscore' ); ?></h2>
					<div class="ts-product-detail-content">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_size_guide ) : ?>
				<div class="ts-product-detail-column ts-product-detail-column--size-guide">
					<h2 class="ts-product-detail-heading"><?php esc_html_e( 'Size Guide', 'tailwindscore' ); ?></h2>
					<div class="ts-product-detail-content">
						<div class="ts-product-detail-size-guide-embed" data-ts-product-size-guide-embed></div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Desktop Layout: Second Row - Reviews -->
		<?php if ( $has_reviews ) : ?>
			<div class="ts-product-detail-row ts-product-detail-row--desktop ts-product-detail-row--full">
				<div class="ts-product-detail-column ts-product-detail-column--reviews">
					<?php comments_template(); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Mobile Layout: Accordion -->
		<div class="ts-product-detail-accordion">
			<?php if ( $has_description ) : ?>
				<div class="ts-product-detail-accordion-item">
					<button
						type="button"
						class="ts-product-detail-accordion-trigger"
						aria-expanded="true"
						data-ts-accordion-trigger
					>
						<span class="ts-product-detail-accordion-title"><?php esc_html_e( 'Description', 'tailwindscore' ); ?></span>
						<span class="ts-product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="ts-product-detail-accordion-content" data-ts-accordion-content>
						<div class="ts-product-detail-accordion-body">
							<?php echo wp_kses_post( $description ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_size_guide ) : ?>
				<div class="ts-product-detail-accordion-item">
					<button
						type="button"
						class="ts-product-detail-accordion-trigger"
						aria-expanded="false"
						data-ts-accordion-trigger
					>
						<span class="ts-product-detail-accordion-title"><?php esc_html_e( 'Size Guide', 'tailwindscore' ); ?></span>
						<span class="ts-product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="ts-product-detail-accordion-content" data-ts-accordion-content hidden>
						<div class="ts-product-detail-accordion-body">
							<div class="ts-product-detail-size-guide-embed" data-ts-product-size-guide-embed></div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $has_reviews ) : ?>
				<div class="ts-product-detail-accordion-item">
					<button
						type="button"
						class="ts-product-detail-accordion-trigger"
						aria-expanded="false"
						data-ts-accordion-trigger
					>
						<span class="ts-product-detail-accordion-title"><?php esc_html_e( 'Reviews', 'tailwindscore' ); ?></span>
						<span class="ts-product-detail-accordion-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</button>
					<div class="ts-product-detail-accordion-content" data-ts-accordion-content hidden>
						<div class="ts-product-detail-accordion-body">
							<?php comments_template(); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_after_single_product_summary', 'tailwindscore_render_product_detail_sections', 10 );
