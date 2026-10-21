<?php
/**
 * Taxonomies (spec v6 §17).
 * - log_domain: practice areas. Feeds the filters and the home domains block. Term fields: sentence, order.
 * - log_authority: the local authority (ArcGIS רשות_מקומית). Feeds the "רשות" filter.
 * - log_stage: stage 1–4 or "הושלם". Computed on save (stage.php), never set by hand. Term meta `number`.
 * - log_unit: organisational unit of a team member. "חברת הבקרה" also feeds the control page.
 * - log_knowledge_type: articles / position papers / announcements / media mentions.
 */

defined( 'ABSPATH' ) || exit;

function log_register_taxonomies(): void {
	register_taxonomy( 'log_domain', [ 'log_project' ], [
		'labels' => [
			'name'          => 'תחומי פעילות',
			'singular_name' => 'תחום',
			'add_new_item'  => 'הוספת תחום',
			'search_items'  => 'חיפוש תחום',
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'rewrite'           => false, // /projects?domain=slug is the only filtered address
		'show_in_rest'      => true,
	] );

	register_taxonomy( 'log_authority', [ 'log_project' ], [
		'labels' => [
			'name'          => 'רשויות',
			'singular_name' => 'רשות',
			'add_new_item'  => 'הוספת רשות',
			'search_items'  => 'חיפוש רשות',
		],
		'hierarchical'      => false,
		'public'            => true,
		'publicly_queryable' => false,
		'show_admin_column' => true,
		'rewrite'           => false,
		'show_in_rest'      => true,
	] );

	register_taxonomy( 'log_stage', [ 'log_project' ], [
		'labels' => [
			'name'          => 'שלבים',
			'singular_name' => 'שלב',
		],
		'hierarchical'       => false,
		'public'             => true,
		'publicly_queryable' => false,
		'show_ui'            => true, // names are editable; assignment is not
		'meta_box_cb'        => false,
		'show_admin_column'  => true,
		'rewrite'            => false,
		'show_in_rest'       => true,
	] );

	register_taxonomy( 'log_unit', [ 'log_person' ], [
		'labels' => [
			'name'          => 'יחידות ארגוניות',
			'singular_name' => 'יחידה ארגונית',
		],
		'hierarchical'       => true, // checkbox UI
		'public'             => false,
		'show_ui'            => true,
		'show_admin_column'  => true,
		'rewrite'            => false,
		'show_in_rest'       => true,
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
		'rewrite'           => false, // /knowledge?type=slug
		'show_in_rest'      => true,
	] );
}
add_action( 'init', 'log_register_taxonomies' );

/** Stage terms: slug → [default name, number]. Number 0 = completed. Names are LOG's to confirm. */
function log_stage_terms(): array {
	return [
		'stage-1'   => [ 'איתור והיתכנות', 1 ],
		'stage-2'   => [ 'תכנון וקידום סטטוטורי', 2 ],
		'stage-3'   => [ 'ליווי ואישור', 3 ],
		'stage-4'   => [ 'בקרה ומעקב', 4 ],
		'completed' => [ 'הושלם', 0 ],
	];
}

/** Organisational units, in display order (spec v6 §12). */
function log_unit_terms(): array {
	return [
		'management'       => 'הנהלה',
		'team-leads'       => 'ראשי צוותים',
		'project-managers' => 'מנהלי פרויקטים',
		'control'          => 'חברת הבקרה',
		'operations'       => 'תפעול',
	];
}

function log_knowledge_type_terms(): array {
	return [
		'article'        => 'מאמרים',
		'position-paper' => 'ניירות עמדה',
		'news'           => 'הכרזות וחדשות חברה',
		'media'          => 'אזכורים בתקשורת',
	];
}

/**
 * Default terms, inserted once. Slugs are stable so templates, URLs and the map can rely on them.
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
	$order = 0;
	foreach ( $domains as $slug => $name ) {
		$order++;
		if ( ! term_exists( $slug, 'log_domain' ) ) {
			$t = wp_insert_term( $name, 'log_domain', [ 'slug' => $slug ] );
			if ( ! is_wp_error( $t ) ) {
				log_set_field( 'order', $order, 'term_' . $t['term_id'] );
			}
		}
	}

	foreach ( log_stage_terms() as $slug => [ $name, $number ] ) {
		if ( ! term_exists( $slug, 'log_stage' ) ) {
			$t = wp_insert_term( $name, 'log_stage', [ 'slug' => $slug ] );
			if ( ! is_wp_error( $t ) ) {
				update_term_meta( $t['term_id'], 'number', $number );
			}
		}
	}

	$order = 0;
	foreach ( log_unit_terms() as $slug => $name ) {
		$order++;
		if ( ! term_exists( $slug, 'log_unit' ) ) {
			$t = wp_insert_term( $name, 'log_unit', [ 'slug' => $slug ] );
			if ( ! is_wp_error( $t ) ) {
				update_term_meta( $t['term_id'], 'order', $order );
			}
		}
	}

	foreach ( log_knowledge_type_terms() as $slug => $name ) {
		if ( ! term_exists( $slug, 'log_knowledge_type' ) ) {
			wp_insert_term( $name, 'log_knowledge_type', [ 'slug' => $slug ] );
		}
	}
}

/**
 * Terms of a taxonomy sorted by their `order` meta (then name). Domains and units use it.
 *
 * @return WP_Term[]
 */
function log_ordered_terms( string $taxonomy, bool $hide_empty = false ): array {
	$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => $hide_empty ] );
	if ( ! is_array( $terms ) ) {
		return [];
	}
	usort( $terms, static function ( WP_Term $a, WP_Term $b ): int {
		$oa = (int) get_term_meta( $a->term_id, 'order', true ) ?: 999;
		$ob = (int) get_term_meta( $b->term_id, 'order', true ) ?: 999;
		return $oa <=> $ob ?: strcmp( $a->name, $b->name );
	} );
	return $terms;
}
