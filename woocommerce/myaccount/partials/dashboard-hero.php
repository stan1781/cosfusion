<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$hero_bg      = class_exists( 'Tailwindscore_Account_Auth_Settings' ) ? Tailwindscore_Account_Auth_Settings::get_hero_bg_color() : '';
$hero_text    = class_exists( 'Tailwindscore_Account_Auth_Settings' ) ? Tailwindscore_Account_Auth_Settings::get_hero_text() : __( 'Track your orders, manage addresses, and checkout faster.', 'tailwindscore' );
$hero_image   = class_exists( 'Tailwindscore_Account_Auth_Settings' ) ? Tailwindscore_Account_Auth_Settings::get_hero_image_url() : '';
$style        = '' !== $hero_bg ? '--ts-wc-account-hero-bg: ' . esc_attr( $hero_bg ) . ';' : '';
$display_name = wp_get_current_user()->display_name;
?>
<header class="ts-wc-account-hero"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
	<div class="ts-wc-account-hero__inner">
		<p class="ts-wc-account-hero__eyebrow"><?php esc_html_e( 'Welcome back', 'tailwindscore' ); ?></p>
		<h1 class="ts-wc-account-hero__title">
			<?php
			printf(
				/* translators: %s: customer display name */
				esc_html__( 'Hello, %s', 'tailwindscore' ),
				esc_html( $display_name )
			);
			?>
			<span class="ts-wc-account-hero__wave" aria-hidden="true">👋</span>
		</h1>
		<p class="ts-wc-account-hero__text"><?php echo esc_html( $hero_text ); ?></p>
	</div>
	<?php if ( '' !== $hero_image ) : ?>
		<div class="ts-wc-account-hero__media">
			<img class="ts-wc-account-hero__image" src="<?php echo esc_url( $hero_image ); ?>" alt="" loading="lazy" decoding="async" />
		</div>
	<?php endif; ?>
</header>
