<?php
declare(strict_types=1);

/**
 * 尺码指南前台 Hook 与 markup。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 注册尺码指南 UI Hook。
 */
final class Tailwindscore_Size_Guide_Frontend {

	public function __construct() {
		add_action( 'tailwindscore_after_size_attribute_label', array( $this, 'render_trigger_button' ), 10, 2 );
		add_action( 'tailwindscore_after_size_attribute_value', array( $this, 'render_inline_container' ), 10, 2 );
		add_filter( 'tailwindscore_product_page_config', array( $this, 'merge_product_config' ), 20, 2 );
		add_action( 'wp_footer', array( $this, 'render_dialog_shell' ), 5 );
	}

	/**
	 * @param array<string, mixed> $config   商品配置。
	 * @param int                  $product_id 商品 ID。
	 * @return array<string, mixed>
	 */
	public function merge_product_config( array $config, int $product_id ): array {
		$sg = tailwindscore_size_guide_config( $product_id );
		if ( $sg !== null ) {
			$config['sizeGuide'] = $sg;
		}
		return $config;
	}

	/**
	 * @param string     $attribute_name 属性键。
	 * @param WC_Product $product        商品。
	 */
	public function render_trigger_button( string $attribute_name, WC_Product $product ): void {
		if ( ! Tailwindscore_Size_Guide_Settings::is_enabled() || ! class_exists( 'Product_Size_Guide' ) ) {
			return;
		}

		$resolved = Product_Size_Guide::resolve( $product->get_id() );
		$mode     = Tailwindscore_Size_Guide_Settings::get_display_mode();
		$has      = $resolved['source'] !== 'empty';

		if ( ! $has && $mode !== 'always' ) {
			return;
		}

		printf(
			'<button type="button" class="ts-size-guide-trigger" data-ts-size-guide-open aria-haspopup="dialog">%s</button>',
			esc_html__( 'Size Guide', 'tailwindscore' )
		);
	}

	/**
	 * @param string     $attribute_name 属性键。
	 * @param WC_Product $product        商品。
	 */
	public function render_inline_container( string $attribute_name, WC_Product $product ): void {
		if ( ! Tailwindscore_Size_Guide_Settings::is_enabled() || ! Tailwindscore_Size_Guide_Settings::inline_enabled() ) {
			return;
		}
		if ( ! function_exists( 'tailwindscore_is_size_variation_attribute' ) || ! tailwindscore_is_size_variation_attribute( $attribute_name ) ) {
			return;
		}
		echo '<div class="ts-size-guide-inline" data-ts-size-guide-inline hidden></div>';
	}

	/**
	 * 对话框壳（内容由 TS 填充）。
	 */
	public function render_dialog_shell(): void {
		if ( ! is_product() || ! Tailwindscore_Size_Guide_Settings::is_enabled() ) {
			return;
		}
		?>
		<dialog id="ts-size-guide-dialog" class="ts-size-guide-dialog">
			<div class="ts-size-guide-dialog__inner">
				<div class="ts-size-guide-dialog__header">
					<h2 class="ts-size-guide-dialog__title" data-ts-size-guide-title><?php esc_html_e( 'Size Guide', 'tailwindscore' ); ?></h2>
					<div class="ts-size-guide-dialog__controls">
						<div class="ts-size-guide-unit-toggle is-hidden" data-ts-size-guide-unit-toggle>
							<button type="button" class="ts-size-guide-unit-btn" data-unit="cm">cm</button>
							<button type="button" class="ts-size-guide-unit-btn" data-unit="inch">inch</button>
						</div>
						<button type="button" class="ts-size-guide-close" data-ts-size-guide-close aria-label="<?php esc_attr_e( 'Close', 'tailwindscore' ); ?>">&times;</button>
					</div>
				</div>
				<div class="ts-size-guide-dialog__body" data-ts-size-guide-body></div>
			</div>
		</dialog>
		<?php
	}
}
