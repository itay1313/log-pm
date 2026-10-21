<?php
/**
 * Project stage (spec v6 §10, §17): "שלב 2 מתוך 4 · תכנון סטטוטורי", or "הושלם".
 *
 * - ArcGIS סטטוס 02 (הושלם) → completed.
 * - Otherwise the statutory milestone (ArcGIS אבן_דרך, 01–09) gives the number. See docs/arcgis-mapping.md.
 * - Milestone "other"/empty → the manual `stage` field the editor picked.
 * The result is stored as the `stage` number and as a log_stage term, which the filters count on.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Statutory milestone → stage 1–4. Null for "other"/empty so a manual stage is left alone.
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
 * Recompute the stage for one project.
 * Called from acf/save_post and from the ArcGIS importer, which writes fields directly
 * and therefore never fires acf/save_post.
 */
function log_project_recompute_derived( int $post_id ): void {
	$stage = log_stage_from_milestone( log_field( 'milestone', $post_id ) ?: null );
	if ( $stage ) {
		log_set_field( 'stage', (string) $stage, $post_id );
	}
	$number = max( 1, min( 4, (int) log_field( 'stage', $post_id, 1 ) ) );
	$slug   = log_field( 'status', $post_id, '01' ) === '02' ? 'completed' : 'stage-' . $number;
	wp_set_object_terms( $post_id, $slug, 'log_stage', false );
}

/**
 * Recompute on every save, after ACF has written its own fields (ACF saves at priority 10).
 */
add_action( 'acf/save_post', function ( $post_id ) {
	if ( get_post_type( $post_id ) === 'log_project' ) {
		log_project_recompute_derived( (int) $post_id );
	}
}, 20 );

/** Without ACF there's no acf/save_post: recompute on the plain save instead. */
add_action( 'save_post_log_project', function ( int $post_id ) {
	if ( ! function_exists( 'acf' ) && ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) ) {
		log_project_recompute_derived( $post_id );
	}
}, 20 );

/**
 * The project's stage for display.
 *
 * @return array{number:int, slug:string, name:string, label:string, completed:bool}
 */
function log_project_stage( int $post_id ): array {
	$terms = wp_get_post_terms( $post_id, 'log_stage' );
	$term  = ( is_array( $terms ) && $terms ) ? $terms[0] : null;
	$defaults = log_stage_terms();

	if ( $term ) {
		$slug   = $term->slug;
		$name   = $term->name;
		$number = (int) ( $defaults[ $slug ][1] ?? get_term_meta( $term->term_id, 'number', true ) );
	} else {
		$number = max( 1, min( 4, (int) log_field( 'stage', $post_id, 1 ) ) );
		$slug   = 'stage-' . $number;
		$name   = $defaults[ $slug ][0];
	}
	$completed = $slug === 'completed';

	return [
		'number'    => $completed ? 0 : $number,
		'slug'      => $slug,
		'name'      => $name,
		'label'     => $completed ? $name : sprintf( 'שלב %d מתוך 4 · %s', $number, $name ),
		'completed' => $completed,
	];
}
