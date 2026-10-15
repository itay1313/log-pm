<?php
/**
 * Taxonomies.
 * - log_domain: the six practice areas (spec §6/§8 filter row).
 * - log_scale: S/M/L/XL, computed on save from units/sqm/dunam (see scale.php). Never set by hand.
 * - log_city: city/locality, used for filtering and the map labels.
 * - log_knowledge_type: article / position paper / press / behind the scenes.
 */

defined( 'ABSPATH' ) || exit;

function log_register_taxonomies(): void {
	register_taxonomy( 'log_domain', [ 'log_project', 'log_person', 'log_knowledge' ], [
		'labels' => [
			'name'          => 'תחומים',
			'singular_name' => 'תחום',
			'add_new_item'  => 'הוספת תחום',
			'search_items'  => 'חיפוש תחום',
		],
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'domain', 'with_front' => false ],
		'show_in_rest' => true,
	] );

	register_taxonomy( 'log_scale', [ 'log_project' ], [
		'labels' => [
			'name'          => 'מדרגות היקף',
			'singular_name' => 'מדרגת היקף',
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'meta_box_cb'       => false, // computed, not edited
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'scale', 'with_front' => false ],
		'show_in_rest'      => true,
	] );

	register_taxonomy( 'log_city', [ 'log_project' ], [
		'labels' => [
			'name'          => 'ערים',
			'singular_name' => 'עיר',
			'add_new_item'  => 'הוספת עיר',
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'city', 'with_front' => false ],
		'show_in_rest'      => true,
	] );

	register_taxonomy( 'log_knowledge_type', [ 'log_knowledge' ], [
		'labels' => [
			'name'          => 'סוגי פריטים',
			'singular_name' => 'סוג פריט',
			'add_new_item'  => 'הוספת סוג',
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'type', 'with_front' => false ],
		'show_in_rest'      => true,
	] );
}
add_action( 'init', 'log_register_taxonomies' );

/**
 * Default terms, inserted once. Slugs are stable so templates and the map can rely on them.
 */
function log_seed_terms(): void {
	$domains = [
		'urban-renewal'   => 'התחדשות עירונית',
		'master-plans'    => 'תכניות מתאר',
		'detailed-plans'  => 'תכניות מפורטות',
		'national-plans'  => 'תכניות לאומיות',
		'open-spaces'     => 'שטחים פתוחים',
		'rural-arab'      => 'המגזר הכפרי והחברה הערבית',
	];
	foreach ( $domains as $slug => $name ) {
		if ( ! term_exists( $slug, 'log_domain' ) ) {
			wp_insert_term( $name, 'log_domain', [ 'slug' => $slug ] );
		}
	}

	foreach ( [ 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL' ] as $slug => $name ) {
		if ( ! term_exists( $slug, 'log_scale' ) ) {
			wp_insert_term( $name, 'log_scale', [ 'slug' => $slug ] );
		}
	}

	$types = [
		'article'        => 'מאמר',
		'position-paper' => 'נייר עמדה',
		'press'          => 'הודעה לעיתונות',
		'behind-scenes'  => 'מאחורי הקלעים',
	];
	foreach ( $types as $slug => $name ) {
		if ( ! term_exists( $slug, 'log_knowledge_type' ) ) {
			wp_insert_term( $name, 'log_knowledge_type', [ 'slug' => $slug ] );
		}
	}
}
