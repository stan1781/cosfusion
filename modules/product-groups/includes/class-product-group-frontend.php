<?php
declare(strict_types=1);

/**
 * 产品组前台渲染与 variations 联动。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * PDP 缩略图条 + 运行时 filter。
 */
final class Tailwindscore_Product_Group_Frontend {

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'woocommerce_before_variations_form', array( self::class, 'render' ), 5 );
		add_action( 'woocommerce_before_add_to_cart_button', array( self::class, 'render_for_simple' ), 5 );
		add_filter( 'tailwindscore/variations/is_color_attribute', array( self::class, 'filter_is_color_attribute' ), 10, 3 );
		add_filter( 'tailwindscore/variations/hide_attribute_row', array( self::class, 'filter_hide_attribute_row' ), 10, 3 );
	}

	/**
	 * Variable 表单内输出；Simple 走另一钩子避免重复。
	 *
	 * @return void
	 */
	public static function render(): void {
		self::output( true );
	}

	/**
	 * @return void
	 */
	public static function render_for_simple(): void {
		global $product;
		if ( ! $product instanceof WC_Product || $product->is_type( 'variable' ) ) {
			return;
		}
		self::output( false );
	}

	/**
	 * @param bool $from_variations_form 是否来自变体表单钩子。
	 * @return void
	 */
	private static function output( bool $from_variations_form ): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// Variable 只在 variations_form 钩子输出，避免 before_add_to_cart 再出一次。
		if ( $product->is_type( 'variable' ) && ! $from_variations_form ) {
			return;
		}

		$current_id = (int) $product->get_id();
		$group      = Tailwindscore_Product_Group_Repository::get_group_for_product( $current_id );
		if ( ! $group instanceof WP_Post ) {
			return;
		}

		$members = self::get_visible_members( Tailwindscore_Product_Group_Repository::get_product_ids( (int) $group->ID ) );
		if ( count( $members ) < 2 ) {
			return;
		}

		$primary_attr = Tailwindscore_Product_Group_Repository::get_primary_attribute( (int) $group->ID );
		$title        = '' !== $primary_attr
			? wc_attribute_label( $primary_attr )
			: trim( (string) $group->post_title );
		if ( '' === $title ) {
			$title = __( 'Styles', 'tailwindscore' );
		}

		$shape = 'square';
		if ( class_exists( 'Tailwindscore_Variation_Settings' ) ) {
			$shape = Tailwindscore_Variation_Settings::get_color_selector_shape();
		}

		$current_label = self::resolve_current_value_label( $product, $primary_attr );
		$index         = 0;
		?>
		<div class="variation-row product-group-row" data-ts-product-group>
			<div class="label variation-row__label">
				<span class="product-group-row__title"><?php echo esc_html( $title ); ?>:</span>
				<?php if ( '' !== $current_label ) : ?>
					<span class="selected-attribute-value" data-ts-product-group-current><?php echo esc_html( $current_label ); ?></span>
				<?php endif; ?>
			</div>
			<div class="value variation-row__value">
				<ul class="custom-variation-selector color-selector mode-grid shape-<?php echo esc_attr( $shape ); ?>">
					<?php foreach ( $members as $member ) : ?>
						<?php
						$mid       = (int) $member->get_id();
						$is_current = $mid === $current_id;
						$name      = $member->get_name();
						$url       = get_permalink( $mid );
						$thumb_id  = (int) $member->get_image_id();
						$lazy      = $index >= 2;
						++$index;
						?>
						<?php if ( $is_current ) : ?>
							<li class="selected" aria-current="page" title="<?php echo esc_attr( $name ); ?>">
								<span class="variation-media">
									<?php echo self::thumb_html( $thumb_id, $name, $lazy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="variation-content"><span class="variation-name"><?php echo esc_html( $name ); ?></span></span>
							</li>
						<?php else : ?>
							<li title="<?php echo esc_attr( $name ); ?>">
								<a class="product-group-row__link" href="<?php echo esc_url( (string) $url ); ?>" data-ts-product-group-prefetch>
									<span class="variation-media">
										<?php echo self::thumb_html( $thumb_id, $name, $lazy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
									<span class="variation-content"><span class="variation-name"><?php echo esc_html( $name ); ?></span></span>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * 标题旁当前值：优先组属性在本商品上的 term 名，否则留空（避免整段商品名占位）。
	 *
	 * @param WC_Product $product      当前商品。
	 * @param string     $primary_attr 组首属性键。
	 */
	private static function resolve_current_value_label( WC_Product $product, string $primary_attr ): string {
		if ( '' === $primary_attr ) {
			return '';
		}

		$taxonomy = $primary_attr;
		if ( ! taxonomy_exists( $taxonomy ) && taxonomy_exists( 'pa_' . $taxonomy ) ) {
			$taxonomy = 'pa_' . $taxonomy;
		}

		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( is_array( $terms ) && isset( $terms[0] ) && $terms[0] instanceof WP_Term ) {
				return $terms[0]->name;
			}
		}

		return '';
	}

	/**
	 * @param int[] $ids 组内 ID。
	 * @return WC_Product[]
	 */
	private static function get_visible_members( array $ids ): array {
		$out = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p instanceof WC_Product || 'publish' !== $p->get_status() ) {
				continue;
			}
			if ( ! $p->is_visible() ) {
				continue;
			}
			$out[] = $p;
		}
		return $out;
	}

	/**
	 * @param int    $attachment_id 附件。
	 * @param string $alt           alt。
	 * @param bool   $lazy          lazy load。
	 */
	private static function thumb_html( int $attachment_id, string $alt, bool $lazy ): string {
		if ( $attachment_id < 1 ) {
			$placeholder = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ) : '';
			if ( '' === $placeholder ) {
				return '<span></span>';
			}
			return sprintf(
				'<img src="%s" alt="%s"%s />',
				esc_url( $placeholder ),
				esc_attr( $alt ),
				$lazy ? ' loading="lazy"' : ''
			);
		}

		$html = wp_get_attachment_image(
			$attachment_id,
			'woocommerce_gallery_thumbnail',
			false,
			array(
				'alt'     => $alt,
				'loading' => $lazy ? 'lazy' : 'eager',
			)
		);

		return is_string( $html ) ? $html : '';
	}

	/**
	 * @param bool     $is_color       原结果。
	 * @param string   $attribute_name 属性。
	 * @param int|null $product_id     商品。
	 */
	public static function filter_is_color_attribute( bool $is_color, string $attribute_name, ?int $product_id ): bool {
		if ( ! $is_color || ! $product_id ) {
			return $is_color;
		}

		$action = Tailwindscore_Product_Group_Repository::get_attr_action_for_product( $product_id, $attribute_name );
		if ( Tailwindscore_Product_Group_Repository::ACTION_DISABLE_IMAGE === $action
			|| Tailwindscore_Product_Group_Repository::ACTION_HIDE_ROW === $action ) {
			return false;
		}

		return $is_color;
	}

	/**
	 * @param bool   $hide           是否隐藏。
	 * @param string $attribute_name 属性。
	 * @param int    $product_id     商品。
	 */
	public static function filter_hide_attribute_row( bool $hide, string $attribute_name, int $product_id ): bool {
		if ( $hide || $product_id < 1 ) {
			return $hide;
		}

		$action = Tailwindscore_Product_Group_Repository::get_attr_action_for_product( $product_id, $attribute_name );
		return Tailwindscore_Product_Group_Repository::ACTION_HIDE_ROW === $action;
	}
}
