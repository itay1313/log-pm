<?php
/**
 * The LOG logo, printed inline from assets/brand/logo.svg so its colours come from CSS
 * (--logo-a grey, --logo-b to --logo-b-deep orange) and src/lines/logo.js can move its parts.
 * The SVG is a stand-in drawn by tools/logo-construct.py until LOG sends the master file.
 */

defined( 'ABSPATH' ) || exit;

/** The wordmark's markup, read once per request, without its comment. */
function log_logo_source(): string {
	static $svg = null;
	if ( $svg === null ) {
		$file = LOG_THEME_DIR . '/assets/brand/logo.svg';
		$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
		$svg  = trim( preg_replace( '/<!--.*?-->\s*/s', '', $svg ) );
	}
	return $svg;
}

/**
 * The logo as inline SVG.
 *
 * @param array $args {
 *     @type string   $variant 'color' (grey and orange, on paper), 'night' (white and orange, on navy)
 *                             or 'mono' (one colour: currentColor).
 *     @type bool     $animate Let src/lines/logo.js assemble it on the first page load of a visit.
 *     @type string   $label   The accessible name. Empty: the logo is decoration (aria-hidden).
 *     @type bool     $tagline Set 'לוג - ניהול פרוייקטים בע"מ' under the mark. The book allows the
 *                             line only from 90px wide, so it is dropped below that (and hidden by
 *                             a container query when the width comes from CSS).
 *     @type int|null $width   Width in px. Default: the --logo-w of the context.
 * }
 */
function log_logo( array $args = [] ): string {
	static $count = 0;
	$args = array_merge( [
		'variant' => 'color',
		'animate' => false,
		'label'   => 'לוג ניהול פרויקטים',
		'tagline' => false,
		'width'   => null,
	], $args );

	$svg = log_logo_source();
	if ( $svg === '' || ! preg_match( '/<svg\b[^>]*\bviewBox="([^"]+)"[^>]*>/', $svg, $open ) ) {
		return '';
	}

	// Several logos share a page (header, menu, footer), so every id gets its own prefix.
	$uid = 'logo' . ( ++$count );
	$svg = str_replace( [ 'id="logo_', 'url(#logo_' ], [ 'id="' . $uid . '_', 'url(#' . $uid . '_' ], $svg );

	$label = trim( (string) $args['label'] );
	$a11y  = $label === '' ? 'aria-hidden="true"' : sprintf( 'role="img" aria-label="%s"', esc_attr( $label ) );
	$svg   = str_replace(
		$open[0],
		sprintf( '<svg class="logo__mark" xmlns="http://www.w3.org/2000/svg" viewBox="%s" %s focusable="false">', esc_attr( $open[1] ), $a11y ),
		$svg
	);

	$variant = in_array( $args['variant'], [ 'color', 'night', 'mono' ], true ) ? $args['variant'] : 'color';
	$width   = $args['width'] !== null ? max( 40, (int) $args['width'] ) : null; // 40px: the book's minimum.
	$tagline = $args['tagline'] && ( $width === null || $width >= 90 );

	$classes = [ 'logo', 'logo--' . $variant ];
	if ( $tagline ) {
		$classes[] = 'logo--tagline';
	}
	$attrs = sprintf( 'class="%s"', esc_attr( implode( ' ', $classes ) ) );
	if ( $width !== null ) {
		$attrs .= sprintf( ' style="--logo-w: %dpx"', $width );
	}
	if ( $args['animate'] ) {
		$attrs .= ' data-animate';
	}

	$line = $tagline ? '<span class="logo__tagline" aria-hidden="true">' . esc_html( 'לוג - ניהול פרוייקטים בע"מ' ) . '</span>' : '';

	return "<span $attrs>$svg$line</span>";
}

/**
 * The favicon: the O alone. A site icon set in the Customizer wins.
 */
add_action( 'wp_head', function () {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( LOG_THEME_URI . '/assets/brand/favicon.svg' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( LOG_THEME_URI . '/assets/brand/apple-touch-icon.png' ) );
}, 3 );
