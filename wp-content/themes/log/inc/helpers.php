<?php
/**
 * Template helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which of the two twin buttons is active. "projects" everywhere except the control page.
 * Project pages flagged as control projects light up "control" too.
 */
function log_twin_state(): string {
	if ( is_page( 'control' ) ) {
		return 'control';
	}
	if ( is_singular( 'log_project' ) && function_exists( 'log_field' ) && log_field( 'is_control', get_the_ID() ) ) {
		return 'control';
	}
	return 'projects';
}

/**
 * Stage 1–4 as the quarter-ring marker from the spec. Pure markup; CSS draws it.
 */
function log_stage_ring( int $stage, bool $with_label = true ): string {
	$stage = max( 1, min( 4, $stage ) );
	$labels = [ 1 => 'איתור והיתכנות', 2 => 'תכנון וקידום סטטוטורי', 3 => 'ליווי ואישור', 4 => 'בקרה ומעקב' ];
	$html = sprintf(
		'<span class="stage stage--%1$d" role="img" aria-label="שלב %1$d מתוך 4: %2$s"><i></i><i></i><i></i><i></i></span>',
		$stage,
		esc_attr( $labels[ $stage ] )
	);
	if ( $with_label ) {
		$html .= sprintf( '<span class="stage__label">שלב %d מתוך 4 · %s</span>', $stage, esc_html( $labels[ $stage ] ) );
	}
	return $html;
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
 * Stage/scale/domain line for cards: "שלב 2 מתוך 4 · התחדשות עירונית · חולון · M".
 */
function log_project_meta_line( int $id ): string {
	$parts = [];
	$domain = get_the_terms( $id, 'log_domain' );
	if ( $domain && ! is_wp_error( $domain ) ) {
		$parts[] = $domain[0]->name;
	}
	$city = function_exists( 'log_field' ) ? log_field( 'city', $id ) : null;
	if ( $city ) {
		$parts[] = $city;
	}
	$scale = get_the_terms( $id, 'log_scale' );
	if ( $scale && ! is_wp_error( $scale ) ) {
		$parts[] = $scale[0]->name;
	}
	return esc_html( implode( ' · ', $parts ) );
}
