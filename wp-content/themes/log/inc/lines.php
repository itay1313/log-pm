<?php
/**
 * The thin line: the brand book's line system (chapter 03), as markup the site and the book share.
 *
 * Every line and every circle has one weight (--line-w). Each kind marks one thing, and if you can't say what
 * a line marks, it doesn't go on the page:
 *   solid   what exists or was approved: page structure, axes, a stage that's done
 *   dashed  what doesn't exist yet: a plan not built, a reference line such as an average
 *   switch  grey until the point where we come in, orange from there; once per line, and only when it means something
 *   dot     ends in a filled orange dot, no ring: points at the thing that matters
 *   cross   two lines cut through each other (they never round off), the dot where they cross
 * A circle is a line that closes:
 *   full    a count: how many dates, how many projects
 *   dashed  what is planned and hasn't happened; never around a person
 *   split   a person: four quarters, orange and grey in turn, cut where the logo's O is cut
 *   halves  a person in black only: two halves, cut at the top and the bottom
 *
 * Lines are decoration for the eye only (aria-hidden); whatever they mean is also said in words.
 * src/lines/draw.js draws them in as they come into view (data-draw); without motion they are simply there.
 */

defined( 'ABSPATH' ) || exit;

/**
 * A line. Horizontal by default; it fills its container's width (or height with axis "y").
 *
 * @param string $kind solid | dashed | switch | dot | cross
 * @param array  $o {
 *     @type string    $axis  "x" (default) or "y".
 *     @type int|float $at    Where along the line the switch or the crossing happens, 0–100 from the start. Default 50.
 *     @type string    $draw  "in" (once, as it comes into view; default), "scrub" (tied to the scroll) or "" (never).
 *     @type string    $class Extra classes.
 * }
 */
function log_line( string $kind = 'solid', array $o = [] ): string {
	$kind = in_array( $kind, [ 'solid', 'dashed', 'switch', 'dot', 'cross' ], true ) ? $kind : 'solid';
	$o    = wp_parse_args( $o, [ 'axis' => 'x', 'at' => 50, 'draw' => 'in', 'class' => '' ] );
	$at   = max( 0, min( 100, (float) $o['at'] ) );

	$parts = '';
	if ( $kind === 'cross' ) {
		$parts = '<i class="ln__x"></i><i class="ln__dot"></i>';
	} elseif ( $kind === 'dot' ) {
		$parts = '<i class="ln__dot"></i>';
	}

	return sprintf(
		'<span class="%1$s" style="--at:%2$s%%"%3$s aria-hidden="true">%4$s</span>',
		esc_attr( trim( "ln ln--$kind" . ( $o['axis'] === 'y' ? ' ln--y' : '' ) . ' ' . $o['class'] ) ),
		esc_attr( (string) $at ),
		$o['draw'] ? ' data-draw="' . esc_attr( $o['draw'] ) . '"' : '',
		$parts
	);
}

/** The dot on its own: 12px, filled, orange (or white on a dark photo, with $class "dot--white"). No ring, ever. */
function log_dot( string $class = '' ): string {
	return '<i class="' . esc_attr( trim( 'dot ' . $class ) ) . '" aria-hidden="true"></i>';
}

/**
 * A circle: a thin line that closes. Its stroke keeps the line's weight at any size.
 * $inner is trusted HTML set in the middle: a number (wrap it so it reads, e.g. "7 מועדים"), or an <img> of a person.
 *
 * @param string $kind  full | dashed | split | halves
 * @param string $inner Escaped HTML for the middle.
 * @param array  $o { @type string $class Extra classes. @type string $draw "in" (default) or "". }
 */
function log_circle( string $kind = 'full', string $inner = '', array $o = [] ): string {
	static $n = 0;
	$kind = in_array( $kind, [ 'full', 'dashed', 'split', 'halves' ], true ) ? $kind : 'full';
	$o    = wp_parse_args( $o, [ 'class' => '', 'draw' => 'in' ] );

	// Arcs on r=49 in a 100 box, angles clockwise from 12 o'clock. The cuts sit where the logo's O is cut.
	$arc = static function ( float $from, float $to, string $class = '' ): string {
		$pt = static fn( float $deg ) => sprintf( '%.3f %.3f', 50 + 49 * sin( deg2rad( $deg ) ), 50 - 49 * cos( deg2rad( $deg ) ) );
		return sprintf( '<path class="%s" d="M%s A49 49 0 0 1 %s"/>', esc_attr( $class ), $pt( $from ), $pt( $to ) );
	};
	$gap = 11; // half the cut, in degrees

	switch ( $kind ) {
		case 'split': // orange at top right and bottom left, grey between, as in the logo's O
			$paths = $arc( $gap, 90 - $gap, 'circ__on' ) . $arc( 90 + $gap, 180 - $gap, 'circ__off' )
				. $arc( 180 + $gap, 270 - $gap, 'circ__on' ) . $arc( 270 + $gap, 360 - $gap, 'circ__off' );
			break;
		case 'halves':
			$paths = $arc( $gap, 180 - $gap ) . $arc( 180 + $gap, 360 - $gap );
			break;
		case 'dashed': // the dashes are revealed through a mask, so drawing never breaks them
			$id    = 'circ-m' . ( ++$n );
			$paths = sprintf(
				'<mask id="%1$s" maskUnits="userSpaceOnUse" x="-10" y="-10" width="120" height="120"><circle class="circ__mask" cx="50" cy="50" r="49" fill="none" stroke="#fff" stroke-width="12" transform="rotate(-90 50 50)"/></mask><circle cx="50" cy="50" r="49" mask="url(#%1$s)"/>',
				$id
			);
			break;
		default:
			$paths = '<circle cx="50" cy="50" r="49" transform="rotate(-90 50 50)"/>';
	}

	return sprintf(
		'<span class="%1$s"%2$s><svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">%3$s</svg>%4$s</span>',
		esc_attr( trim( "circ circ--$kind " . $o['class'] ) ),
		$o['draw'] ? ' data-draw="' . esc_attr( $o['draw'] ) . '"' : '',
		$paths,
		$inner !== '' ? '<span class="circ__in">' . $inner . '</span>' : ''
	);
}
