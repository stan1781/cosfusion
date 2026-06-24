<?php
declare(strict_types=1);

/**
 * Footer navigation, payment icons, and Kirki settings.
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
 * Resolve a theme_mod image value to a valid attachment id.
 *
 * @param mixed $image Raw value from theme_mod.
 */
function tailwindscore_footer_payment_image_attachment_id( $image ): int {
	if ( is_numeric( $image ) ) {
		$attachment_id = absint( $image );

		return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
	}

	$url = is_string( $image ) ? trim( $image ) : '';

	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );

	return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
}

/**
 * Footer payment image attachment id from Customizer.
 */
function tailwindscore_footer_payment_image_id(): int {
	return tailwindscore_footer_payment_image_attachment_id(
		tailwindscore_mod( 'footer_payment_image', '' )
	);
}

/**
 * Whether a footer payment image is configured.
 */
function tailwindscore_footer_has_payment_image(): bool {
	return tailwindscore_footer_payment_image_id() > 0;
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

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'footer_payment_image',
			'label'       => esc_html__( 'Payment icons', 'tailwindscore' ),
			'description' => esc_html__( 'Upload a single composite image of accepted payment methods, shown beside the copyright line.', 'tailwindscore' ),
			'section'     => $targets['footer'],
			'priority'    => 20,
			'default'     => '',
			'choices'     => array(
				'save_as' => 'id',
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_footer_nav_kirki_register', 10, 2 );
