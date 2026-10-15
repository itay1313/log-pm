<?php
/**
 * Strip WordPress head noise and features this site doesn't use.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
} );

// No blog, no comments, no XML-RPC.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_action( 'admin_menu', fn() => remove_menu_page( 'edit-comments.php' ) );

// Global styles / block library CSS are dead weight on a classic theme with no block content.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'wp-block-library' );
}, 20 );

// jQuery isn't used on the front end.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_admin() ) {
		wp_deregister_script( 'jquery' );
	}
} );
