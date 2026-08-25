<?php
declare(strict_types=1);

/**
 * Customizer：带 optgroup 的字体选择控件。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 原生 select + optgroup（避免 Kirki ReactSelect 无分组）。
 */
class Tailwindscore_Typography_Font_Control extends WP_Customize_Control {

	/**
	 * @var string
	 */
	public $type = 'tailwindscore_font';

	/**
	 * @return void
	 */
	public function render_content(): void {
		if ( empty( $this->choices ) || ! is_array( $this->choices ) ) {
			return;
		}

		$input_id = '_customize-input-' . $this->id;
		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<label for="<?php echo esc_attr( $input_id ); ?>">
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			</label>
		<?php endif; ?>
		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<select
			id="<?php echo esc_attr( $input_id ); ?>"
			<?php $this->link(); ?>
		>
			<?php foreach ( $this->choices as $group_label => $options ) : ?>
				<?php if ( ! is_array( $options ) ) : ?>
					<option value="<?php echo esc_attr( (string) $group_label ); ?>" <?php selected( $this->value(), (string) $group_label ); ?>>
						<?php echo esc_html( (string) $options ); ?>
					</option>
					<?php continue; ?>
				<?php endif; ?>
				<optgroup label="<?php echo esc_attr( (string) $group_label ); ?>">
					<?php foreach ( $options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $this->value(), (string) $value ); ?>>
							<?php echo esc_html( (string) $label ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php
	}
}
