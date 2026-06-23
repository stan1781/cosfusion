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
				<?php
				$logo_pc_dims = tailwindscore_image_url_dimensions( $logo_pc, 200, 60 );
				?>
				<img
					class="ts-branding__logo ts-branding__logo--pc"
					src="<?php echo esc_url( $logo_pc ); ?>"
					alt="<?php echo esc_attr( $site_name ); ?>"
					width="<?php echo esc_attr( (string) $logo_pc_dims['width'] ); ?>"
					height="<?php echo esc_attr( (string) $logo_pc_dims['height'] ); ?>"
				>
			<?php endif; ?>

			<?php if ( $logo_mobile ) : ?>
				<?php
				$logo_mobile_dims = tailwindscore_image_url_dimensions( $logo_mobile, 120, 40 );
				?>
				<img
					class="ts-branding__logo ts-branding__logo--mobile"
					src="<?php echo esc_url( $logo_mobile ); ?>"
					alt="<?php echo esc_attr( $site_name ); ?>"
					width="<?php echo esc_attr( (string) $logo_mobile_dims['width'] ); ?>"
					height="<?php echo esc_attr( (string) $logo_mobile_dims['height'] ); ?>"
				>
			<?php endif; ?>

		<?php else : ?>

			<?php echo esc_html( $site_name ); ?>

		<?php endif; ?>

	</a>
</div>
