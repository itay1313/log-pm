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

/**
 * Enqueue one Vite entry: its CSS and its JS as a module. From the dev server when LOG_VITE_DEV is set,
 * from the built manifest otherwise. $handle names the script (log-<handle>) and its styles (log-<handle>-style-N).
 */
function log_vite_entry( string $src, string $handle ): void {
	if ( defined( 'LOG_VITE_DEV' ) && LOG_VITE_DEV ) {
		$dev = rtrim( LOG_VITE_DEV, '/' );
		wp_enqueue_script( 'vite-client', "$dev/@vite/client", [], null );
		wp_enqueue_script( "log-$handle", "$dev/$src", [], null );
		log_vite_module( "log-$handle" );
		return;
	}

	$entry = log_vite_manifest()[ $src ] ?? null;
	if ( ! $entry ) {
		return;
	}
	foreach ( $entry['css'] ?? [] as $i => $css ) {
		wp_enqueue_style( "log-$handle-style-$i", LOG_THEME_URI . '/assets/dist/' . $css, [], null );
	}
	wp_enqueue_script( "log-$handle", LOG_THEME_URI . '/assets/dist/' . $entry['file'], [], null, [ 'strategy' => 'defer' ] );
	log_vite_module( "log-$handle" );
}

/** Script handles that load as ES modules. Called with a handle to add it, without to read the list. */
function log_vite_module( string $handle = '' ): array {
	static $handles = [ 'vite-client' ];
	if ( $handle !== '' && ! in_array( $handle, $handles, true ) ) {
		$handles[] = $handle;
	}
	return $handles;
}

/** The brand book at /brand is its own world: its own entry, none of the site's JS or CSS. */
function log_is_brand_book(): bool {
	return is_page( 'brand' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( log_is_brand_book() ) {
		log_vite_entry( 'src/brand/main.js', 'brand' );
	} else {
		log_vite_entry( 'src/main.js', 'main' );
	}
} );

/**
 * Preload the two faces the first screen sets: Ploni Light (the headline, whose reveal waits for its font)
 * and Ploni Tzar Medium (buttons, labels). Without this the browser finds them only after the CSS arrives.
 * The brand book's cover sets Ploni Tzar Bold over Ploni Regular instead.
 */
add_action( 'wp_head', function () {
	if ( defined( 'LOG_VITE_DEV' ) && LOG_VITE_DEV ) {
		return;
	}
	$m = log_vite_manifest();
	$fonts = log_is_brand_book()
		? [ 'assets/fonts/ploni_tzar_bold.woff2', 'assets/fonts/ploni_regular.woff2' ]
		: [ 'assets/fonts/ploni_light.woff2', 'assets/fonts/ploni_tzar_medium.woff2' ];
	foreach ( $fonts as $font ) {
		if ( ! empty( $m[ $font ]['file'] ) ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( LOG_THEME_URI . '/assets/dist/' . $m[ $font ]['file'] ) );
		}
	}
}, 2 );

add_filter( 'script_tag_attributes', fn( $attrs ) => $attrs );
add_filter( 'wp_script_attributes', function ( array $attrs ) {
	$modules = array_map( fn( $h ) => "$h-js", log_vite_module() );
	if ( in_array( $attrs['id'] ?? '', $modules, true ) ) {
		$attrs['type'] = 'module';
	}
	return $attrs;
} );

/**
 * Small inline config the JS reads: REST base, map token, measurement id.
 */
add_action( 'wp_head', function () {
	if ( log_is_brand_book() ) {
		return;
	}
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
