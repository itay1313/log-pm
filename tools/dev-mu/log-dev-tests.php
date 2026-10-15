<?php
/**
 * Dev-only test page (mounted into Playground by tools/dev.sh as a mu-plugin; never deployed).
 * Tools → LOG Dev tests: run the Instagram importer on tools/fixtures/instagram-sample.json twice
 * and show created/updated/skipped, then the merged news feed.
 */

defined( 'ABSPATH' ) || exit;

// Local only: show PHP errors instead of WordPress's generic "critical error" page.
if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) { define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ); }
@ini_set( 'display_errors', '1' );
error_reporting( E_ALL & ~E_DEPRECATED );

add_action( 'admin_menu', function () {
	add_management_page( 'LOG Dev tests', 'LOG Dev tests', 'manage_options', 'log-dev-tests', function () {
		echo '<div class="wrap"><h1>LOG Dev tests</h1>';
		if ( isset( $_POST['run'] ) && check_admin_referer( 'log_dev' ) ) {
			$fixture = json_decode( file_get_contents( WP_CONTENT_DIR . '/mu-plugins/instagram-sample.json' ), true );
			$first   = log_instagram_import_items( $fixture['data'] );
			$second  = log_instagram_import_items( $fixture['data'] );
			echo '<pre id="result">first: ' . wp_json_encode( $first ) . "\nsecond: " . wp_json_encode( $second ) . "\n";
			echo 'last media error: ' . esc_html( (string) get_option( 'log_instagram_last_media_error', '' ) ) . "\n";
			$probe = wp_remote_get( 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Hadera_aerial_view.jpg/640px-Hadera_aerial_view.jpg', [ 'timeout' => 15 ] );
			$probe2 = wp_remote_get( 'https://picsum.photos/seed/log/640/480.jpg', [ 'timeout' => 15 ] );
			echo 'probe picsum: ' . ( is_wp_error( $probe2 ) ? $probe2->get_error_message() : wp_remote_retrieve_response_code( $probe2 ) . ' ' . wp_remote_retrieve_header( $probe2, 'content-type' ) ) . "\n";
			echo 'wikimedia body: ' . esc_html( substr( strip_tags( (string) wp_remote_retrieve_body( $probe ) ), 0, 160 ) ) . "\n";
			echo 'outbound probe: ' . ( is_wp_error( $probe ) ? $probe->get_error_message() : wp_remote_retrieve_response_code( $probe ) . ' ' . strlen( wp_remote_retrieve_body( $probe ) ) . ' bytes' ) . "\n";
			foreach ( log_news_items( 8 ) as $it ) {
				echo wp_date( 'Y-m-d', $it['date'] ) . ' | ' . $it['source'] . ' | ' . ( $it['title'] ?: $it['text'] ) . ' | img=' . ( $it['image'] ? 'yes' : 'no' ) . "\n";
			}
			echo '</pre>';
		}
		if ( isset( $_POST['arcgis'] ) && check_admin_referer( 'log_dev' ) ) {
			$file = WP_CONTENT_DIR . '/mu-plugins/blue_lines_main.geojson';
			if ( ! file_exists( $file ) ) {
				echo '<pre id="arcgis-result">no private fixture mounted (tools/fixtures/private/blue_lines_main.geojson)</pre>';
			} else {
				$res = log_import_run( json_decode( file_get_contents( $file ), true ), false );
				// Local only: publish and approve everything that has a location, so the hero has data.
				$ids = get_posts( [ 'post_type' => 'log_project', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );
				$approved = 0;
				foreach ( $ids as $id ) {
					if ( ! log_field( 'lat', $id ) ) { continue; }
					wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
					log_set_field( 'approved', 1, $id );
					$approved++;
				}
				log_flush_map_cache();
				echo '<pre id="arcgis-result">' . esc_html( wp_json_encode( [ 'summary' => array_diff_key( $res, [ 'rows' => 1 ] ), 'approved' => $approved ], JSON_UNESCAPED_UNICODE ) ) . '</pre>';
			}
		}
		echo '<form method="post">';
		wp_nonce_field( 'log_dev' );
		submit_button( 'Run Instagram import on fixture (twice)', 'primary', 'run' );
		submit_button( 'Import private ArcGIS GeoJSON + approve all (local only)', 'secondary', 'arcgis' );
		echo '</form></div>';
	} );
} );
