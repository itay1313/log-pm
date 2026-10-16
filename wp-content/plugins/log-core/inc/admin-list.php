<?php
/**
 * Projects list in wp-admin: publication approval at a glance, and in bulk.
 *
 * - Column "אישור": ✓ when the project is approved for the site and the map.
 * - Bulk actions: "אישור ופרסום" (approve + publish) and "ביטול אישור" (unapprove, keep the post).
 *   Approval is management's call (spec §15), so it's an explicit action, never automatic.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'manage_log_project_posts_columns', function ( array $cols ): array {
	$out = [];
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( $k === 'title' ) {
			$out['log_approved'] = 'אישור';
		}
	}
	return $out;
} );

add_action( 'manage_log_project_posts_custom_column', function ( string $col, int $id ) {
	if ( $col !== 'log_approved' ) {
		return;
	}
	echo log_field( 'approved', $id )
		? '<span style="color:#1e7e34;font-weight:600" aria-label="מאושר">✓</span>'
		: '<span style="color:#8c8f94" aria-label="לא מאושר">—</span>';
}, 10, 2 );

add_filter( 'bulk_actions-edit-log_project', function ( array $actions ): array {
	$actions['log_approve']   = 'אישור ופרסום';
	$actions['log_unapprove'] = 'ביטול אישור';
	return $actions;
} );

add_filter( 'handle_bulk_actions-edit-log_project', function ( string $redirect, string $action, array $ids ): string {
	if ( ! in_array( $action, [ 'log_approve', 'log_unapprove' ], true ) || ! current_user_can( 'publish_posts' ) ) {
		return $redirect;
	}
	$done = 0;
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( get_post_type( $id ) !== 'log_project' || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		if ( $action === 'log_approve' ) {
			log_set_field( 'approved', 1, $id );
			if ( get_post_status( $id ) !== 'publish' ) {
				wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
			}
		} else {
			log_set_field( 'approved', 0, $id );
		}
		$done++;
	}
	log_flush_map_cache();
	return add_query_arg( [ 'log_bulk' => $action, 'log_done' => $done ], $redirect );
}, 10, 3 );

add_action( 'admin_notices', function () {
	if ( empty( $_GET['log_bulk'] ) || get_current_screen()?->id !== 'edit-log_project' ) {
		return;
	}
	$n    = (int) ( $_GET['log_done'] ?? 0 );
	$text = $_GET['log_bulk'] === 'log_approve'
		? sprintf( '%d פרויקטים אושרו ופורסמו. הם מופיעים עכשיו באתר ובמפה.', $n )
		: sprintf( 'האישור בוטל ל-%d פרויקטים. הם יורדו מהמפה ומהאתר.', $n );
	printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $text ) );
} );
