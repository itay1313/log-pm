<?php
/**
 * The web brand book at /brand (template page-brand.php in the theme). It is a working document for LOG and
 * its suppliers, not part of the public site: password-protected, never indexed, never listed, never searched.
 * The site's gate lets it through (site-gate.php): its own password is enough.
 */

defined( 'ABSPATH' ) || exit;

const LOG_BRAND_BOOK_SLUG = 'brand';

function log_brand_book_id(): int {
	$page = get_page_by_path( LOG_BRAND_BOOK_SLUG );
	return $page ? (int) $page->ID : 0;
}

/**
 * A new brand page starts locked with a random password. Nobody types it from here: an editor reads or
 * changes it in the page's "Visibility" box and sends it to whoever needs the book.
 */
function log_brand_book_protect( int $id ): void {
	if ( $id && get_post_field( 'post_password', $id ) === '' ) {
		wp_update_post( [ 'ID' => $id, 'post_password' => wp_generate_password( 12, false ) ] );
	}
}

// Never indexed: the robots meta, plus the header for anything that reads only headers.
add_filter( 'wp_robots', function ( array $robots ): array {
	return is_page( LOG_BRAND_BOOK_SLUG ) ? wp_robots_no_robots( $robots ) : $robots;
} );

// Yoast (on uPress) prints its own robots meta and sitemaps; it gets the same answer.
add_filter( 'wpseo_robots', fn( $robots ) => is_page( LOG_BRAND_BOOK_SLUG ) ? 'noindex, nofollow' : $robots );
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$id = log_brand_book_id();
	return $id ? array_merge( (array) $ids, [ $id ] ) : $ids;
} );

add_action( 'template_redirect', function () {
	if ( is_page( LOG_BRAND_BOOK_SLUG ) ) {
		header( 'X-Robots-Tag: noindex, nofollow' );
		nocache_headers();
	}
}, -1 ); // before the site's gate, which runs at 0

// Never listed: out of the WP sitemap even if the password is ever removed.
add_filter( 'wp_sitemaps_posts_query_args', function ( array $args, string $post_type ): array {
	if ( $post_type === 'page' && ( $id = log_brand_book_id() ) ) {
		$args['post__not_in'] = array_merge( $args['post__not_in'] ?? [], [ $id ] );
	}
	return $args;
}, 10, 2 );

/*
 * Never searched. log_search() (search.php) reads only projects, knowledge and open jobs, and the main /?s=
 * query is pinned to knowledge, so pages never show there. Core's REST search would still list the page
 * title; take it out of that too.
 */
add_filter( 'rest_post_search_query', function ( array $args ): array {
	if ( $id = log_brand_book_id() ) {
		$args['post__not_in'] = array_merge( $args['post__not_in'] ?? [], [ $id ] );
	}
	return $args;
} );
