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
 * In wp-admin every text field shows the copy that is live on the site, never an empty box.
 * The copy comes from the templates' fallbacks, copied into acf-json by tools/sync-text-defaults.py:
 * "default_value" on text fields, "log_default_rows" on lists. ACF fills default_value by itself only
 * while the field was never saved; this also covers a field that was saved empty, which the site
 * shows as the fallback anyway. Front end untouched: log_text()/log_rows() already fall back.
 */
add_filter( 'acf/load_value', function ( $value, $post_id, array $field ) {
	if ( ! is_admin() || ! in_array( $field['type'], [ 'text', 'textarea' ], true ) ) {
		return $value;
	}
	return ( $value === '' || $value === null ) && ( $field['default_value'] ?? '' ) !== '' ? $field['default_value'] : $value;
}, 20, 3 );

add_filter( 'acf/load_value/type=repeater', function ( $value, $post_id, array $field ) {
	if ( ! is_admin() || ! empty( $value ) || empty( $field['log_default_rows'] ) ) {
		return $value;
	}
	// Admin rows are keyed by sub field key, not name.
	$keys = array_column( $field['sub_fields'] ?? [], 'key', 'name' );
	return array_map(
		static fn( array $row ) => array_combine(
			array_map( static fn( $name ) => $keys[ $name ] ?? $name, array_keys( $row ) ),
			array_values( $row )
		),
		$field['log_default_rows']
	);
}, 20, 3 );

/**
 * One line above the text fields, so the editor knows what they're looking at.
 */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	$texts  = $screen && str_ends_with( $screen->id, '_page_log-texts' );
	$page   = $screen && $screen->base === 'post' && $screen->post_type === 'page' && log_page_has_texts( (int) ( $_GET['post'] ?? 0 ) );
	if ( ! $texts && ! $page ) {
		return;
	}
	echo '<div class="notice notice-info"><p>הטקסט בשדות הוא הטקסט שמופיע עכשיו באתר. משנים, שומרים, וזה עולה. שדה שמרוקנים חוזר לטקסט המקורי.</p></div>';
} );

/**
 * Text pages open in the classic screen: the block editor shows an empty canvas and folds the
 * fields into a strip at the bottom, where nobody finds them. Classic puts the fields first,
 * and ACF's hide_on_screen drops the content box on home and contact, whose templates don't print it.
 */
add_filter( 'use_block_editor_for_post', function ( bool $use, WP_Post $post ): bool {
	return log_page_has_texts( $post->ID ) ? false : $use;
}, 10, 2 );

/**
 * Pages whose template reads its texts from ACF (home, about, control, contact).
 */
function log_page_has_texts( int $page_id ): bool {
	if ( ! $page_id ) {
		return false;
	}
	return $page_id === (int) get_option( 'page_on_front' )
		|| in_array( log_page_template( $page_id ), [ 'page-about.php', 'page-control.php', 'page-contact.php' ], true );
}

/**
 * The template a page actually renders with. The theme's page-{slug}.php files have no
 * "Template Name", so the editor's template select doesn't list them and every save writes
 * "default" back, while WordPress still picks page-about.php by slug. Resolve it the same way.
 */
function log_page_template( int $page_id ): string {
	$set = get_page_template_slug( $page_id );
	if ( $set ) {
		return $set;
	}
	$slug = rawurldecode( (string) get_post_field( 'post_name', $page_id ) );
	return $slug && locate_template( "page-$slug.php" ) ? "page-$slug.php" : '';
}

/**
 * ACF's "page template" location rule reads the saved template only; give it the resolved one,
 * or the About, Control and Contact fields vanish the first time someone saves those pages.
 */
add_filter( 'acf/location/rule_match/page_template', function ( $match, array $rule, array $screen ) {
	$page_id = (int) ( $screen['post_id'] ?? 0 );
	if ( ! $page_id || get_post_type( $page_id ) !== 'page' || get_page_template_slug( $page_id ) ) {
		return $match;
	}
	$is = log_page_template( $page_id ) === $rule['value'];
	return $rule['operator'] === '==' ? $is : ! $is;
}, 10, 3 );

/**
 * Admin bar on the site: a direct way from the page you're reading to the fields that hold its text.
 * Pages get WordPress's own "edit page"; archives and system pages read from the Texts options page.
 */
add_action( 'admin_bar_menu', function ( WP_Admin_Bar $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_pages' ) || ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	if ( is_post_type_archive( [ 'log_project', 'log_job', 'log_knowledge' ] ) || is_singular( 'log_job' ) || is_404() || is_search() ) {
		$bar->add_node( [
			'id'    => 'log-texts',
			'title' => 'עריכת הטקסטים בעמוד',
			'href'  => admin_url( 'admin.php?page=log-texts' ),
		] );
	}
}, 80 );

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
	} elseif ( $term_id = log_term_id( $post_id ) ) {
		$v = get_term_meta( $term_id, $name, true );
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
		return;
	}
	// Without ACF, write exactly what ACF would: the value plus the "_name" → field key reference,
	// so the data is ACF-native the moment ACF Pro is activated.
	$key = log_acf_field_key( $name, $post_id );
	if ( $post_id === 'option' || $post_id === 'options' ) {
		update_option( "options_$name", $value, false );
		if ( $key ) {
			update_option( "_options_$name", $key, false );
		}
	} elseif ( $term_id = log_term_id( $post_id ) ) {
		update_term_meta( $term_id, $name, $value );
		if ( $key ) {
			update_term_meta( $term_id, "_$name", $key );
		}
	} else {
		update_post_meta( (int) $post_id, $name, $value );
		if ( $key ) {
			update_post_meta( (int) $post_id, "_$name", $key );
		}
	}
}

/**
 * Field name → ACF field key, from the versioned acf-json groups (top-level fields), per post type
 * or options page, since names repeat across groups (e.g. "url" on clients and on social posts).
 */
function log_acf_field_key( string $name, $post_id = null ): ?string {
	static $map = null;
	if ( $map === null ) {
		$map = [];
		foreach ( glob( LOG_CORE_DIR . 'acf-json/*.json' ) ?: [] as $file ) {
			$group = json_decode( (string) file_get_contents( $file ), true );
			$scopes = [];
			foreach ( $group['location'] ?? [] as $or ) {
				foreach ( $or as $rule ) {
					if ( ( $rule['param'] ?? '' ) === 'post_type' ) {
						$scopes[] = $rule['value'];
					} elseif ( ( $rule['param'] ?? '' ) === 'options_page' ) {
						$scopes[] = 'option';
					} elseif ( ( $rule['param'] ?? '' ) === 'taxonomy' ) {
						$scopes[] = 'term';
					}
				}
			}
			foreach ( $group['fields'] ?? [] as $f ) {
				if ( empty( $f['name'] ) ) {
					continue;
				}
				foreach ( $scopes ?: [ '*' ] as $s ) {
					$map[ $s ][ $f['name'] ] ??= $f['key'];
				}
			}
		}
	}
	$scope = match ( true ) {
		$post_id === 'option' || $post_id === 'options' => 'option',
		(bool) log_term_id( $post_id )                  => 'term',
		(bool) $post_id                                 => get_post_type( (int) $post_id ),
		default                                         => '*',
	};
	return $map[ $scope ][ $name ] ?? null;
}

/**
 * ACF's term address ("term_12", "log_domain_12" or a WP_Term) → term ID; 0 for anything else.
 */
function log_term_id( $post_id ): int {
	if ( $post_id instanceof WP_Term ) {
		return $post_id->term_id;
	}
	if ( is_string( $post_id ) && preg_match( '/^(?:term|[a-z_]+)_(\d+)$/', $post_id, $m ) && ! ctype_digit( $post_id ) ) {
		return (int) $m[1];
	}
	return 0;
}
