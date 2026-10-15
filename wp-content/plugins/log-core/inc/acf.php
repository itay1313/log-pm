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
	acf_add_options_sub_page( [
		'page_title'  => 'טקסטים: ארכיונים ועמודי מערכת',
		'menu_title'  => 'טקסטים',
		'menu_slug'   => 'log-texts',
		'parent_slug' => 'log-settings',
		'capability'  => 'edit_pages',
	] );
} );

/**
 * Text helper for templates: an ACF value if the editor filled it, otherwise the fallback copy.
 * $post_id false = current post; 'option' = the settings/texts pages.
 */
function log_text( string $name, string $fallback, $post_id = false ): string {
	$v = log_field( $name, $post_id );
	return is_string( $v ) && trim( $v ) !== '' ? $v : $fallback;
}

/**
 * Repeater helper: the editor's rows if any, otherwise the fallback rows.
 */
function log_rows( string $name, array $fallback, $post_id = false ): array {
	$v = log_field( $name, $post_id );
	return is_array( $v ) && $v ? $v : $fallback;
}

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
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name, $post_id );
	} elseif ( $post_id === 'option' || $post_id === 'options' ) {
		$v = get_option( "options_$name" );
	} else {
		$v = get_post_meta( $post_id ? (int) $post_id : get_the_ID(), $name, true );
	}
	return ( $v === null || $v === '' || $v === false ) ? $default : $v;
}

function log_option( string $name, $default = null ) {
	return log_field( $name, 'option', $default );
}

/**
 * Safe field setter: ACF's update_field when ACF is loaded, plain meta otherwise.
 * Keeps importers and cron jobs working before ACF Pro is installed; ACF reads the same meta later.
 */
function log_set_field( string $name, $value, $post_id ): void {
	if ( function_exists( 'update_field' ) ) {
		update_field( $name, $value, $post_id );
	} elseif ( $post_id === 'option' || $post_id === 'options' ) {
		update_option( "options_$name", $value, false );
	} else {
		update_post_meta( (int) $post_id, $name, $value );
	}
}
