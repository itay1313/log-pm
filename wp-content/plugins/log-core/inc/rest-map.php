<?php
/**
 * GET /wp-json/log/v1/map
 * GeoJSON FeatureCollection of approved projects, for the hero map and the projects map view.
 * Only public fields go out. Cached in a transient, cleared whenever a project is saved.
 *
 * GET /wp-json/log/v1/stats — the site numbers (units, projects, authorities, team).
 */

defined( 'ABSPATH' ) || exit;

const LOG_MAP_TRANSIENT = 'log_map_geojson_v1';

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
		$id  = $p->ID;
		$lat = log_field( 'lat', $id );
		$lng = log_field( 'lng', $id );
		$national = (bool) log_field( 'is_national', $id );

		$props = [
			'id'       => $id,
			'title'    => get_the_title( $p ),
			'url'      => get_permalink( $p ),
			'city'     => log_field( 'city', $id, '' ),
			'stage'    => (int) log_field( 'stage', $id, 1 ),
			'year'     => log_field( 'year', $id ),
			'units'    => log_field( 'units', $id ),
			'sqm'      => log_field( 'sqm_employment', $id ),
			'dunam'    => log_field( 'dunam', $id ),
			'scale'    => log_first_term_slug( $id, 'log_scale' ),
			'domain'   => log_first_term_slug( $id, 'log_domain' ),
			'domains'  => wp_get_post_terms( $id, 'log_domain', [ 'fields' => 'slugs' ] ),
			'featured' => (bool) log_field( 'featured', $id ),
			'control'  => (bool) log_field( 'is_control', $id ),
			'national' => $national,
			'image'    => get_the_post_thumbnail_url( $id, 'medium' ) ?: null,
		];

		// National plans have no single location: they ride along with null geometry
		// so the list view can still show them.
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

function log_first_term_slug( int $post_id, string $tax ): ?string {
	$terms = wp_get_post_terms( $post_id, $tax, [ 'fields' => 'slugs' ] );
	return ( is_array( $terms ) && $terms ) ? $terms[0] : null;
}

/**
 * Site-wide numbers for the hero counters. Computed from approved projects,
 * each overridable from the settings page.
 */
function log_site_stats(): array {
	$cached = get_transient( 'log_site_stats_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$units = 0;
	$active = 0;
	foreach ( log_approved_projects_query( [ 'fields' => 'ids' ] )->posts as $id ) {
		$units += (int) log_field( 'units', $id, 0 );
		if ( (int) log_field( 'stage', $id, 1 ) < 4 ) {
			$active++;
		}
	}
	$team = wp_count_posts( 'log_person' )->publish ?? 0;

	$stats = [
		'units'       => (int) ( log_option( 'units_override' ) ?: $units ),
		'projects'    => (int) ( log_option( 'projects_override' ) ?: $active ),
		'authorities' => (int) log_option( 'authorities_count', 0 ),
		'team'        => (int) ( log_option( 'team_override' ) ?: $team ),
	];
	set_transient( 'log_site_stats_v1', $stats, HOUR_IN_SECONDS );
	return $stats;
}

/**
 * Bust caches when relevant content or the settings change.
 */
function log_flush_map_cache(): void {
	delete_transient( LOG_MAP_TRANSIENT );
	delete_transient( 'log_site_stats_v1' );
}
add_action( 'save_post_log_project', 'log_flush_map_cache' );
add_action( 'save_post_log_person', 'log_flush_map_cache' );
add_action( 'deleted_post', 'log_flush_map_cache' );
add_action( 'acf/save_post', function ( $post_id ) {
	if ( $post_id === 'options' || get_post_type( $post_id ) === 'log_project' ) {
		log_flush_map_cache();
	}
}, 30 );
