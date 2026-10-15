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
 * Projects and knowledge items show up in site search; people and clients never do.
 * Posts (the blog) are not part of this site, so they're excluded too.
 */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_search() ) {
		$q->set( 'post_type', [ 'log_project', 'log_job', 'log_knowledge', 'page' ] );
	}
	if ( $q->is_post_type_archive( 'log_project' ) ) {
		$q->set( 'posts_per_page', 24 );
		$q->set( 'orderby', 'menu_order date' );
		$q->set( 'order', 'DESC' );
	}
} );
