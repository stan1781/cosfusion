<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Kirki config id.
 */
function tailwindscore_kirki_config_id(): string {
	return 'tailwindscore';
}

/**
 * Kirki panel id (legacy, for backward compatibility).
 */
function tailwindscore_kirki_panel_id(): string {
	return 'tailwindscore';
}

/**
 * Kirki panel id: Header.
 */
function tailwindscore_kirki_panel_header(): string {
	return 'tailwindscore_header';
}

/**
 * Kirki panel id: Footer.
 */
function tailwindscore_kirki_panel_footer(): string {
	return 'tailwindscore_footer';
}

/**
 * Customizer target IDs: theme panels plus native WordPress / WooCommerce panels and sections.
 *
 * @return array<string, string>
 */
function tailwindscore_kirki_customizer_targets(): array {
	return array(
		'header'      => tailwindscore_kirki_panel_header(),
		'footer'      => tailwindscore_kirki_panel_footer(),
		'woocommerce' => 'woocommerce',
		'product'     => 'woocommerce',
		'account'     => 'woocommerce',
		'homepage'    => 'static_front_page',
		'shop'        => 'woocommerce_product_catalog',
		'checkout'    => 'woocommerce_checkout',
	);
}

/**
 * Register a placeholder section with an informational custom field.
 *
 * Customizer hides sections that have no controls, so a custom field keeps the section visible.
 *
 * @param string      $cfg         Kirki config id.
 * @param string      $section_id  Section id.
 * @param string|null $panel_id    Parent panel id, or null for a top-level section.
 * @param string      $title       Section title.
 * @param string      $description Placeholder copy.
 * @param int         $priority    Section priority.
 */
function tailwindscore_kirki_register_section_placeholder(
	string $cfg,
	string $section_id,
	?string $panel_id,
	string $title,
	string $description,
	int $priority = 10
): void {
	$section_args = array(
		'title'       => $title,
		'description' => $description,
		'priority'    => $priority,
	);

	if ( null !== $panel_id ) {
		$section_args['panel'] = $panel_id;
	}

	Kirki::add_section(
		$section_id,
		$section_args
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'custom',
			'settings' => $section_id . '_notice',
			'section'  => $section_id,
			'default'  => '<p class="description">' . esc_html( $description ) . '</p>',
		)
	);
}

/**
 * Read theme_mod with default (no legacy option fallback).
 *
 * @param string $key     Setting name.
 * @param mixed  $default Default when unset.
 * @return mixed
 */
function tailwindscore_mod( string $key, $default = null ) {
	$value = get_theme_mod( $key, $default );
	return ( null === $value || '' === $value ) && null !== $default ? $default : $value;
}

/**
 * Merge parent theme_mods into the active child theme's mods (child values win).
 *
 * WordPress stores Customizer / Kirki data in theme_mods_{stylesheet}. When a child
 * theme is active, that option is separate from the parent's. The pre_option filter
 * receives false (not the DB value); child mods must be loaded explicitly while the
 * filter is temporarily removed to avoid recursion.
 *
 * @param mixed $pre Value from a prior pre_option filter, or false.
 * @return mixed
 */
function tailwindscore_filter_child_theme_mods( $pre ) {
	if ( false !== $pre ) {
		return $pre;
	}

	$stylesheet = get_stylesheet();
	$template   = get_template();

	if ( $stylesheet === $template ) {
		return $pre;
	}

	$filter = 'pre_option_theme_mods_' . $stylesheet;
	remove_filter( $filter, 'tailwindscore_filter_child_theme_mods' );

	$child_mods  = get_option( "theme_mods_{$stylesheet}", array() );
	$parent_mods = get_option( "theme_mods_{$template}", array() );

	add_filter( $filter, 'tailwindscore_filter_child_theme_mods' );

	$parent_mods = is_array( $parent_mods ) ? $parent_mods : array();
	$child_mods  = is_array( $child_mods ) ? $child_mods : array();

	return array_replace( $parent_mods, $child_mods );
}

/**
 * Register theme_mod inheritance when this theme is used as a parent.
 */
function tailwindscore_register_child_theme_mods_inherit(): void {
	if ( get_stylesheet() === get_template() ) {
		return;
	}

	add_filter(
		'pre_option_theme_mods_' . get_stylesheet(),
		'tailwindscore_filter_child_theme_mods'
	);
}
add_action( 'after_setup_theme', 'tailwindscore_register_child_theme_mods_inherit', 1 );
