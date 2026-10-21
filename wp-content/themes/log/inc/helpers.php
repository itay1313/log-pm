<?php
/**
 * Template helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which of the two twin buttons is active (spec v6 §6): "control" on the control page,
 * "projects" on the projects list and project pages, none elsewhere.
 */
function log_twin_state(): string {
	if ( is_page( 'control' ) ) {
		return 'control';
	}
	if ( is_post_type_archive( 'log_project' ) || is_singular( 'log_project' ) ) {
		return 'projects';
	}
	return '';
}

/**
 * Step marker 1–4 (process steps, roles, recruitment path). Decorative: the step's own heading carries the meaning.
 */
function log_stage_ring( int $step ): string {
	$step = max( 1, min( 4, $step ) );
	return sprintf( '<span class="stage stage--%d" aria-hidden="true"><i></i><i></i><i></i><i></i></span>', $step );
}

/**
 * Hebrew-style number formatting (1,500) with a unit.
 */
function log_number( $n, string $unit = '' ): string {
	if ( $n === null || $n === '' ) {
		return '';
	}
	return number_format_i18n( (float) $n ) . ( $unit ? ' ' . $unit : '' );
}

/**
 * Newline-separated textarea → <ul>.
 */
function log_lines_to_list( ?string $text, string $class = '' ): string {
	if ( ! $text ) {
		return '';
	}
	$items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $text ) ) );
	if ( ! $items ) {
		return '';
	}
	$out = '<ul' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>';
	foreach ( $items as $item ) {
		$out .= '<li>' . esc_html( ltrim( $item, '-•· ' ) ) . '</li>';
	}
	return $out . '</ul>';
}

/**
 * "חולון · עיריית חולון": the city and the authority, once each (ArcGIS often gives the same name).
 */
function log_project_place( int $id ): string {
	$parts = [ (string) log_field( 'city', $id, '' ) ];
	$auth  = get_the_terms( $id, 'log_authority' );
	if ( $auth && ! is_wp_error( $auth ) ) {
		$parts[] = $auth[0]->name;
	}
	return implode( ' · ', array_unique( array_filter( array_map( 'trim', $parts ) ) ) );
}

/**
 * "התחדשות עירונית · חולון": domain, then place. Plain text, escaped.
 */
function log_project_meta_line( int $id ): string {
	$parts  = [];
	$domain = get_the_terms( $id, 'log_domain' );
	if ( $domain && ! is_wp_error( $domain ) ) {
		$parts[] = $domain[0]->name;
	}
	$parts[] = log_project_place( $id );
	return esc_html( implode( ' · ', array_filter( $parts ) ) );
}

/**
 * Lower-case haystack for the client-side search on cards and lists: name, city, authority.
 */
function log_project_search_text( int $id ): string {
	return mb_strtolower( get_the_title( $id ) . ' ' . log_project_place( $id ) );
}
