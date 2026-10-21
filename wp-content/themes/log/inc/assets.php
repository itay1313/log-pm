<?php
/**
 * Asset loading through the Vite manifest. In dev (`npm run dev`) assets come from
 * the Vite server when LOG_VITE_DEV is defined in wp-config.php.
 */

defined( 'ABSPATH' ) || exit;

function log_vite_manifest(): array {
	static $manifest = null;
	if ( $manifest === null ) {
		$file     = LOG_THEME_DIR . '/assets/dist/.vite/manifest.json';
		$manifest = file_exists( $file ) ? (array) json_decode( file_get_contents( $file ), true ) : [];
	}
	return $manifest;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( defined( 'LOG_VITE_DEV' ) && LOG_VITE_DEV ) {
		$dev = rtrim( LOG_VITE_DEV, '/' );
		wp_enqueue_script( 'vite-client', "$dev/@vite/client", [], null );
		wp_enqueue_script( 'log-main', "$dev/src/main.js", [], null );
		return;
	}

	$m = log_vite_manifest();
	$entry = $m['src/main.js'] ?? null;
	if ( ! $entry ) {
		return;
	}
	foreach ( $entry['css'] ?? [] as $i => $css ) {
		wp_enqueue_style( "log-style-$i", LOG_THEME_URI . '/assets/dist/' . $css, [], null );
	}
	wp_enqueue_script( 'log-main', LOG_THEME_URI . '/assets/dist/' . $entry['file'], [], null, [ 'strategy' => 'defer' ] );
} );

add_filter( 'script_tag_attributes', fn( $attrs ) => $attrs );
add_filter( 'wp_script_attributes', function ( array $attrs ) {
	if ( in_array( $attrs['id'] ?? '', [ 'log-main-js', 'vite-client-js' ], true ) ) {
		$attrs['type'] = 'module';
	}
	return $attrs;
} );

/**
 * Small inline config the JS reads: REST base, map token, measurement id.
 */
add_action( 'wp_head', function () {
	$config = [
		'rest'   => esc_url_raw( rest_url( 'log/v1/' ) ),
		'mapbox' => [
			'token' => function_exists( 'log_option' ) ? (string) log_option( 'mapbox_token', '' ) : '',
			'style' => function_exists( 'log_option' ) ? (string) log_option( 'mapbox_style', '' ) : '',
		],
		// GA4 measurement id; loaded only after cookie consent (consent.js).
		'ga'     => function_exists( 'log_option' ) ? (string) log_option( 'ga_id', '' ) : '',
	];
	echo '<script>window.LOG=' . wp_json_encode( $config ) . ';</script>' . "\n";
}, 5 );
