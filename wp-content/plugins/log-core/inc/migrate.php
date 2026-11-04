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
 *
 * 3: the cookie policy page, and new rewrite rules (/search?q=).
 *
 * 4: client logos from the old site (assets/clients), attached to the matching client
 *    (created if missing) and marked approved, since they were already public there.
 */

defined( 'ABSPATH' ) || exit;

const LOG_SCHEMA_VERSION = 4;

add_action( 'init', function () {
	if ( (int) get_option( 'log_schema_version', 1 ) >= LOG_SCHEMA_VERSION || wp_installing() ) {
		return;
	}
	// One run at a time; a concurrent request just skips.
	if ( get_transient( 'log_migrating' ) ) {
		return;
	}
	set_transient( 'log_migrating', 1, 5 * MINUTE_IN_SECONDS );
	$from = (int) get_option( 'log_schema_version', 1 );
	if ( $from < 2 ) {
		log_migrate_v2();
	}
	if ( $from < 3 ) {
		log_migrate_v3();
	}
	if ( $from < 4 ) {
		log_migrate_v4();
	}
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

function log_migrate_v3(): void {
	if ( ! get_page_by_path( 'cookies' ) ) {
		wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'מדיניות עוגיות', 'post_name' => 'cookies' ] );
	}
	flush_rewrite_rules( false );
}

/**
 * The 13 logos the old site showed under "מבין לקוחותינו". Each row: file, body type, then the
 * titles it may already have in the admin (the first is used when the client is created).
 */
function log_client_logos(): array {
	return [
		[ 'housing-ministry', 'government', 'משרד הבינוי והשיכון' ],
		[ 'israel-land-authority', 'government', 'רשות מקרקעי ישראל' ],
		[ 'planning-administration', 'government', 'מנהל התכנון', 'מינהל התכנון' ],
		[ 'urban-renewal-authority', 'government', 'הרשות הממשלתית להתחדשות עירונית', 'הרשות להתחדשות עירונית' ],
		[ 'holon', 'authority', 'עיריית חולון', 'חולון' ],
		[ 'hadera', 'authority', 'עיריית חדרה', 'חדרה' ],
		[ 'beit-shemesh', 'authority', 'עיריית בית שמש', 'בית שמש' ],
		[ 'tidhar', 'developer', 'תדהר' ],
		[ 'levinski-ofer', 'developer', 'לוינסקי-עופר', 'לוינסקי עופר' ],
		[ 'tzarfati', 'developer', 'צרפתי', 'צרפתי שמעון' ],
		[ 'aura', 'developer', 'אאורה', 'AURA' ],
		[ 'yuvalim', 'developer', 'קבוצת יובלים', 'יובלים' ],
		[ 'acro', 'developer', 'אקרו נדל"ן', 'אקרו' ],
	];
}

function log_migrate_v4(): void {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ( log_client_logos() as $order => $row ) {
		[ $file, $type ] = $row;
		$titles = array_slice( $row, 2 );
		$path   = LOG_CORE_DIR . "assets/clients/$file.png";
		if ( ! is_readable( $path ) ) {
			continue;
		}

		$id = 0;
		foreach ( $titles as $title ) {
			$found = get_posts( [
				'post_type'      => 'log_client',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'title'          => $title,
			] );
			if ( $found ) {
				$id = (int) $found[0];
				break;
			}
		}
		if ( ! $id ) {
			$id = wp_insert_post( [
				'post_type'   => 'log_client',
				'post_status' => 'publish',
				'post_title'  => $titles[0],
				'menu_order'  => $order,
			], true );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			log_set_field( 'body_type', $type, $id );
		} elseif ( ! log_field( 'body_type', $id ) ) {
			log_set_field( 'body_type', $type, $id );
		}

		if ( ! has_post_thumbnail( $id ) ) {
			// media_handle_sideload moves the file, so hand it a copy.
			$tmp = wp_tempnam( "$file.png" );
			if ( $tmp && copy( $path, $tmp ) ) {
				$att = media_handle_sideload( [ 'name' => "log-client-$file.png", 'tmp_name' => $tmp ], $id, get_the_title( $id ) );
				if ( is_wp_error( $att ) ) {
					@unlink( $tmp );
				} else {
					set_post_thumbnail( $id, $att );
					update_post_meta( $att, '_wp_attachment_image_alt', get_the_title( $id ) );
				}
			}
		}
		log_set_field( 'logo_approved', 1, $id );
	}
}
