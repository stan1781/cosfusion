<?php
declare(strict_types=1);

$logo_pc     = tailwindscore_mod( 'tailwindscore_logo_pc', '' );
$logo_mobile = tailwindscore_mod( 'tailwindscore_logo_mobile', '' );
$site_name   = tailwindscore_site_name();
$home_url    = esc_url( home_url( '/' ) );
?>
<div class="ts-branding">
	<a href="<?php echo $home_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

		<?php if ( $logo_pc || $logo_mobile ) : ?>

			<?php if ( $logo_pc ) : ?>
				<img
					class="ts-branding__logo ts-branding__logo--pc"
					src="<?php echo esc_url( $logo_pc ); ?>"
					alt="<?php echo esc_attr( $site_name ); ?>"
				>
			<?php endif; ?>

			<?php if ( $logo_mobile ) : ?>
				<img
					class="ts-branding__logo ts-branding__logo--mobile"
					src="<?php echo esc_url( $logo_mobile ); ?>"
					alt="<?php echo esc_attr( $site_name ); ?>"
				>
			<?php endif; ?>

		<?php else : ?>

			<?php echo esc_html( $site_name ); ?>

		<?php endif; ?>

	</a>
</div>
