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
 * Kirki panel id.
 */
function tailwindscore_kirki_panel_id(): string {
	return 'tailwindscore';
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
