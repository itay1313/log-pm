<?php
/**
 * "מה חדש" (spec §6.9, §15): social posts as a private CPT, merged with knowledge items by date.
 *
 * - Instagram: synced hourly from the Instagram API (token from settings). Long-lived tokens are
 *   refreshed automatically when they're older than 50 days.
 * - LinkedIn: manual entry at launch (spec §20). The record shape is identical, so an aggregator
 *   can fill it later without touching templates.
 */

defined( 'ABSPATH' ) || exit;

const LOG_IG_API = 'https://graph.instagram.com';

add_action( 'init', function () {
	register_post_type( 'log_post', [
		'labels' => [
			'name'          => 'פוסטים ברשתות',
			'singular_name' => 'פוסט',
			'add_new'       => 'פוסט חדש',
			'add_new_item'  => 'הוספת פוסט',
			'edit_item'     => 'עריכת פוסט',
			'all_items'     => 'כל הפוסטים',
			'not_found'     => 'אין פוסטים',
			'featured_image' => 'תמונת הפוסט',
			'set_featured_image' => 'בחירת תמונה',
		],
		'public'              => false,
		'show_ui'             => true,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'menu_position'       => 10,
		'menu_icon'           => 'dashicons-share',
		'supports'            => [ 'title', 'thumbnail' ],
		'show_in_rest'        => false,
	] );
} );

add_filter( 'use_block_editor_for_post_type', fn( bool $use, string $type ) => $type === 'log_post' ? false : $use, 10, 2 );

/* ---------- Merged feed ---------- */

/**
 * The latest items for the "what's new" grid: knowledge items + social posts, newest first.
 * Each item: ['source' => knowledge|instagram|linkedin, 'title', 'text', 'url', 'image', 'date' (timestamp), 'external' => bool]
 */
function log_news_items( int $limit = 8 ): array {
	$cached = get_transient( 'log_news_v1' );
	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}
	$items = [];

	$knowledge = new WP_Query( [ 'post_type' => 'log_knowledge', 'posts_per_page' => $limit, 'no_found_rows' => true ] );
	foreach ( $knowledge->posts as $p ) {
		$type = get_the_terms( $p, 'log_knowledge_type' );
		$items[] = [
			'source'   => 'knowledge',
			'label'    => $type && ! is_wp_error( $type ) ? $type[0]->name : 'מרכז ידע',
			'title'    => get_the_title( $p ),
			'text'     => get_the_excerpt( $p ),
			'url'      => get_permalink( $p ),
			'image'    => get_the_post_thumbnail_url( $p, 'log-card' ) ?: null,
			'date'     => get_post_time( 'U', true, $p ),
			'external' => false,
		];
	}

	$social = new WP_Query( [ 'post_type' => 'log_post', 'post_status' => 'publish', 'posts_per_page' => $limit, 'no_found_rows' => true, 'meta_key' => 'posted_at', 'orderby' => 'meta_value', 'order' => 'DESC' ] );
	foreach ( $social->posts as $p ) {
		$source = log_field( 'source', $p->ID, 'instagram' );
		$posted = log_field( 'posted_at', $p->ID );
		$items[] = [
			'source'   => $source,
			'label'    => $source === 'linkedin' ? 'LinkedIn' : 'Instagram',
			'title'    => '',
			'text'     => wp_trim_words( (string) log_field( 'text', $p->ID, get_the_title( $p ) ), 24, '…' ),
			'url'      => log_field( 'url', $p->ID, '' ),
			'image'    => get_the_post_thumbnail_url( $p, 'log-card' ) ?: null,
			'date'     => $posted ? strtotime( $posted ) : get_post_time( 'U', true, $p ),
			'external' => true,
		];
	}

	usort( $items, static fn( $a, $b ) => $b['date'] <=> $a['date'] );
	set_transient( 'log_news_v1', $items, HOUR_IN_SECONDS );
	return array_slice( $items, 0, $limit );
}
foreach ( [ 'save_post_log_post', 'save_post_log_knowledge', 'deleted_post' ] as $hook ) {
	add_action( $hook, fn() => delete_transient( 'log_news_v1' ) );
}

/* ---------- Instagram sync ---------- */

add_filter( 'cron_schedules', function ( array $s ): array {
	$s['log_hourly'] = [ 'interval' => HOUR_IN_SECONDS, 'display' => 'LOG · every hour' ];
	return $s;
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'log_instagram_sync' ) ) {
		wp_schedule_event( time() + 300, 'log_hourly', 'log_instagram_sync' );
	}
} );
add_action( 'log_instagram_sync', 'log_instagram_sync' );

/**
 * Pull the latest media and upsert. Returns a human-readable status line and stores it for the admin.
 */
function log_instagram_sync( int $limit = 12 ): string {
	$token = trim( (string) log_option( 'instagram_token', '' ) );
	if ( $token === '' ) {
		return log_instagram_status( 'אין טוקן. הזינו טוקן בהגדרות לוג → רשתות.' );
	}

	log_instagram_maybe_refresh( $token );

	$url = add_query_arg( [
		'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
		'limit'        => $limit,
		'access_token' => $token,
	], LOG_IG_API . '/me/media' );
	$res = wp_remote_get( $url, [ 'timeout' => 20 ] );
	if ( is_wp_error( $res ) ) {
		return log_instagram_status( 'שגיאת רשת: ' . $res->get_error_message() );
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $body ) || empty( $body['data'] ) ) {
		$msg = $body['error']['message'] ?? 'תשובה ריקה מאינסטגרם';
		return log_instagram_status( "שגיאה: $msg" );
	}

	$r = log_instagram_import_items( $body['data'] );
	return log_instagram_status( sprintf( 'סונכרן: %d חדשים, %d עודכנו, %d דולגו.', $r['created'], $r['updated'], $r['skipped'] ) );
}

/**
 * Upsert an array of Instagram media objects (the API's `data` array). Testable without the network.
 */
function log_instagram_import_items( array $items ): array {
	$r = [ 'created' => 0, 'updated' => 0, 'skipped' => 0 ];
	foreach ( $items as $m ) {
		if ( empty( $m['id'] ) || empty( $m['permalink'] ) ) {
			$r['skipped']++;
			continue;
		}
		$image = $m['media_type'] === 'VIDEO' ? ( $m['thumbnail_url'] ?? null ) : ( $m['media_url'] ?? null );
		if ( ! $image ) {
			$r['skipped']++;
			continue;
		}
		$existing = get_posts( [ 'post_type' => 'log_post', 'post_status' => 'any', 'meta_key' => 'external_id', 'meta_value' => 'ig:' . $m['id'], 'fields' => 'ids', 'posts_per_page' => 1 ] );
		$caption  = trim( (string) ( $m['caption'] ?? '' ) );
		$title    = wp_trim_words( $caption ?: 'פוסט באינסטגרם', 8, '…' );
		$posted   = gmdate( 'Y-m-d H:i:s', strtotime( $m['timestamp'] ?? 'now' ) );

		if ( $existing ) {
			$id = $existing[0];
			log_set_field( 'text', $caption, $id );
			log_set_field( 'posted_at', $posted, $id );
			$r['updated']++;
			continue;
		}
		$id = wp_insert_post( [ 'post_type' => 'log_post', 'post_status' => 'publish', 'post_title' => $title, 'post_date' => get_date_from_gmt( $posted ) ] );
		if ( ! $id || is_wp_error( $id ) ) {
			$r['skipped']++;
			continue;
		}
		log_set_field( 'source', 'instagram', $id );
		log_set_field( 'url', esc_url_raw( $m['permalink'] ), $id );
		log_set_field( 'text', $caption, $id );
		log_set_field( 'posted_at', $posted, $id );
		log_set_field( 'external_id', 'ig:' . $m['id'], $id );
		log_sideload_thumbnail( $image, $id, $title );
		$r['created']++;
	}
	delete_transient( 'log_news_v1' );
	return $r;
}

function log_sideload_thumbnail( string $url, int $post_id, string $alt ): void {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_sideload_image( $url, $post_id, $alt, 'id' );
	if ( is_wp_error( $att ) ) {
		update_option( 'log_instagram_last_media_error', $att->get_error_message(), false );
		return;
	}
	set_post_thumbnail( $post_id, (int) $att );
}

/**
 * Long-lived Instagram tokens expire after 60 days; refresh when ours is 50+ days old.
 */
function log_instagram_maybe_refresh( string $token ): void {
	$since = (int) get_option( 'log_instagram_token_time', 0 );
	if ( $since && time() - $since < 50 * DAY_IN_SECONDS ) {
		return;
	}
	$res = wp_remote_get( add_query_arg( [ 'grant_type' => 'ig_refresh_token', 'access_token' => $token ], LOG_IG_API . '/refresh_access_token' ), [ 'timeout' => 20 ] );
	$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! empty( $body['access_token'] ) ) {
		log_set_field( 'instagram_token', $body['access_token'], 'option' );
	}
	update_option( 'log_instagram_token_time', time(), false );
}

function log_instagram_status( string $msg ): string {
	update_option( 'log_instagram_status', [ 'time' => time(), 'message' => $msg ], false );
	return $msg;
}

/* ---------- Admin: status + sync now ---------- */

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'log_post' ) {
		return;
	}
	if ( isset( $_GET['log_ig_sync'] ) && check_admin_referer( 'log_ig_sync' ) ) {
		$msg = log_instagram_sync();
		printf( '<div class="notice notice-info"><p>%s</p></div>', esc_html( $msg ) );
	}
	$status = get_option( 'log_instagram_status' );
	$line   = $status ? sprintf( 'סנכרון אינסטגרם אחרון: %s · %s', wp_date( 'd/m/Y H:i', $status['time'] ), $status['message'] ) : 'אינסטגרם עדיין לא סונכרן.';
	$url    = wp_nonce_url( add_query_arg( 'log_ig_sync', 1, admin_url( 'edit.php?post_type=log_post' ) ), 'log_ig_sync' );
	printf( '<div class="notice notice-info"><p>%s <a class="button button-small" href="%s">סנכרון עכשיו</a></p></div>', esc_html( $line ), esc_url( $url ) );
} );

add_filter( 'manage_log_post_posts_columns', fn( array $c ) => [ 'cb' => $c['cb'], 'title' => 'פוסט', 'source' => 'מקור', 'posted' => 'פורסם', 'date' => 'נוסף' ] );
add_action( 'manage_log_post_posts_custom_column', function ( string $col, int $id ) {
	if ( $col === 'source' ) {
		echo esc_html( log_field( 'source', $id, 'instagram' ) === 'linkedin' ? 'LinkedIn' : 'Instagram' );
	} elseif ( $col === 'posted' ) {
		$p = log_field( 'posted_at', $id );
		echo esc_html( $p ? wp_date( 'd/m/Y', strtotime( $p ) ) : '—' );
	}
}, 10, 2 );
