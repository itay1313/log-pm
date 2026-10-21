<?php
/**
 * Site search (spec v6 §16): projects (name, city, authority), knowledge items (title, excerpt, body)
 * and open jobs (title, team). Public content only. Hebrew-aware: niqqud, geresh and quotes are ignored,
 * a leading ה is optional, and a word matches inside longer words.
 * WordPress's own search only looks at titles and bodies, so results are computed here.
 */

defined( 'ABSPATH' ) || exit;

/** Lower-case, no niqqud, no geresh/gershayim or quotes, single spaces. Same rules as src/filter.js. */
function log_search_norm( string $s ): string {
	$s = mb_strtolower( wp_strip_all_tags( $s ) );
	$s = preg_replace( '/[\x{0591}-\x{05C7}]/u', '', $s );
	$s = preg_replace( '/["\'`\x{05F3}\x{05F4}]/u', '', $s );
	return trim( preg_replace( '/\s+/u', ' ', $s ) );
}

function log_search_matches( string $haystack, string $query ): bool {
	$h = log_search_norm( $haystack );
	foreach ( explode( ' ', log_search_norm( $query ) ) as $w ) {
		if ( $w === '' ) {
			continue;
		}
		if ( mb_strpos( $h, $w ) === false && ! ( mb_strlen( $w ) > 2 && mb_substr( $w, 0, 1 ) === 'ה' && mb_strpos( $h, mb_substr( $w, 1 ) ) !== false ) ) {
			return false;
		}
	}
	return true;
}

/**
 * @return array<string, array{label:string, items:array<int, array{type:string, title:string, line:string, url:string}>}>
 */
function log_search( string $query ): array {
	$query = trim( $query );
	$groups = [
		'projects'  => [ 'label' => 'פרויקטים', 'items' => [] ],
		'knowledge' => [ 'label' => 'מרכז ידע', 'items' => [] ],
		'jobs'      => [ 'label' => 'משרות פתוחות', 'items' => [] ],
	];
	if ( log_search_norm( $query ) === '' ) {
		return $groups;
	}

	foreach ( log_approved_projects_query()->posts as $p ) {
		if ( ! log_project_is_public( $p->ID ) ) {
			continue;
		}
		$auth  = wp_get_post_terms( $p->ID, 'log_authority', [ 'fields' => 'names' ] );
		$place = implode( ' · ', array_unique( array_filter( [ (string) log_field( 'city', $p->ID, '' ), $auth[0] ?? '' ] ) ) );
		if ( ! log_search_matches( $p->post_title . ' ' . $place, $query ) ) {
			continue;
		}
		$domain = wp_get_post_terms( $p->ID, 'log_domain', [ 'fields' => 'names' ] );
		$groups['projects']['items'][] = [
			'type'  => 'פרויקט',
			'title' => get_the_title( $p ),
			'line'  => implode( ' · ', array_filter( [ $domain[0] ?? '', $place, log_project_stage( $p->ID )['label'] ] ) ),
			'url'   => log_project_link( $p->ID ),
		];
	}

	$knowledge = new WP_Query( [ 'post_type' => 'log_knowledge', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true ] );
	foreach ( $knowledge->posts as $p ) {
		if ( ! log_search_matches( $p->post_title . ' ' . $p->post_excerpt . ' ' . $p->post_content . ' ' . log_field( 'outlet', $p->ID, '' ), $query ) ) {
			continue;
		}
		$type = wp_get_post_terms( $p->ID, 'log_knowledge_type', [ 'fields' => 'names' ] );
		$groups['knowledge']['items'][] = [
			'type'  => $type[0] ?? 'מרכז ידע',
			'title' => get_the_title( $p ),
			'line'  => wp_trim_words( get_the_excerpt( $p ), 20, '…' ),
			'url'   => get_permalink( $p ),
		];
	}

	$jobs = new WP_Query( [ 'post_type' => 'log_job', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true, 'meta_query' => [ [ 'key' => 'is_open', 'value' => '1' ] ] ] );
	foreach ( $jobs->posts as $p ) {
		$team = (string) log_field( 'team', $p->ID, '' );
		if ( ! log_search_matches( $p->post_title . ' ' . $team, $query ) ) {
			continue;
		}
		$groups['jobs']['items'][] = [
			'type'  => 'משרה',
			'title' => get_the_title( $p ),
			'line'  => $team,
			'url'   => get_permalink( $p ),
		];
	}

	return $groups;
}

/** The main query on /?s= is not used for results; keep it cheap and never 404. */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$q->set( 'post_type', 'log_knowledge' );
		$q->set( 'posts_per_page', 1 );
	}
}, 20 );

/**
 * The search address is /search?q=… (spec v6 §4). WordPress searches by `s`; map one onto the other.
 * The old /?s=… keeps working.
 */
add_action( 'init', function () {
	add_rewrite_rule( '^search/?$', 'index.php?log_search=1', 'top' );
} );
add_filter( 'query_vars', fn( array $vars ) => array_merge( $vars, [ 'log_search' ] ) );
add_filter( 'request', function ( array $vars ): array {
	if ( ! empty( $vars['log_search'] ) ) {
		unset( $vars['log_search'] );
		$q         = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$vars['s'] = $q !== '' ? $q : ' '; // a blank search still renders the search page
	}
	return $vars;
} );
/** Search pages stay out of Google. */
add_filter( 'wp_robots', fn( array $r ) => is_search() ? array_merge( $r, [ 'noindex' => true ] ) : $r );
