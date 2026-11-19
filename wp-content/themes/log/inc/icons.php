<?php
/**
 * LOG's eighteen icons (brand book chapter 04). One family: a 48 box, one thin line that keeps its weight at any
 * size (non-scaling stroke, --ico-w), rounded joins, one orange part (class "o") and a dashed part (class "d")
 * only where something isn't built yet. Every icon draws what is written under it.
 *
 * Drawn for the web book after the v3 book's set (which only exists as a picture). A new icon follows the same
 * rules: the 48 box with 4 to spare on each side, the same line, orange on one part only.
 */

defined( 'ABSPATH' ) || exit;

/** [ name => [ Hebrew label, SVG body ] ], in the book's order. */
function log_icons(): array {
	return [
		'urban'        => [ 'תכנון עירוני', '<path d="M4 42h40"/><path d="M7 42V27h7v15"/><path class="o" d="M14 42V15h8v27"/><path class="d" d="M22 42V8h7v34"/><path d="M29 42V22h6v20"/><path d="M31.5 26.5v2M31.5 31.5v2"/><circle cx="40.5" cy="33" r="3"/><path d="M40.5 36v6"/>' ],
		'team'         => [ 'צוות', '<circle class="o" cx="24" cy="16" r="5"/><path class="o" d="M15 35a9 9 0 0 1 18 0"/><circle cx="10.5" cy="21" r="3.5"/><path d="M4 35a7 7 0 0 1 11-5.7"/><circle cx="37.5" cy="21" r="3.5"/><path d="M44 35a7 7 0 0 0-11-5.7"/>' ],
		'sustain'      => [ 'קיימות', '<path d="M24 6a18 18 0 0 1 0 36"/><path d="M27.5 38.5 24 42l3.5 3.5"/><path class="d" d="M24 42a18 18 0 0 1 0-36"/><path d="M20.5 2.5 24 6l-3.5 3.5"/><path d="M24 33c-7-4.5-8.5-12-0.5-20 8 8 6.5 15.5 0.5 20z"/><path class="o" d="M24 37V19M24 27l-3.5-3.5M24 23.5l3-3"/>' ],
		'sites'        => [ 'אתרים', '<path d="M21.5 30H10L4 42h40l-6-12H26.5"/><path class="d" d="M17 30l-2.5 12M31 30l2.5 12M7 36.5h7.5M33.5 36.5H41"/><path class="o" d="M24 33.5c-4.5-5-7-8.5-7-11.5a7 7 0 0 1 14 0c0 3-2.5 6.5-7 11.5z"/><circle cx="24" cy="22" r="2.5"/>' ],
		'present'      => [ 'מצגות', '<path d="M24 4v4M5 8h38M8 8v23h32V8"/><path d="M24 31v5m0 0-6 7m6-7 6 7"/><path d="M14 26v-3M19 26v-6M24 26v-4"/><path class="o" d="M30 26V14"/>' ],
		'connect'      => [ 'חיבור', '<circle cx="22" cy="26" r="4"/><circle cx="10" cy="14" r="3"/><circle cx="10" cy="38" r="3"/><circle cx="34" cy="38" r="3"/><path d="M19.17 23.17l-7.05-7.05M19.17 28.83l-7.05 7.05M24.83 28.83l7.05 7.05"/><circle class="o" cx="38" cy="10" r="3.5"/><path class="o" d="M24.83 23.17l10.7-10.7"/>' ],
		'talk'         => [ 'שיחה', '<path d="M8 9h20a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H15l-6 5v-5H8a2 2 0 0 1-2-2V11a2 2 0 0 1 2-2z"/><path class="o" d="M11.5 17h13"/><path class="d" d="M30 17h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-2v5l-5-5H22a2 2 0 0 1-2-2v-6"/>' ],
		'plans'        => [ 'תוכניות', '<path d="M30 5H10v38h28V13"/><path class="o" d="M30 5l8 8h-8z"/><path d="M16 15h8"/><path class="d" d="M16 22h16M16 28h16M16 34h16"/>' ],
		'lead'         => [ 'הובלה', '<path d="M10 44c5-7 11-10 11.5-17S17 14 19 6"/><path d="M30 44c-1-7 4-11 4-18s-5-12-5-20"/><path class="d" d="M20 44c2.5-6 8-9.5 8-17s-4-10.5-4-14"/><path class="o" d="M20.5 8.5 24 5l3.5 3.5"/><circle cx="7" cy="22" r="3"/><path d="M7 25v5"/><circle cx="41" cy="18" r="3"/><path d="M41 21v5"/>' ],
		'build'        => [ 'ביצוע', '<path d="M9 33a15 15 0 0 1 30 0"/><path d="M6.5 33h35a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-35A1.5 1.5 0 0 1 5 35.5v-1A1.5 1.5 0 0 1 6.5 33z"/><path class="o" d="M21.5 18.5V33M26.5 18.5V33"/>' ],
		'schedule'     => [ 'לוח זמנים', '<path d="M8 11h32a1 1 0 0 1 1 1v28a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V12a1 1 0 0 1 1-1zM7 18h34"/><path d="M16 7v7M32 7v7"/><path class="d" d="M7 26h34M7 34h34M18 18v23M30 18v23"/><path class="o" d="M20.5 30h7"/>' ],
		'data'         => [ 'נתונים', '<path class="o" d="M25.19 7.04A17 17 0 0 1 40.96 22.81L33.98 23.3A10 10 0 0 0 24.7 14.02Z"/><path d="M40.96 25.19A17 17 0 0 1 19.31 40.34L21.24 33.61A10 10 0 0 0 33.98 24.7Z"/><path d="M17.09 39.53A17 17 0 0 1 7.5 28.11L14.3 26.42A10 10 0 0 0 19.93 33.14Z"/><path class="d" d="M7.09 25.78A17 17 0 0 1 22.81 7.04L23.3 14.02A10 10 0 0 0 14.05 25.05Z"/>' ],
		'ideas'        => [ 'רעיונות', '<path d="M18.5 32c-3.5-2.5-5.5-6.5-5.5-10.5a11 11 0 0 1 22 0c0 4-2 8-5.5 10.5V36h-11z"/><path d="M19 40h10M21.5 44h5"/><path class="o" d="M22 36v-6l2-4 2 4v6"/><path d="M24 3v3M10 7.5l2.2 2.2M38 7.5l-2.2 2.2M4.5 21.5H8M40 21.5h3.5"/>' ],
		'buildings'    => [ 'מבנים', '<path d="M4 42h40"/><path d="M8 42V10h12v32M11 10V6h6v4"/><path class="d" d="M12 15v22M16 15v22"/><path class="o" d="M20 42V20h11v22M23.5 25.5h4M23.5 30h4M23.5 34.5h4"/><circle cx="37.5" cy="31.5" r="3.5"/><path d="M37.5 35v7"/>' ],
		'layers'       => [ 'שכבות', '<path class="o" d="M24 7l17 8.5L24 24 7 15.5z"/><path class="d" d="M7 23l17 8.5L41 23"/><path d="M7 30.5 24 39l17-8.5"/>' ],
		'environment'  => [ 'סביבה', '<circle cx="24" cy="15" r="10"/><path d="M24 25v8"/><path class="o" d="M24 23V10M24 17l-3.5-3.5M24 14l3.5-3.5M24 20.5l3-3"/><path d="M6 33h36"/><path class="d" d="M6 38.5c3-2 6-2 9 0s6 2 9 0 6-2 9 0 6 2 9 0M10 43.5c2.3-1.5 4.7-1.5 7 0s4.7 1.5 7 0 4.7-1.5 7 0 4.7 1.5 7 0"/>' ],
		'control'      => [ 'בקרה', '<path d="M5 12h8.5M19.5 12H43"/><circle cx="16.5" cy="12" r="3"/><path d="M5 24h22.5M33.5 24H43"/><circle class="o" cx="30.5" cy="24" r="3"/><path d="M5 36h4.5"/><circle cx="12.5" cy="36" r="3"/><path class="d" d="M15.5 36H43"/>' ],
		'target'       => [ 'יעד', '<circle class="d" cx="24" cy="24" r="14"/><path d="M24 4v6M24 38v6M4 24h6M38 24h6"/><path class="o" d="M17 31l14-14M23.5 17H31v7.5"/>' ],
	];
}

/**
 * One icon as inline SVG. Decoration by default (aria-hidden): the word under it says what it is.
 * $label: give it an accessible name instead.
 */
function log_icon( string $name, string $label = '', string $class = '' ): string {
	$icons = log_icons();
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$a11y = $label === '' ? 'aria-hidden="true"' : sprintf( 'role="img" aria-label="%s"', esc_attr( $label ) );
	return sprintf(
		'<svg class="%s" viewBox="0 0 48 48" %s focusable="false">%s</svg>',
		esc_attr( trim( 'ico ico--' . $name . ' ' . $class ) ),
		$a11y,
		$icons[ $name ][1] // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup from log_icons()
	);
}
