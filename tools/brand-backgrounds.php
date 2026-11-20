<?php
/**
 * Prints the brand book's downloadable grounds as JSON, for tools/brand-backgrounds.mjs: [ { slug, name, svg } ].
 * Runs the theme's own PHP without WordPress, so the few WordPress functions the two files call are stood in here.
 *
 *   npx @php-wasm/cli tools/brand-backgrounds.php <path to the theme>
 */

define( 'ABSPATH', '/' );
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function number_format_i18n( $n ) { return number_format( $n ); }

require $argv[1] . '/inc/city.php';
require $argv[1] . '/inc/brand-book.php';

$all = [];
foreach ( log_bk_backgrounds() as $slug => [ $name, $city ] ) {
	$all[] = [ 'slug' => $slug, 'name' => $name, 'svg' => log_city( $city ) ];
}
echo json_encode( $all );
