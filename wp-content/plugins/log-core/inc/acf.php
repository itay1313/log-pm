<?php
/**
 * ACF glue: field groups live in log-core/acf-json so they're versioned and
 * sync between local and uPress automatically. Also registers the site options page
 * (offices, phones, socials) that the footer, contact page and schema read from.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'acf/settings/save_json', fn() => LOG_CORE_DIR . 'acf-json' );
add_filter( 'acf/settings/load_json', function ( array $paths ) {
	$paths[] = LOG_CORE_DIR . 'acf-json';
	return $paths;
} );

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( [
		'page_title' => 'הגדרות האתר',
		'menu_title' => 'הגדרות לוג',
		'menu_slug'  => 'log-settings',
		'capability' => 'manage_options',
		'position'   => 59,
		'icon_url'   => 'dashicons-admin-site-alt3',
		'redirect'   => false,
	] );
} );

/**
 * Loud notice when ACF Pro is missing: the whole content model depends on it.
 */
add_action( 'admin_notices', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>LOG Core:</strong> נדרש ACF Pro. בלעדיו שדות הפרויקטים, המשרות והצוות לא יוצגו.</p></div>';
} );

/**
 * Safe field getter that works before ACF loads (and in WP-CLI/REST contexts).
 */
function log_field( string $name, $post_id = false, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$v = get_field( $name, $post_id );
	return ( $v === null || $v === '' || $v === false ) ? $default : $v;
}

function log_option( string $name, $default = null ) {
	return log_field( $name, 'option', $default );
}
