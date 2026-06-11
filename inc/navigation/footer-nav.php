<?php
declare(strict_types=1);

/**
 * Footer navigation menu and Kirki settings.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed footer nav alignment values.
 *
 * @return array<string, string>
 */
function tailwindscore_footer_nav_alignment_choices(): array {
	return array(
		'left'   => esc_html__( 'Left', 'tailwindscore' ),
		'center' => esc_html__( 'Center', 'tailwindscore' ),
		'right'  => esc_html__( 'Right', 'tailwindscore' ),
	);
}

/**
 * Sanitize footer nav alignment theme_mod.
 *
 * @param mixed $value Raw value.
 */
function tailwindscore_footer_nav_alignment_sanitize( $value ): string {
	$value = is_string( $value ) ? $value : 'center';

	return array_key_exists( $value, tailwindscore_footer_nav_alignment_choices() ) ? $value : 'center';
}

/**
 * Footer nav horizontal alignment (left, center, right).
 */
function tailwindscore_footer_nav_alignment(): string {
	return tailwindscore_footer_nav_alignment_sanitize(
		tailwindscore_mod( 'footer_nav_alignment', 'center' )
	);
}

/**
 * BEM modifier class for footer nav alignment.
 */
function tailwindscore_footer_nav_alignment_class(): string {
	return 'ts-site-footer__nav--align-' . tailwindscore_footer_nav_alignment();
}

/**
 * Whether the footer nav row should render.
 */
function tailwindscore_should_render_footer_nav(): bool {
	return has_nav_menu( 'footer' );
}

/**
 * Build wp_nav_menu() args for the footer location.
 *
 * @return array<string, mixed>
 */
function tailwindscore_footer_nav_menu_args(): array {
	$menu_args = array(
		'theme_location' => 'footer',
		'container'      => false,
		'menu_class'     => 'ts-site-footer__nav-list',
		'depth'          => 1,
		'fallback_cb'    => false,
	);

	/**
	 * @param array<string, mixed> $menu_args
	 */
	return apply_filters( 'tailwindscore_footer_nav_menu_args', $menu_args );
}

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_footer_nav_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		$targets['footer'],
		array(
			'title'       => esc_html__( 'Footer', 'tailwindscore' ),
			'description' => esc_html__( 'Footer navigation and layout.', 'tailwindscore' ),
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'custom',
			'settings' => 'footer_nav_menu_notice',
			'section'  => $targets['footer'],
			'default'  => '<p class="description">' . esc_html__( 'Assign a menu under Appearance → Menus to the “Footer Menu” location. Links render as a single horizontal row above the copyright area.', 'tailwindscore' ) . '</p>',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'radio-buttonset',
			'settings'          => 'footer_nav_alignment',
			'label'             => esc_html__( 'Footer menu alignment', 'tailwindscore' ),
			'section'           => $targets['footer'],
			'default'           => 'center',
			'choices'           => tailwindscore_footer_nav_alignment_choices(),
			'sanitize_callback' => 'tailwindscore_footer_nav_alignment_sanitize',
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_footer_nav_kirki_register', 10, 2 );
