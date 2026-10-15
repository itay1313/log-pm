<?php
/**
 * Scale tier (S/M/L/XL), spec v5.1 §8.
 * The tier is the HIGHEST of the three criteria; a missing criterion is ignored.
 * A project flagged as a national plan is always XL.
 *
 *   units       sqm employment   dunam
 *   S  ≤500     ≤20,000          ≤200
 *   M  ≤2,000   ≤60,000          ≤1,000
 *   L  ≤5,000   ≤150,000         ≤5,000
 *   XL  above   above            above / national plan
 */

defined( 'ABSPATH' ) || exit;

function log_compute_scale( ?float $units, ?float $sqm, ?float $dunam, bool $national = false ): string {
	if ( $national ) {
		return 'xl';
	}
	$ladder = [
		[ 'units' => 500,  'sqm' => 20000,  'dunam' => 200,  'tier' => 's' ],
		[ 'units' => 2000, 'sqm' => 60000,  'dunam' => 1000, 'tier' => 'm' ],
		[ 'units' => 5000, 'sqm' => 150000, 'dunam' => 5000, 'tier' => 'l' ],
	];
	$tiers  = [ 's' => 0, 'm' => 1, 'l' => 2, 'xl' => 3 ];
	$result = null;

	foreach ( [ 'units' => $units, 'sqm' => $sqm, 'dunam' => $dunam ] as $key => $value ) {
		if ( $value === null || $value <= 0 ) {
			continue;
		}
		$tier = 'xl';
		foreach ( $ladder as $step ) {
			if ( $value <= $step[ $key ] ) {
				$tier = $step['tier'];
				break;
			}
		}
		if ( $result === null || $tiers[ $tier ] > $tiers[ $result ] ) {
			$result = $tier;
		}
	}

	return $result ?? 's';
}

/**
 * Statutory milestone (ArcGIS אבן_דרך, 01–09) → the four-stage ring. See docs/arcgis-mapping.md.
 * Returns null for "other"/empty so a manual stage is left alone.
 */
function log_stage_from_milestone( ?string $milestone ): ?int {
	return match ( $milestone ) {
		'01'                     => 1,
		'02', '03', '04', '05'   => 2,
		'06', '07', '08'         => 3,
		'09'                     => 4,
		default                  => null,
	};
}

/**
 * Recompute derived fields on every save, after ACF has written its own (ACF saves at priority 10).
 */
add_action( 'acf/save_post', function ( $post_id ) {
	if ( get_post_type( $post_id ) !== 'log_project' ) {
		return;
	}
	$stage = log_stage_from_milestone( get_field( 'milestone', $post_id ) ?: null );
	if ( $stage ) {
		update_field( 'stage', (string) $stage, $post_id );
	}

	$num = static fn( $v ) => ( $v === '' || $v === null ) ? null : (float) $v;
	$tier = log_compute_scale(
		$num( get_field( 'units', $post_id ) ),
		$num( get_field( 'sqm_employment', $post_id ) ),
		$num( get_field( 'dunam', $post_id ) ),
		(bool) get_field( 'is_national', $post_id )
	);
	wp_set_object_terms( $post_id, $tier, 'log_scale', false );
}, 20 );
