<?php
declare(strict_types=1);

/**
 * Customizer：字体推荐组合（一点应用）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 在标题/正文字体下拉下方按品类展示可用预设按钮。
 */
class Tailwindscore_Typography_Presets_Control extends WP_Customize_Control {

	/**
	 * @var string
	 */
	public $type = 'tailwindscore_font_presets';

	/**
	 * @var list<array{id: string, group?: string, label: string, detail: string, heading: string, body: string}>
	 */
	public $presets = array();

	/**
	 * @return void
	 */
	public function render_content(): void {
		$presets = is_array( $this->presets ) ? $this->presets : array();
		if ( array() === $presets ) {
			return;
		}

		$grouped = array();
		foreach ( $presets as $preset ) {
			if ( ! is_array( $preset ) ) {
				continue;
			}
			$group = isset( $preset['group'] ) ? (string) $preset['group'] : '';
			if ( '' === $group ) {
				$group = __( '其它', 'tailwindscore' );
			}
			if ( ! isset( $grouped[ $group ] ) ) {
				$grouped[ $group ] = array();
			}
			$grouped[ $group ][] = $preset;
		}
		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<div class="ts-font-presets" style="margin-top:8px;">
			<?php foreach ( $grouped as $group_label => $items ) : ?>
				<p style="margin:12px 0 6px;font-weight:600;"><?php echo esc_html( (string) $group_label ); ?></p>
				<ul style="margin:0;padding:0;list-style:none;">
					<?php foreach ( $items as $preset ) : ?>
						<?php
						$id      = isset( $preset['id'] ) ? (string) $preset['id'] : '';
						$label   = isset( $preset['label'] ) ? (string) $preset['label'] : '';
						$detail  = isset( $preset['detail'] ) ? (string) $preset['detail'] : '';
						$heading = isset( $preset['heading'] ) ? (string) $preset['heading'] : '';
						$body    = isset( $preset['body'] ) ? (string) $preset['body'] : '';
						if ( '' === $id || '' === $heading || '' === $body ) {
							continue;
						}
						?>
						<li style="margin:0 0 8px;">
							<button
								type="button"
								class="button button-secondary"
								style="width:100%;text-align:left;height:auto;padding:6px 10px;white-space:normal;line-height:1.35;"
								data-ts-font-preset="<?php echo esc_attr( $id ); ?>"
								data-ts-heading="<?php echo esc_attr( $heading ); ?>"
								data-ts-body="<?php echo esc_attr( $body ); ?>"
							>
								<strong><?php echo esc_html( $label ); ?></strong>
								<?php if ( '' !== $detail ) : ?>
									<br /><span class="description" style="margin:0;"><?php echo esc_html( $detail ); ?></span>
								<?php endif; ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
