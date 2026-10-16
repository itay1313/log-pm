<?php
/**
 * Theme supports, menus, image sizes.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	remove_theme_support( 'core-block-patterns' );

	register_nav_menus( [
		'primary' => 'תפריט ראשי (כותרת)',
		'twin'    => 'שני הכפתורים הצמודים',
		'footer'  => 'תפריט תחתון',
	] );

	// Card and hero media. Width-first so RTL layouts stay predictable.
	add_image_size( 'log-card', 800, 600, true );
	add_image_size( 'log-wide', 1600, 900, true );
	add_image_size( 'log-portrait', 600, 800, true );
} );

/**
 * Hebrew first. WPLANG is he_IL; make sure the html element says so even on fresh installs.
 */
add_filter( 'language_attributes', fn( $out ) => str_contains( $out, 'dir=' ) ? $out : $out . ' dir="rtl"' );

// Pages that open on a full-screen picture: the header sits on it (main.css "has-cover").
add_filter( 'body_class', function ( array $classes ): array {
	if ( is_front_page() || is_singular( 'log_project' ) ) {
		$classes[] = 'has-cover';
	}
	return $classes;
} );
