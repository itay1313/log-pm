<?php
/**
 * Dev-only test page (mounted into Playground by tools/dev.sh as a mu-plugin; never deployed).
 * Tools → LOG Dev tests: run the Instagram importer on tools/fixtures/instagram-sample.json twice
 * and show created/updated/skipped, then the merged news feed.
 */

defined( 'ABSPATH' ) || exit;

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
			echo 'outbound probe: ' . ( is_wp_error( $probe ) ? $probe->get_error_message() : wp_remote_retrieve_response_code( $probe ) . ' ' . strlen( wp_remote_retrieve_body( $probe ) ) . ' bytes' ) . "\n";
			foreach ( log_news_items( 8 ) as $it ) {
				echo wp_date( 'Y-m-d', $it['date'] ) . ' | ' . $it['source'] . ' | ' . ( $it['title'] ?: $it['text'] ) . ' | img=' . ( $it['image'] ? 'yes' : 'no' ) . "\n";
			}
			echo '</pre>';
		}
		echo '<form method="post">';
		wp_nonce_field( 'log_dev' );
		submit_button( 'Run Instagram import on fixture (twice)', 'primary', 'run' );
		echo '</form></div>';
	} );
} );
