<?php
declare(strict_types=1);

/**
 * 认证外壳开头标记 — 分栏 hero + 表单面板。
 *
 * @package TailwindScore
 *
 * @var string $subtitle   面板副标题。
 * @var bool   $split_hero 是否显示左侧 hero 栏。
 * @var string $context    login|register|recovery
 */

defined( 'ABSPATH' ) || exit;

$subtitle   = isset( $subtitle ) ? (string) $subtitle : '';
$split_hero = ! empty( $split_hero );
$context    = isset( $context ) ? sanitize_key( (string) $context ) : 'login';

$hero_url    = Tailwindscore_Account_Auth_Settings::get_hero_image_url();
$hero_bg     = Tailwindscore_Account_Auth_Settings::get_hero_bg_color();
$hero_style  = '' !== $hero_bg ? '--ts-account-auth-hero-bg: ' . esc_attr( $hero_bg ) . ';' : '';

if ( 'register' === $context ) {
	$hero_heading = Tailwindscore_Account_Auth_Settings::get_register_hero_heading();
	$hero_text    = Tailwindscore_Account_Auth_Settings::get_register_hero_text();
} else {
	$hero_heading = Tailwindscore_Account_Auth_Settings::get_hero_heading();
	$hero_text    = Tailwindscore_Account_Auth_Settings::get_hero_text();
}

$header_class = $split_hero ? 'account-auth__header account-auth__header--split' : 'account-auth__header';
?>
<div class="account-auth">
	<div class="account-auth__viewport">
		<div class="account-auth__card">
			<div class="account-auth__layout<?php echo $split_hero ? ' account-auth__layout--split' : ''; ?>">
				<?php if ( $split_hero ) : ?>
				<div class="account-auth__hero"<?php echo $hero_style ? ' style="' . esc_attr( $hero_style ) . '"' : ''; ?>>
					<div class="account-auth__hero-inner">
						<?php if ( $hero_url ) : ?>
							<img
								src="<?php echo esc_url( $hero_url ); ?>"
								alt=""
								class="account-auth__hero-image"
								width="520"
								height="400"
								loading="lazy"
								decoding="async"
							>
						<?php endif; ?>
						<h2 class="account-auth__hero-title"><?php echo esc_html( $hero_heading ); ?></h2>
						<p class="account-auth__hero-text"><?php echo esc_html( $hero_text ); ?></p>
					</div>
				</div>
				<?php endif; ?>

				<div class="account-auth__panel">
					<div class="account-auth__panel-inner">
						<?php get_template_part( 'template-parts/account-auth/escape-links' ); ?>

						<header class="<?php echo esc_attr( $header_class ); ?>">
							<a class="account-auth__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
								<?php echo esc_html( tailwindscore_site_name() ); ?>
							</a>
							<?php if ( '' !== $subtitle ) : ?>
								<p class="account-auth__subtitle"><?php echo esc_html( $subtitle ); ?></p>
							<?php endif; ?>
						</header>

						<?php
						if ( 'login' === $context && isset( $_GET['registration'] ) && 'success' === $_GET['registration'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$auth_notices = wc_print_notice(
								esc_html__( 'Registration successful. You can now sign in.', 'tailwindscore' ),
								'success',
								array(),
								true
							);
						} else {
							$auth_notices = wc_print_notices( true );
						}

						if ( $auth_notices ) :
							?>
						<div class="woocommerce-notices-wrapper" data-ts-auth-notices hidden>
							<?php echo $auth_notices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
							<?php
						endif;
						?>
