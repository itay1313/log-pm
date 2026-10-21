<?php
/**
 * Custom post types. URLs follow spec v5.1 §4:
 * /projects/{slug}, /careers/{slug}, /knowledge/{slug}.
 * People and clients are internal: no public URL, used by templates only.
 */

defined( 'ABSPATH' ) || exit;

function log_register_post_types(): void {
	register_post_type( 'log_project', [
		'labels' => [
			'name'               => 'פרויקטים',
			'singular_name'      => 'פרויקט',
			'add_new'            => 'פרויקט חדש',
			'add_new_item'       => 'הוספת פרויקט',
			'edit_item'          => 'עריכת פרויקט',
			'all_items'          => 'כל הפרויקטים',
			'search_items'       => 'חיפוש פרויקטים',
			'not_found'          => 'לא נמצאו פרויקטים',
			'featured_image'     => 'מדיה ראשית',
			'set_featured_image' => 'בחירת מדיה ראשית',
		],
		'public'        => true,
		'has_archive'   => 'projects',
		'rewrite'       => [ 'slug' => 'projects', 'with_front' => false ],
		'menu_position' => 5,
		'menu_icon'     => 'dashicons-location-alt',
		'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
		'show_in_rest'  => true,
	] );

	register_post_type( 'log_job', [
		'labels' => [
			'name'          => 'משרות',
			'singular_name' => 'משרה',
			'add_new'       => 'משרה חדשה',
			'add_new_item'  => 'הוספת משרה',
			'edit_item'     => 'עריכת משרה',
			'all_items'     => 'כל המשרות',
			'not_found'     => 'אין משרות',
		],
		'public'        => true,
		'has_archive'   => 'careers',
		'rewrite'       => [ 'slug' => 'careers', 'with_front' => false ],
		'menu_position' => 6,
		'menu_icon'     => 'dashicons-businessperson',
		'supports'      => [ 'title', 'editor', 'revisions' ],
		'show_in_rest'  => true,
	] );

	register_post_type( 'log_knowledge', [
		'labels' => [
			'name'          => 'מרכז ידע',
			'singular_name' => 'פריט ידע',
			'add_new'       => 'פריט חדש',
			'add_new_item'  => 'הוספת פריט ידע',
			'edit_item'     => 'עריכת פריט ידע',
			'all_items'     => 'כל הפריטים',
			'not_found'     => 'אין פריטי ידע',
		],
		'public'        => true,
		'has_archive'   => 'knowledge',
		'rewrite'       => [ 'slug' => 'knowledge', 'with_front' => false ],
		'menu_position' => 7,
		'menu_icon'     => 'dashicons-media-document',
		'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ],
		'show_in_rest'  => true,
	] );

	register_post_type( 'log_person', [
		'labels' => [
			'name'          => 'צוות',
			'singular_name' => 'איש צוות',
			'add_new'       => 'הוספת איש צוות',
			'add_new_item'  => 'הוספת איש צוות',
			'edit_item'     => 'עריכה',
			'all_items'     => 'כל הצוות',
			'not_found'     => 'אין אנשי צוות',
		],
		'public'              => false,
		'show_ui'             => true,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'menu_position'       => 8,
		'menu_icon'           => 'dashicons-groups',
		'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
		'show_in_rest'        => true,
	] );

	register_post_type( 'log_client', [
		'labels' => [
			'name'          => 'לקוחות ושותפים',
			'singular_name' => 'לקוח',
			'add_new'       => 'הוספת לקוח',
			'add_new_item'  => 'הוספת לקוח',
			'edit_item'     => 'עריכה',
			'all_items'     => 'כל הלקוחות',
			'not_found'     => 'אין לקוחות',
		],
		'public'              => false,
		'show_ui'             => true,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'menu_position'       => 9,
		'menu_icon'           => 'dashicons-building',
		'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
		'show_in_rest'        => true,
	] );
}
add_action( 'init', 'log_register_post_types' );

/**
 * Classic editor for the structured types: the ACF fields are the content, the body is a footnote.
 * Knowledge items keep the block editor since they're long-form.
 */
add_filter( 'use_block_editor_for_post_type', function ( bool $use, string $type ): bool {
	return in_array( $type, [ 'log_project', 'log_job', 'log_person', 'log_client' ], true ) ? false : $use;
}, 10, 2 );

/**
 * The projects archive: 24 per page server-side (the template renders them all; projects.js pages them).
 * Site search is handled in search.php / inc/search.php.
 */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'log_project' ) ) {
		$q->set( 'posts_per_page', 24 );
		$q->set( 'orderby', 'menu_order date' );
		$q->set( 'order', 'DESC' );
	}
} );

/**
 * Closed jobs (spec v6 §14): the address keeps working and says the job is closed, but it's
 * kept out of Google (noindex) and out of the sitemap. Reopening restores both.
 */
function log_job_is_closed( int $id ): bool {
	return get_post_type( $id ) === 'log_job' && ! log_field( 'is_open', $id );
}
add_filter( 'wp_robots', function ( array $robots ): array {
	if ( is_singular( 'log_job' ) && log_job_is_closed( get_queried_object_id() ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );
add_filter( 'wpseo_robots', function ( $robots ) {
	return is_singular( 'log_job' ) && log_job_is_closed( get_queried_object_id() ) ? 'noindex, follow' : $robots;
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( array $args, string $type ): array {
	if ( $type === 'log_job' ) {
		$args['meta_query'] = [ [ 'key' => 'is_open', 'value' => '1' ] ];
	}
	return $args;
}, 10, 2 );
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$closed = get_posts( [
		'post_type'      => 'log_job',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => [ 'relation' => 'OR', [ 'key' => 'is_open', 'value' => '1', 'compare' => '!=' ], [ 'key' => 'is_open', 'compare' => 'NOT EXISTS' ] ],
	] );
	return array_merge( (array) $ids, $closed );
} );
