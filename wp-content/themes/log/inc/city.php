<?php
/**
 * The city in lines: the home map's look (src/hero/scene.js) as a drawing, for places where the 3D map can't go:
 * the brand book, its PDF, a page without WebGL. Navy volumes, the orange line on their corners and roof, roads as
 * thin lines that run to the edge, and on top of it a plan the way the map draws one: an orange plate under a
 * dashed boundary, with its dimensions and its area.
 *
 * The city is made up, from a seed, so it is the same drawing on every load. The plan's numbers are not made up:
 * its lengths and its area are measured from the drawing itself.
 *
 * Styled in src/styles/city.css; src/fx/city.js raises it out of the ground.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array $o {
 *   w, h      The drawing's size in sheet pixels: one unit of the SVG is one sheet pixel.
 *   seed      Which city.
 *   cols,rows City blocks across and deep. block, street: their sizes in metres.
 *   scale     Sheet pixels per metre. theta: the turn of the grid, in degrees. tilt: how flat the ground lies (0 to 1).
 *   cx, cy    Where the city's middle sits in the drawing, as fractions of w and h.
 *   density   0 to 1: how built the blocks are. The edges thin out by themselves.
 *   plan      A plan's corners on the street grid, [[col,row], ...]. What stands inside it today is cleared.
 *   ghosts    true: inside the plan, the proposed buildings stand as a massing study, the way the site's project
 *             cards draw a plan not yet built: see-through orange volumes with a floor line on every storey.
 *   dims      Dimension lines along the plan's two near sides. area: its size, written on it.
 *   pins      Projects: [col,row] is one dot, [col,row,n] is n projects at one place.
 *   fade      [from, to] in sheet pixels from the top: the roads come out of the ground between the two, so words can stand above.
 *   label     For screen readers; without it the drawing is decoration.
 *   class     Extra classes.
 * }
 */
function log_city( array $o = [] ): string {
	$o += [
		'w' => 1280, 'h' => 720, 'seed' => 7, 'cols' => 12, 'rows' => 9, 'block' => 84, 'street' => 18,
		'scale' => 0.62, 'theta' => 34, 'tilt' => 0.56, 'cx' => 0.5, 'cy' => 0.55, 'density' => 0.9,
		'plan' => [], 'ghosts' => false, 'dims' => true, 'area' => true, 'pins' => [], 'fade' => [], 'label' => '', 'class' => '',
	];
	static $uid = 0;
	$uid++;

	// A small generator of its own, so the drawing never depends on PHP's global random state.
	$state = ( (int) $o['seed'] * 2654435761 + 12345 ) % 4294967296;
	$rnd   = static function () use ( &$state ): float {
		$state = ( $state * 1664525 + 1013904223 ) % 4294967296;
		return $state / 4294967296;
	};

	$pitch = $o['block'] + $o['street'];
	$mx    = ( $o['cols'] * $pitch - $o['street'] ) / 2; // the city's middle, in metres
	$my    = ( $o['rows'] * $pitch - $o['street'] ) / 2;
	$a     = deg2rad( (float) $o['theta'] );
	[ $ca, $sa ] = [ cos( $a ), sin( $a ) ];
	$k  = (float) $o['tilt'];
	$zk = sqrt( max( 0, 1 - $k * $k ) );
	$s  = (float) $o['scale'];
	$ox = $o['w'] * $o['cx'];
	$oy = $o['h'] * $o['cy'];

	/** Metres on the ground (and a height) to sheet pixels. */
	$pt = static function ( float $x, float $y, float $z = 0 ) use ( $mx, $my, $ca, $sa, $k, $zk, $s, $ox, $oy ): array {
		$x -= $mx;
		$y -= $my;
		return [ $ox + ( $x * $ca - $y * $sa ) * $s, $oy + ( $x * $sa + $y * $ca ) * $k * $s - $z * $zk * $s ];
	};
	$n  = static fn( float $v ): string => rtrim( rtrim( number_format( $v, 1, '.', '' ), '0' ), '.' );
	$xy = static fn( array $p ): string => $n( $p[0] ) . ' ' . $n( $p[1] );
	/** A corner of the street grid (the middle of a crossing) in metres. */
	$grid = static fn( float $c, float $r ): array => [ $c * $pitch - $o['street'] / 2, $r * $pitch - $o['street'] / 2 ];

	$plan   = array_values( $o['plan'] );
	$inside = static function ( float $c, float $r ) use ( $plan ): bool {
		$in = false;
		for ( $i = 0, $j = count( $plan ) - 1; $i < count( $plan ); $j = $i++ ) {
			[ $xi, $yi ] = $plan[ $i ];
			[ $xj, $yj ] = $plan[ $j ];
			if ( ( $yi > $r ) !== ( $yj > $r ) && $c < ( $xj - $xi ) * ( $r - $yi ) / ( $yj - $yi ) + $xi ) {
				$in = ! $in;
			}
		}
		return $in;
	};

	// ---------------------------------------------------------------- roads: thin lines that run to the edge
	$far   = 60 * $pitch;
	$roads = '';
	for ( $c = 0; $c <= $o['cols']; $c++ ) {
		[ $x ] = $grid( $c, 0 );
		$roads .= 'M' . $xy( $pt( $x, -$far ) ) . 'L' . $xy( $pt( $x, $far ) );
	}
	for ( $r = 0; $r <= $o['rows']; $r++ ) {
		[ , $y ] = $grid( 0, $r );
		$roads .= 'M' . $xy( $pt( -$far, $y ) ) . 'L' . $xy( $pt( $far, $y ) );
	}

	// ---------------------------------------------------------------- buildings
	$floor = 4.6; // a storey, drawn a little tall so low houses still read as volumes
	$built = [];
	for ( $c = 0; $c < $o['cols']; $c++ ) {
		for ( $r = 0; $r < $o['rows']; $r++ ) {
			$lots = [ 1 + (int) ( $rnd() * 3 ), 1 + (int) ( $rnd() * 3 ) ]; // always drawn, so a plan never reshuffles the city
			$dice = [];
			for ( $i = 0; $i < 9; $i++ ) {
				$dice[] = [ $rnd(), $rnd(), $rnd(), $rnd(), $rnd(), $rnd() ];
			}
			$ghost = $plan && $inside( $c + 0.5, $r + 0.5 );
			if ( $ghost && ! $o['ghosts'] ) {
				continue;
			}
			// The city thins out towards its edges, the way a town does.
			$e    = max( abs( ( $c + 0.5 ) / $o['cols'] - 0.5 ), abs( ( $r + 0.5 ) / $o['rows'] - 0.5 ) ) * 2;
			$fill = $o['density'] * ( 1 - max( 0, ( $e - 0.55 ) / 0.45 ) ** 1.6 * 0.82 );
			$lw   = $o['block'] / $lots[0];
			$ld   = $o['block'] / $lots[1];
			for ( $i = 0; $i < $lots[0]; $i++ ) {
				for ( $j = 0; $j < $lots[1]; $j++ ) {
					$d = $dice[ $i * 3 + $j ];
					if ( ! $ghost && $d[0] > $fill ) {
						continue;
					}
					// A plan builds denser and higher than the town around it: every lot, and a third of them towers.
					$tower  = $ghost ? $d[1] > 0.66 : $d[1] > 0.93 && $e < 0.7;
					$floors = $tower ? 11 + (int) ( $d[2] * 13 ) : ( $ghost ? 4 + (int) ( $d[2] * 5 ) : 2 + (int) ( $d[2] * $d[2] * 6 ) );
					$gap    = 3 + $d[3] * 4;
					$w      = ( $lw - 2 * $gap ) * ( $tower ? 0.62 : 0.72 + $d[4] * 0.28 );
					$dp     = ( $ld - 2 * $gap ) * ( $tower ? 0.62 : 0.72 + $d[5] * 0.28 );
					if ( $w < 7 || $dp < 7 ) {
						continue;
					}
					$x0      = $c * $pitch + $i * $lw + $gap + ( $lw - 2 * $gap - $w ) * $d[4];
					$y0      = $r * $pitch + $j * $ld + $gap + ( $ld - 2 * $gap - $dp ) * $d[5];
					$built[] = [ $x0, $y0, $x0 + $w, $y0 + $dp, $floors * $floor, $floors, $e, $ghost ];
				}
			}
		}
	}
	// Far first, so what stands in front covers what stands behind.
	usort( $built, static fn( $p, $q ) => ( ( $p[0] + $p[2] ) * $sa + ( $p[1] + $p[3] ) * $ca ) <=> ( ( $q[0] + $q[2] ) * $sa + ( $q[1] + $q[3] ) * $ca ) );

	$houses = '';
	foreach ( $built as [ $x0, $y0, $x1, $y1, $h, $floors, $e, $ghost ] ) {
		// Seen from the near side: the left corner, the corner facing us, the right one, and the one behind.
		[ $l, $f, $rt ]         = [ $pt( $x0, $y1 ), $pt( $x1, $y1 ), $pt( $x1, $y0 ) ];
		[ $lt, $ft, $rtt, $bt ] = [ $pt( $x0, $y1, $h ), $pt( $x1, $y1, $h ), $pt( $x1, $y0, $h ), $pt( $x0, $y0, $h ) ];
		$walls = 'M' . $xy( $l ) . 'L' . $xy( $f ) . 'L' . $xy( $rt ) . 'L' . $xy( $rtt ) . 'L' . $xy( $ft ) . 'L' . $xy( $lt ) . 'ZM' . $xy( $f ) . 'L' . $xy( $ft );
		$roof  = 'M' . $xy( $lt ) . 'L' . $xy( $ft ) . 'L' . $xy( $rtt ) . 'L' . $xy( $bt ) . 'Z';
		$lines = '';
		// A tower shows its storeys (on a house they would close up into a fill); a proposed building shows every one.
		if ( $floors >= 9 || $ghost ) {
			for ( $z = $floor * ( $ghost ? 1 : 2 ); $z < $h - 1; $z += $floor * ( $ghost ? 1 : 2 ) ) {
				$lines .= 'M' . $xy( $pt( $x0, $y1, $z ) ) . 'L' . $xy( $pt( $x1, $y1, $z ) ) . 'L' . $xy( $pt( $x1, $y0, $z ) );
			}
		}
		$houses .= sprintf(
			'<g class="b%s"><path class="w" d="%s"/><path class="r" d="%s"/>%s</g>',
			$ghost ? ' b--ghost' : ( $e > 0.78 ? ' b--far' : ( $e > 0.5 ? ' b--mid' : '' ) ),
			$walls,
			$roof,
			$lines ? '<path class="f" d="' . $lines . '"/>' : ''
		);
	}

	// ---------------------------------------------------------------- the plan
	$site = '';
	$over = '';
	if ( count( $plan ) > 2 ) {
		$ring = '';
		foreach ( $plan as $i => [ $c, $r ] ) {
			$ring .= ( $i ? 'L' : 'M' ) . $xy( $pt( ...$grid( $c, $r ) ) );
		}
		$ring .= 'Z';
		// Its own streets show through the plate, with a roundabout where two of them meet.
		[ $c0, $r0, $c1, $r1 ] = [ min( array_column( $plan, 0 ) ), min( array_column( $plan, 1 ) ), max( array_column( $plan, 0 ) ), max( array_column( $plan, 1 ) ) ];
		$streets = '';
		$rounds  = '';
		for ( $c = $c0; $c < $c1; $c++ ) {
			for ( $r = $r0; $r < $r1; $r++ ) {
				if ( ! $inside( $c + 0.5, $r + 0.5 ) ) {
					continue;
				}
				if ( $inside( $c + 1.5, $r + 0.5 ) ) {
					$streets .= 'M' . $xy( $pt( ...$grid( $c + 1, $r ) ) ) . 'L' . $xy( $pt( ...$grid( $c + 1, $r + 1 ) ) );
				}
				if ( $inside( $c + 0.5, $r + 1.5 ) ) {
					$streets .= 'M' . $xy( $pt( ...$grid( $c, $r + 1 ) ) ) . 'L' . $xy( $pt( ...$grid( $c + 1, $r + 1 ) ) );
				}
				if ( $inside( $c + 1.5, $r + 0.5 ) && $inside( $c + 0.5, $r + 1.5 ) && $inside( $c + 1.5, $r + 1.5 ) ) {
					$p       = $pt( ...$grid( $c + 1, $r + 1 ) );
					$rounds .= sprintf( '<ellipse cx="%s" cy="%s" rx="%s" ry="%s"/>', $n( $p[0] ), $n( $p[1] ), $n( 11 * $s ), $n( 11 * $s * $k ) );
				}
			}
		}
		$site = '<g class="cty__site"><path class="cty__plate" d="' . $ring . '"/><path class="cty__streets" d="' . $streets . '"/><g class="cty__rounds">' . $rounds . '</g></g>';
		$over = '<path class="cty__bound" d="' . $ring . '"/>';

		// Measured from the drawing: the lengths of the two near sides, and the area inside the boundary.
		if ( $o['dims'] ) {
			$off = 0.85;
			foreach ( [ [ [ $c0, $r1 + $off ], [ $c1, $r1 + $off ], [ 0, 1 ], $c1 - $c0 ], [ [ $c1 + $off, $r0 ], [ $c1 + $off, $r1 ], [ 1, 0 ], $r1 - $r0 ] ] as [ $from, $to, $out, $len ] ) {
				$p    = $pt( ...$grid( ...$from ) );
				$q    = $pt( ...$grid( ...$to ) );
				$tick = '';
				foreach ( [ $from, $to ] as $end ) {
					$tick .= 'M' . $xy( $pt( ...$grid( $end[0] - $out[0] * 0.22, $end[1] - $out[1] * 0.22 ) ) ) . 'L' . $xy( $pt( ...$grid( $end[0] + $out[0] * 0.22, $end[1] + $out[1] * 0.22 ) ) );
				}
				$at    = $pt( ...$grid( ( $from[0] + $to[0] ) / 2 + $out[0] * 0.7, ( $from[1] + $to[1] ) / 2 + $out[1] * 0.7 ) );
				$over .= sprintf(
					'<g class="cty__dim"><path class="cty__dim-line" pathLength="1" d="M%s L%s"/><path class="cty__dim-ticks" d="%s"/><text x="%s" y="%s">%s מ\'</text></g>',
					$xy( $p ),
					$xy( $q ),
					$tick,
					$n( $at[0] ),
					$n( $at[1] ),
					esc_html( number_format_i18n( round( $len * $pitch / 10 ) * 10 ) )
				);
			}
		}
		if ( $o['area'] ) {
			$sum = 0;
			[ $gx, $gy ] = [ 0, 0 ];
			foreach ( $plan as $i => [ $c, $r ] ) {
				[ $c2, $r2 ] = $plan[ ( $i + 1 ) % count( $plan ) ];
				$cross = $c * $r2 - $c2 * $r;
				$sum  += $cross;
				$gx   += ( $c + $c2 ) * $cross;
				$gy   += ( $r + $r2 ) * $cross;
			}
			$at    = $pt( ...$grid( $gx / ( 3 * $sum ), $gy / ( 3 * $sum ) ) );
			$dunam = (int) round( abs( $sum ) / 2 * $pitch * $pitch / 1000 );
			$over .= sprintf(
				'<g class="cty__area"><text x="%1$s" y="%2$s"><tspan class="cty__area-n" data-count="%3$d">%4$s</tspan> דונם</text><text class="cty__area-cap" x="%1$s" y="%5$s">שטח התכנית</text></g>',
				$n( $at[0] ),
				$n( $at[1] ),
				$dunam,
				esc_html( number_format_i18n( $dunam ) ),
				$n( $at[1] + 22 )
			);
		}
	}

	// ---------------------------------------------------------------- projects: a dot, or a number where several share a place
	foreach ( $o['pins'] as $pin ) {
		$p     = $pt( ...$grid( (float) $pin[0], (float) $pin[1] ) );
		$count = (int) ( $pin[2] ?? 1 );
		$over .= sprintf(
			'<g transform="translate(%s)"><g class="cty__pin%s">%s</g></g>',
			$xy( $p ),
			$count > 1 ? ' cty__pin--many' : '',
			$count > 1 ? '<circle r="17"/><text y="5">' . $count . '</text>' : '<circle class="cty__pin-ring" r="12.5"/><circle r="8"/>'
		);
	}

	$defs = '';
	$ink  = '';
	if ( count( $o['fade'] ) === 2 ) {
		$defs = sprintf( '<defs><linearGradient id="cty-fade-%d" gradientUnits="userSpaceOnUse" x1="0" y1="%d" x2="0" y2="%d"><stop class="cty__fade" offset="0" stop-opacity="0"/><stop class="cty__fade" offset="1"/></linearGradient></defs>', $uid, $o['fade'][0], $o['fade'][1] );
		$ink  = sprintf( ' style="stroke: url(#cty-fade-%d)"', $uid );
	}

	return sprintf(
		'<svg class="cty %1$s" viewBox="0 0 %2$d %3$d" preserveAspectRatio="xMidYMid slice" style="--cty-w: %2$d" %4$s focusable="false">%9$s<path class="cty__roads"%10$s d="%5$s"/>%6$s<g class="cty__houses">%7$s</g><g class="cty__over">%8$s</g></svg>',
		esc_attr( $o['class'] ),
		$o['w'],
		$o['h'],
		$o['label'] !== '' ? 'role="img" aria-label="' . esc_attr( $o['label'] ) . '"' : 'aria-hidden="true"',
		$roads,
		$site,
		$houses,
		$over,
		$defs,
		$ink
	);
}
