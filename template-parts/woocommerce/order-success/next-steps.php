<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$steps = tailwindscore_get_thankyou_next_steps();

if ( empty( $steps ) ) {
	return;
}
?>
<section class="wc-thankyou__next-steps" aria-label="<?php esc_attr_e( 'Next steps', 'tailwindscore' ); ?>">
	<header class="wc-thankyou__next-steps-header">
		<span class="wc-thankyou__next-steps-header-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'clipboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<h2 class="wc-thankyou__next-steps-title"><?php esc_html_e( 'Next steps', 'tailwindscore' ); ?></h2>
	</header>
	<ul class="wc-thankyou__next-steps-list">
		<?php foreach ( $steps as $step ) : ?>
			<li class="wc-thankyou__next-step">
				<span class="wc-thankyou__next-step-icon" aria-hidden="true">
					<span class="icon"><?php echo tailwindscore_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</span>
				<span class="wc-thankyou__next-step-body">
					<?php
					echo wp_kses(
						(string) ( $step['body'] ?? '' ),
						array(
							'br'     => array(),
							'strong' => array(),
						)
					);
					?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
