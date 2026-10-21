<?php
/**
 * Project publishing rules and addresses (spec v6 §8–10, §18).
 *
 * Two separate switches:
 * - "מאושר לפרסום" (approved, Kobi's call): the project exists on the public site at all, on the map and in the list.
 * - "עמוד מלא" (full_page): it also gets /projects/{slug}. A full page needs a name, a domain, a stage
 *   and a professional story (at least "האתגר" or "העבודה של לוג"). Without one, the project lives only
 *   as a map popup and a card without a link.
 *
 * A project's map address is /?p={slug}: the home map opens on it with its popup open.
 */

defined( 'ABSPATH' ) || exit;

function log_project_is_public( int $post_id ): bool {
	return get_post_type( $post_id ) === 'log_project'
		&& get_post_status( $post_id ) === 'publish'
		&& (bool) log_field( 'approved', $post_id )
		&& ! in_array( log_field( 'status', $post_id, '01' ), [ '03', '99' ], true ); // stopped / cancelled
}

/**
 * Whether the page's minimum content is there. Missing parts, by label, for the admin notice.
 *
 * @return string[]
 */
function log_project_page_missing( int $post_id ): array {
	$missing = [];
	if ( trim( get_the_title( $post_id ) ) === '' ) {
		$missing[] = 'שם';
	}
	if ( ! has_term( '', 'log_domain', $post_id ) ) {
		$missing[] = 'תחום פעילות';
	}
	if ( ! has_term( '', 'log_stage', $post_id ) ) {
		$missing[] = 'שלב או סטטוס';
	}
	if ( ! trim( (string) log_field( 'challenge', $post_id, '' ) ) && ! trim( (string) log_field( 'process', $post_id, '' ) ) ) {
		$missing[] = 'סיפור מקצועי (לפחות "האתגר" או "העבודה של לוג")';
	}
	return $missing;
}

function log_project_has_page( int $post_id ): bool {
	return log_project_is_public( $post_id )
		&& (bool) log_field( 'full_page', $post_id )
		&& ! log_project_page_missing( $post_id );
}

/** /?p={slug}. Re-encoded upper-case, as WordPress's canonical redirect wants it (stored slugs are lower-case %d7…). */
function log_project_map_url( int $post_id ): string {
	return home_url( '/?p=' . rawurlencode( rawurldecode( get_post_field( 'post_name', $post_id ) ) ) );
}

/** Where a card or a search result goes: the full page if there is one, else the project on the map. */
function log_project_link( int $post_id ): string {
	return log_project_has_page( $post_id ) ? get_permalink( $post_id ) : log_project_map_url( $post_id );
}

/**
 * Years in process, from the start year. Null when there's no start year or the project is completed.
 */
function log_project_years( int $post_id ): ?int {
	$start = (int) log_field( 'year', $post_id, 0 );
	if ( $start < 1950 || log_field( 'status', $post_id, '01' ) === '02' ) {
		return null;
	}
	return max( 1, (int) wp_date( 'Y' ) - $start + 1 );
}

/**
 * /?p={slug} belongs to the home map, not to WordPress. WP reads `p` as a post ID, and any
 * query var turns the static front page into the blog index. Drop non-numeric `p` before WP sees it;
 * the map reads it from location.search. Numeric ?p=123 (WP's own shortlinks) is left alone.
 */
add_filter( 'request', function ( array $vars ): array {
	if ( isset( $vars['p'] ) && ! ctype_digit( (string) $vars['p'] ) ) {
		unset( $vars['p'] );
	}
	return $vars;
} );

/**
 * /projects/{slug} only exists for projects with a full page.
 * - Not public (unapproved, stopped, draft): 404.
 * - Public, no full page: the project on the map, so an old or shared link still lands somewhere useful.
 * Logged-in editors still see the page, to preview it before switching "עמוד מלא" on.
 */
add_action( 'template_redirect', function () {
	if ( ! is_singular( 'log_project' ) || current_user_can( 'edit_posts' ) ) {
		return;
	}
	$id = get_queried_object_id();
	if ( ! log_project_is_public( $id ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}
	if ( ! log_project_has_page( $id ) ) {
		wp_safe_redirect( log_project_map_url( $id ), 302 );
		exit;
	}
} );

/** Projects without a full page stay out of the sitemap. */
add_filter( 'wp_sitemaps_posts_query_args', function ( array $args, string $type ): array {
	if ( $type === 'log_project' ) {
		$args['meta_query'] = [
			[ 'key' => 'approved', 'value' => '1' ],
			[ 'key' => 'full_page', 'value' => '1' ],
		];
	}
	return $args;
}, 10, 2 );
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$q = new WP_Query( [
		'post_type'      => 'log_project',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	] );
	$hidden = array_filter( $q->posts, static fn( $id ) => ! log_project_has_page( (int) $id ) );
	return array_merge( (array) $ids, array_values( $hidden ) );
} );

/**
 * Editor feedback: "עמוד מלא" is on but the page can't be published yet.
 */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'log_project' ) {
		return;
	}
	$id = (int) ( $_GET['post'] ?? 0 );
	if ( ! $id || ! log_field( 'full_page', $id ) ) {
		return;
	}
	$missing = log_project_page_missing( $id );
	if ( $missing ) {
		printf(
			'<div class="notice notice-warning"><p><strong>העמוד המלא לא יוצג עדיין.</strong> חסר: %s.</p></div>',
			esc_html( implode( ', ', $missing ) )
		);
	} elseif ( ! log_project_is_public( $id ) ) {
		echo '<div class="notice notice-info"><p>העמוד המלא מוכן, ויוצג אחרי אישור הפרויקט לפרסום.</p></div>';
	}
} );

/**
 * A project from a URL parameter: its slug (/contact?project=holon-hachava) or, for old links, its ID.
 * Public projects only; 0 otherwise.
 */
function log_project_from_param( $value ): int {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	if ( $value === '' ) {
		return 0;
	}
	if ( ctype_digit( $value ) ) {
		$id = (int) $value;
	} else {
		$post = get_page_by_path( sanitize_title( rawurldecode( $value ) ), OBJECT, 'log_project' );
		$id   = $post ? $post->ID : 0;
	}
	return $id && log_project_is_public( $id ) ? $id : 0;
}

/**
 * Israeli grid (ITM) from WGS84, for the grid reference every project carries (spec v6 visual world:
 * survey sheets). Transverse Mercator on GRS80 with the ITM origin, no datum shift: good to a few tens
 * of metres, shown rounded to 10 m. Same formula as the theme's src/itm.js.
 *
 * @return array{0:float,1:float}|null [easting, northing]
 */
function log_itm( $lat, $lng ): ?array {
	if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
		return null;
	}
	$a = 6378137.0; $f = 1 / 298.257222101; $e2 = $f * ( 2 - $f ); $ep2 = $e2 / ( 1 - $e2 );
	$rad = M_PI / 180; $lat0 = 31.73439361 * $rad; $lon0 = 35.20451694 * $rad; $k0 = 1.0000067;
	$m = static function ( float $p ) use ( $a, $e2 ): float {
		$e4 = $e2 * $e2; $e6 = $e4 * $e2;
		return $a * ( ( 1 - $e2 / 4 - 3 * $e4 / 64 - 5 * $e6 / 256 ) * $p
			- ( 3 * $e2 / 8 + 3 * $e4 / 32 + 45 * $e6 / 1024 ) * sin( 2 * $p )
			+ ( 15 * $e4 / 256 + 45 * $e6 / 1024 ) * sin( 4 * $p )
			- ( 35 * $e6 / 3072 ) * sin( 6 * $p ) );
	};
	$phi = (float) $lat * $rad;
	$s = sin( $phi ); $c = cos( $phi ); $t = tan( $phi );
	$n  = $a / sqrt( 1 - $e2 * $s * $s );
	$tt = $t * $t; $cc = $ep2 * $c * $c; $aa = ( (float) $lng * $rad - $lon0 ) * $c;
	$x = $k0 * $n * ( $aa + ( 1 - $tt + $cc ) * $aa ** 3 / 6 + ( 5 - 18 * $tt + $tt * $tt + 72 * $cc - 58 * $ep2 ) * $aa ** 5 / 120 );
	$y = $k0 * ( $m( $phi ) - $m( $lat0 ) + $n * $t * ( $aa * $aa / 2 + ( 5 - $tt + 9 * $cc + 4 * $cc * $cc ) * $aa ** 4 / 24 + ( 61 - 58 * $tt + $tt * $tt + 600 * $cc - 330 * $ep2 ) * $aa ** 6 / 720 ) );
	return [ 219529.584 + $x, 626907.39 + $y ];
}

/** "180350 / 664120" for a project, or '' when it has no single location. */
function log_project_grid_ref( int $post_id ): string {
	if ( log_field( 'is_national', $post_id ) ) {
		return '';
	}
	$itm = log_itm( log_field( 'lat', $post_id ), log_field( 'lng', $post_id ) );
	return $itm ? sprintf( '%d / %d', round( $itm[0] / 10 ) * 10, round( $itm[1] / 10 ) * 10 ) : '';
}
