<?php
/**
 * The roundabout: LOG's O seen from above (brand book chapter 06, and the first moment of the home page).
 *
 * An aerial roundabout at night, drawn in the logo's own units around the O's centre (0,0): the ring road is
 * the O (outer radius 190, inner 130), and its four arms are the roads that cut the O into quarters (60 wide).
 * src/home/roundabout.js shrinks it until the ring is the size of the logo's O, the city falls away, the ring turns
 * into the logo, its quarters lock, and thin lines run out of the O to the edges.
 *
 * Colours are the night family and the map's tree green, all from the tokens. No orange: the logo brings it.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The aerial drawing. Everything is generated once per request from a fixed seed, so it is the same every time.
 *
 * @param string $viewbox The window onto the scene, in logo units (the scene spans -1200..1200 both ways).
 * @param string $class   Extra classes.
 */
function log_roundabout_svg( string $viewbox = '-1200 -1200 2400 2400', string $class = '' ): string {
	static $city = null;
	if ( $city === null ) {
		$city = log_roundabout_city();
	}
	return sprintf(
		'<svg class="%s" viewBox="%s" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( trim( 'ra__city ' . $class ) ),
		esc_attr( $viewbox ),
		$city // phpcs:ignore WordPress.Security.EscapeOutput -- generated geometry
	);
}

/** The scene's markup: city blocks, plaza, ring, arms, island, markings and the traffic. */
function log_roundabout_city(): string {
	$n   = static fn( float $v ): string => rtrim( rtrim( sprintf( '%.1f', $v ), '0' ), '.' );
	$out = '';

	// A seeded random, so the city never changes between loads.
	$seed = 7;
	$rand = static function ( float $a, float $b ) use ( &$seed ): float {
		$seed = ( $seed * 1103515245 + 12345 ) & 0x7fffffff;
		return $a + ( $b - $a ) * ( $seed / 0x7fffffff );
	};

	// City blocks on a street grid, kept clear of the two roads and the roundabout's plaza.
	$blocks = '';
	$builds = '';
	for ( $gx = -1200; $gx < 1200; $gx += 150 ) {
		for ( $gy = -1200; $gy < 1200; $gy += 130 ) {
			$x = $gx + 13;
			$y = $gy + 13;
			$w = 124;
			$h = 104;
			// Trim against the roads (60 wide, with a 16 pavement on each side).
			foreach ( [ [ 'x', $x, $w ], [ 'y', $y, $h ] ] as [ $axis, $a, $len ] ) {
				if ( $a < 46 && $a + $len > -46 ) {
					if ( $a + $len / 2 < 0 ) {
						$len = -46 - $a;
					} else {
						$len -= 46 - $a;
						$a = 46;
					}
					if ( $axis === 'x' ) {
						[ $x, $w ] = [ $a, $len ];
					} else {
						[ $y, $h ] = [ $a, $len ];
					}
				}
			}
			if ( $w < 30 || $h < 30 ) {
				continue;
			}
			// Nothing inside the plaza around the ring.
			$cx = max( $x, min( 0, $x + $w ) );
			$cy = max( $y, min( 0, $y + $h ) );
			if ( hypot( $cx, $cy ) < 250 ) {
				continue;
			}
			$blocks .= sprintf( '<rect x="%s" y="%s" width="%s" height="%s" rx="4"/>', $n( $x ), $n( $y ), $n( $w ), $n( $h ) );
			// One to three buildings in each block, their roofs a shade lighter.
			$count = (int) round( $rand( 1, 3 ) );
			for ( $i = 0; $i < $count; $i++ ) {
				$bw = $rand( 22, min( 60, $w - 16 ) );
				$bh = $rand( 18, min( 48, $h - 16 ) );
				$bx = $x + 8 + $rand( 0, $w - 16 - $bw );
				$by = $y + 8 + $rand( 0, $h - 16 - $bh );
				$builds .= sprintf( '<rect x="%s" y="%s" width="%s" height="%s"/>', $n( $bx ), $n( $by ), $n( $bw ), $n( $bh ) );
			}
		}
	}
	$out .= '<g class="ra__fade"><g class="ra__blocks">' . $blocks . '</g><g class="ra__roofs">' . $builds . '</g>';
	$out .= '<circle class="ra__plaza" r="222"/></g>';

	// The ring road: the O. Its colour goes from asphalt to the logo's light grey.
	$out .= '<circle class="ra__ring" r="160" stroke-width="60"/><circle class="ra__ring ra__ring--lit" r="160" stroke-width="60"/>';

	// The arms: the ground's colour, laid over the ring, so they cut it into the O's four quarters.
	$out .= '<g class="ra__arms"><rect x="-1300" y="-30" width="2600" height="60"/><rect x="-30" y="-1300" width="60" height="2600"/></g>';

	// Everything from here on falls away as the camera rises.
	$out .= '<g class="ra__fade">';

	// The island, planted.
	$trees = '';
	foreach ( [ [ 20, 66, 26 ], [ 95, 70, 22 ], [ 160, 58, 24 ], [ 230, 72, 20 ], [ 300, 60, 25 ], [ 0, 0, 30 ], [ 200, 18, 14 ] ] as [ $deg, $dist, $r ] ) {
		$trees .= sprintf( '<circle cx="%s" cy="%s" r="%d"/>', $n( $dist * sin( deg2rad( $deg ) ) ), $n( -$dist * cos( deg2rad( $deg ) ) ), $r );
	}
	$out .= '<circle class="ra__island" r="128"/><circle class="ra__kerb" r="118"/><g class="ra__trees">' . $trees . '</g>';

	// Markings: the ring's lane line, the arms' centre lines, give-way lines and a zebra on each arm.
	$marks = '<circle r="160" class="ra__lane"/>';
	foreach ( [ 0, 90, 180, 270 ] as $deg ) {
		$zebra = '';
		for ( $i = -24; $i <= 18; $i += 10 ) {
			$zebra .= sprintf( '<rect x="236" y="%d" width="24" height="5"/>', $i );
		}
		$marks .= sprintf(
			'<g transform="rotate(%1$d)"><path class="ra__lane" d="M290 0H1300"/><path class="ra__giveway" d="M196 2V28"/>%2$s</g>',
			$deg,
			$zebra
		);
	}
	$out .= '<g class="ra__marks">' . $marks . '</g>';

	// Traffic: cars on the ring (turning counter-clockwise, as traffic does here) and on the arms.
	$car  = '<g class="ra__car"><rect x="-8" y="-4.5" width="16" height="9" rx="2.5"/><circle class="ra__lamp" cx="8" cy="-2.6" r="1.6"/><circle class="ra__lamp" cx="8" cy="2.6" r="1.6"/></g>';
	$ring = '';
	foreach ( [ [ 10, 145 ], [ 75, 175 ], [ 140, 145 ], [ 200, 175 ], [ 262, 145 ], [ 318, 175 ] ] as [ $deg, $r ] ) {
		// On a counter-clockwise ring a car at angle a heads along -tangent: rotate so its nose (x+) points there.
		$ring .= sprintf( '<g transform="rotate(%1$d) translate(0 %2$d) rotate(180)">%3$s</g>', $deg, -$r, $car );
	}
	$out .= '<g class="ra__traffic" data-ra-ring>' . $ring . '</g>';
	$arms = '';
	foreach ( [ 0, 90, 180, 270 ] as $deg ) {
		// Driving on the right: heading in (west, on this arm) the right-hand lane is the upper one.
		$arms .= sprintf( '<g transform="rotate(%1$d)"><g data-ra-lane="in"><g transform="translate(0 -15) rotate(180)">%2$s</g></g><g data-ra-lane="out"><g transform="translate(0 15)">%2$s</g></g></g>', $deg, $car );
	}
	$out .= '<g class="ra__traffic">' . $arms . '</g>';

	$out .= '</g>';
	return '<g class="ra__world">' . $out . '</g>';
}

/**
 * The whole stage: the drawing, the four lines that run out of the O, and the logo whose O the ring becomes.
 *
 * @param string $context 'home' (the opening, fixed over the page) or 'book' (a sheet of the brand book).
 */
function log_roundabout( string $context = 'home' ): string {
	$lines = '';
	foreach ( [ 'n', 'e', 's', 'w' ] as $dir ) {
		$lines .= '<i class="ra__line ra__line--' . $dir . '"></i>';
	}
	return sprintf(
		'<div class="ra ra--%1$s" data-ra aria-hidden="true">%2$s<div class="ra__lines">%3$s</div><div class="ra__logo">%4$s</div></div>',
		esc_attr( $context ),
		log_roundabout_svg(),
		$lines,
		log_logo( [ 'variant' => 'night', 'label' => '' ] )
	);
}
