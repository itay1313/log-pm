<?php
/**
 * GET /wp-json/log/v1/map
 * GeoJSON FeatureCollection of approved projects, for the hero map and the projects map view.
 * Only public fields go out. Cached in a transient, cleared whenever a project is saved.
 *
 * GET /wp-json/log/v1/stats — the three map numbers (projects, units, sqm).
 */

defined( 'ABSPATH' ) || exit;

const LOG_MAP_TRANSIENT = 'log_map_geojson_v4';

add_action( 'rest_api_init', function () {
	register_rest_route( 'log/v1', '/map', [
		'methods'             => 'GET',
		'callback'            => 'log_rest_map',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'log/v1', '/stats', [
		'methods'             => 'GET',
		'callback'            => fn() => rest_ensure_response( log_site_stats() ),
		'permission_callback' => '__return_true',
	] );
} );

function log_rest_map(): WP_REST_Response {
	$data = get_transient( LOG_MAP_TRANSIENT );
	if ( $data === false ) {
		$data = log_build_map_geojson();
		set_transient( LOG_MAP_TRANSIENT, $data, DAY_IN_SECONDS );
	}
	$res = rest_ensure_response( $data );
	$res->header( 'Cache-Control', 'public, max-age=300' );
	return $res;
}

function log_approved_projects_query( array $extra = [] ): WP_Query {
	return new WP_Query( array_merge( [
		'post_type'      => 'log_project',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'meta_query'     => [ [ 'key' => 'approved', 'value' => '1' ] ],
	], $extra ) );
}

function log_build_map_geojson(): array {
	$features = [];
	$q        = log_approved_projects_query();

	foreach ( $q->posts as $p ) {
		$id = $p->ID;
		if ( ! log_project_is_public( $id ) ) { // stopped (03) / cancelled (99) never reach the map
			continue;
		}
		$lat      = log_field( 'lat', $id );
		$lng      = log_field( 'lng', $id );
		$national = (bool) log_field( 'is_national', $id );
		$page     = log_project_has_page( $id );
		$stage    = log_project_stage( $id );
		$domain   = log_first_term( $id, 'log_domain' );
		$auth     = log_first_term( $id, 'log_authority' );
		$num      = static fn( $v ) => ( $v === null || $v === '' ) ? null : (float) $v;

		$props = [
			'id'         => $id,
			'slug'       => $p->post_name, // URL-encoded, as it appears in /?p=
			'title'      => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ), // plain text: the JS escapes it, so &#8211; would show literally
			'page'       => $page,
			'url'        => $page ? get_permalink( $p ) : null,
			'city'       => log_field( 'city', $id, '' ),
			'authority'  => $auth ? rawurldecode( $auth->slug ) : null, // Hebrew slugs are stored encoded
			'authorityName' => $auth ? html_entity_decode( $auth->name, ENT_QUOTES, 'UTF-8' ) : null,
			'domain'     => $domain?->slug,
			'domainName' => $domain ? html_entity_decode( $domain->name, ENT_QUOTES, 'UTF-8' ) : null,
			'stage'      => $stage['slug'],
			'stageNumber' => $stage['number'],
			'stageLabel' => $stage['label'],
			'completed'  => $stage['completed'],
			'year'       => log_field( 'year', $id ),
			'units'      => $num( log_field( 'units', $id ) ),
			'sqm'        => $num( log_field( 'sqm_employment', $id ) ),
			'dunam'      => $num( log_field( 'dunam', $id ) ),
			'featured'   => (bool) log_field( 'featured', $id ),
			'national'   => $national,
			'image'      => get_the_post_thumbnail_url( $id, 'log-card' ) ?: null,
			'excerpt'    => log_short_text( $p ),
		];

		// National plans have no single location: they ride along with null geometry
		// so the "פריסה ארצית" list and the numbers still include them.
		$geometry = null;
		if ( ! $national && $lat !== null && $lng !== null ) {
			$geometry = [ 'type' => 'Point', 'coordinates' => [ (float) $lng, (float) $lat ] ];
		}

		$boundary = log_field( 'boundary', $id );
		if ( $boundary ) {
			$decoded = json_decode( $boundary, true );
			if ( is_array( $decoded ) && isset( $decoded['type'], $decoded['coordinates'] ) ) {
				$props['boundary'] = $decoded;
			}
		}

		$features[] = [
			'type'       => 'Feature',
			'id'         => $id,
			'geometry'   => $geometry,
			'properties' => $props,
		];
	}

	return [
		'type'      => 'FeatureCollection',
		'generated' => gmdate( 'c' ),
		'features'  => $features,
	];
}

/**
 * Short text for the hero card: the excerpt if written, else the challenge, else the solution. ~22 words.
 */
function log_short_text( WP_Post $p ): string {
	$text = has_excerpt( $p ) ? $p->post_excerpt : ( log_field( 'challenge', $p->ID ) ?: log_field( 'solution', $p->ID, '' ) );
	return html_entity_decode( wp_trim_words( wp_strip_all_tags( (string) $text ), 22, '…' ), ENT_QUOTES, 'UTF-8' );
}

function log_first_term( int $post_id, string $tax ): ?WP_Term {
	$terms = wp_get_post_terms( $post_id, $tax );
	return ( is_array( $terms ) && $terms ) ? $terms[0] : null;
}

function log_first_term_slug( int $post_id, string $tax ): ?string {
	return log_first_term( $post_id, $tax )?->slug;
}

/**
 * The three map numbers (spec v6 §8) for the whole group of public projects: projects, housing units,
 * total sqm. Each record counts once; national plans count too; a missing value adds nothing.
 * The map recomputes the same sums client-side as search and filters narrow the group.
 *
 * @return array{projects:int, units:int, sqm:int}
 */
function log_site_stats(): array {
	$cached = get_transient( 'log_site_stats_v2' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$stats = [ 'projects' => 0, 'units' => 0, 'sqm' => 0 ];
	foreach ( log_approved_projects_query( [ 'fields' => 'ids' ] )->posts as $id ) {
		if ( ! log_project_is_public( (int) $id ) ) {
			continue;
		}
		$stats['projects']++;
		$stats['units'] += (int) log_field( 'units', $id, 0 );
		$stats['sqm']   += (int) log_field( 'sqm_employment', $id, 0 );
	}
	set_transient( 'log_site_stats_v2', $stats, HOUR_IN_SECONDS );
	return $stats;
}

/**
 * Bust caches when relevant content or the settings change.
 */
function log_flush_map_cache(): void {
	delete_transient( LOG_MAP_TRANSIENT );
	delete_transient( 'log_site_stats_v2' );
}
add_action( 'save_post_log_project', 'log_flush_map_cache' );
add_action( 'deleted_post', 'log_flush_map_cache' );
add_action( 'edited_term', function ( $term_id, $tt_id, string $taxonomy ) {
	if ( in_array( $taxonomy, [ 'log_domain', 'log_authority', 'log_stage' ], true ) ) {
		log_flush_map_cache();
	}
}, 10, 3 );
add_action( 'acf/save_post', function ( $post_id ) {
	if ( $post_id === 'options' || get_post_type( $post_id ) === 'log_project' ) {
		log_flush_map_cache();
	}
}, 30 );
