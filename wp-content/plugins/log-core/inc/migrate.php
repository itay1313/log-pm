<?php
/**
 * One-off data migrations, run once per site on the first request after a deploy.
 * Each step is idempotent; the stored version only moves forward.
 *
 * 2 (spec v6):
 * - seed the new terms (stages, units, knowledge types);
 * - log_city → log_authority (the ArcGIS רשות_מקומית value), then drop log_city and log_scale terms;
 * - stage term for every project;
 * - knowledge types renamed/merged to the v6 list;
 * - team members' old "group" (owner/management) → the "הנהלה" unit.
 */

defined( 'ABSPATH' ) || exit;

const LOG_SCHEMA_VERSION = 2;

add_action( 'init', function () {
	if ( (int) get_option( 'log_schema_version', 1 ) >= LOG_SCHEMA_VERSION || wp_installing() ) {
		return;
	}
	// One run at a time; a concurrent request just skips.
	if ( get_transient( 'log_migrating' ) ) {
		return;
	}
	set_transient( 'log_migrating', 1, 5 * MINUTE_IN_SECONDS );
	log_migrate_v2();
	update_option( 'log_schema_version', LOG_SCHEMA_VERSION, true );
	delete_transient( 'log_migrating' );
	if ( function_exists( 'log_flush_map_cache' ) ) {
		log_flush_map_cache();
	}
}, 20 ); // after the taxonomies are registered

function log_migrate_v2(): void {
	global $wpdb;

	log_seed_terms();

	// Retired taxonomies aren't registered any more; register them silently to read and delete their terms.
	foreach ( [ 'log_city', 'log_scale' ] as $old ) {
		if ( ! taxonomy_exists( $old ) ) {
			register_taxonomy( $old, [ 'log_project' ], [ 'public' => false, 'rewrite' => false ] );
		}
	}

	$projects = get_posts( [
		'post_type'      => 'log_project',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );
	foreach ( $projects as $id ) {
		$id     = (int) $id;
		$cities = wp_get_object_terms( $id, 'log_city', [ 'fields' => 'names' ] );
		$name   = ( is_array( $cities ) && $cities ) ? $cities[0] : (string) log_field( 'city', $id, '' );
		if ( $name !== '' && ! has_term( '', 'log_authority', $id ) ) {
			wp_set_object_terms( $id, $name, 'log_authority', false );
		}
		log_project_recompute_derived( $id );
	}

	foreach ( [ 'log_city', 'log_scale' ] as $old ) {
		$terms = get_terms( [ 'taxonomy' => $old, 'hide_empty' => false, 'fields' => 'ids' ] );
		foreach ( is_array( $terms ) ? $terms : [] as $tid ) {
			wp_delete_term( (int) $tid, $old );
		}
	}

	// Knowledge types: press → news (renamed), behind-scenes merged into news, labels per v6.
	$labels = log_knowledge_type_terms();
	$press  = get_term_by( 'slug', 'press', 'log_knowledge_type' );
	$news   = get_term_by( 'slug', 'news', 'log_knowledge_type' );
	if ( $press && ! $news ) {
		wp_update_term( $press->term_id, 'log_knowledge_type', [ 'slug' => 'news', 'name' => $labels['news'] ] );
	} elseif ( $press && $news ) {
		log_migrate_merge_term( $press, $news );
	}
	$behind = get_term_by( 'slug', 'behind-scenes', 'log_knowledge_type' );
	$news   = get_term_by( 'slug', 'news', 'log_knowledge_type' );
	if ( $behind && $news ) {
		log_migrate_merge_term( $behind, $news );
	}
	foreach ( $labels as $slug => $name ) {
		$t = get_term_by( 'slug', $slug, 'log_knowledge_type' );
		if ( $t && $t->name !== $name ) {
			wp_update_term( $t->term_id, 'log_knowledge_type', [ 'name' => $name ] );
		}
	}

	// Team: owners and management → "הנהלה". Everyone else waits for LOG's unit list.
	$management = get_term_by( 'slug', 'management', 'log_unit' );
	if ( $management ) {
		$people = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'group' AND meta_value IN ('owner','management')" );
		foreach ( $people as $pid ) {
			if ( ! has_term( '', 'log_unit', (int) $pid ) ) {
				wp_set_object_terms( (int) $pid, $management->term_id, 'log_unit', false );
			}
		}
	}
}

/** Move every object from one term to another, then delete the first. */
function log_migrate_merge_term( WP_Term $from, WP_Term $to ): void {
	$objects = get_objects_in_term( $from->term_id, $from->taxonomy );
	foreach ( is_array( $objects ) ? $objects : [] as $oid ) {
		wp_set_object_terms( (int) $oid, $to->term_id, $to->taxonomy, true );
	}
	wp_delete_term( $from->term_id, $from->taxonomy );
}
