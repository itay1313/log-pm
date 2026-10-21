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
 * Numbered survey station for process steps, roles and the hiring path. The number carries the order.
 */
function log_stage_ring( int $step ): string {
	return sprintf( '<span class="stage" aria-hidden="true">%d</span>', max( 1, $step ) );
}

/**
 * The stage row: four steps on one unbroken line, filled to "now" (or all the way when completed),
 * with the stage text under it. $stage is log_project_stage()'s array.
 */
function log_stage_row( array $stage, string $class = '' ): string {
	$done = ! empty( $stage['completed'] );
	return sprintf(
		'<div class="stage-row%1$s%2$s"><span class="stage-row__track" aria-hidden="true" style="--now:%3$d"><i></i><i></i><i></i><i></i><span class="stage-row__fill"></span></span><span class="stage-row__label">%4$s</span></div>',
		$done ? ' stage-row--done' : '',
		$class ? ' ' . esc_attr( $class ) : '',
		$done ? 4 : max( 1, min( 4, (int) $stage['number'] ) ),
		esc_html( $stage['label'] )
	);
}

/**
 * Where the current page sits in the sheet series: [number, name], from the header's page order.
 * Item pages take their area's sheet. Home is sheet 0 (the index map).
 */
function log_sheet(): array {
	if ( is_front_page() ) {
		return [ 0, 'מפת הפרויקטים' ];
	}
	foreach ( log_nav_items() as $i => [ $label, , $current ] ) {
		if ( $current ) {
			return [ $i + 1, $label ];
		}
	}
	if ( is_search() ) {
		return [ 0, 'חיפוש' ];
	}
	return [ 0, wp_strip_all_tags( get_the_title() ) ];
}

/** The sheet label for a title block: "גיליון 3 · אודות". */
function log_sheet_label( string $extra = '' ): string {
	[ $n, $name ] = log_sheet();
	$num = $n ? sprintf( '%02d', $n ) : '—';
	return sprintf(
		'<p class="sheet-label"><b>%1$s</b>גיליון · %2$s%3$s</p>',
		esc_html( $num ),
		esc_html( $name ),
		$extra !== '' ? '<br>' . esc_html( $extra ) : ''
	);
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

/**
 * Public projects (approved, published, not stopped), as post objects. Cached per request.
 *
 * @return WP_Post[]
 */
function log_public_projects(): array {
	static $posts = null;
	if ( $posts === null ) {
		$posts = array_values( array_filter(
			log_approved_projects_query()->posts,
			static fn( $p ) => log_project_is_public( $p->ID )
		) );
	}
	return $posts;
}

/**
 * The three filters with their options and counts over the given projects (spec v6 §8–9).
 * Option values are decoded slugs (Hebrew authority slugs are stored encoded).
 *
 * @param WP_Post[] $projects
 * @return array<string, array{label:string, options:array<int, array{value:string, name:string, count:int}>}>
 */
function log_project_filters( array $projects ): array {
	$out = [];
	foreach ( [ 'domain' => [ 'log_domain', 'תחום' ], 'stage' => [ 'log_stage', 'שלב או סטטוס' ], 'authority' => [ 'log_authority', 'רשות' ] ] as $param => [ $tax, $label ] ) {
		$counts = [];
		foreach ( $projects as $p ) {
			foreach ( wp_get_post_terms( $p->ID, $tax, [ 'fields' => 'slugs' ] ) as $slug ) {
				$counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1;
			}
		}
		$terms = $tax === 'log_domain' ? log_ordered_terms( $tax, true ) : get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true ] );
		if ( ! is_array( $terms ) ) {
			continue;
		}
		if ( $tax === 'log_stage' ) { // 1, 2, 3, 4, then completed
			$rank = array_flip( array_keys( log_stage_terms() ) );
			usort( $terms, static fn( $a, $b ) => ( $rank[ $a->slug ] ?? 99 ) <=> ( $rank[ $b->slug ] ?? 99 ) );
		}
		$options = [];
		foreach ( $terms as $t ) {
			if ( ! empty( $counts[ $t->slug ] ) ) {
				$options[] = [ 'value' => rawurldecode( $t->slug ), 'name' => $t->name, 'count' => $counts[ $t->slug ] ];
			}
		}
		if ( $options ) {
			$out[ $param ] = [ 'label' => $label, 'options' => $options ];
		}
	}
	return $out;
}

/**
 * The two offices from the settings page, in display order. Offices without an address or phone are skipped.
 *
 * @return array<int, array{name:string, address:string, phone:string, email:string, nav:string}>
 */
function log_offices(): array {
	$out = [];
	foreach ( [ 'tlv' => 'תל אביב', 'jlm' => 'ירושלים' ] as $key => $name ) {
		$office = [
			'name'    => $name,
			'address' => (string) log_option( "{$key}_address", '' ),
			'phone'   => (string) log_option( "{$key}_phone", '' ),
			'email'   => (string) log_option( "{$key}_email", '' ),
			'nav'     => (string) log_option( "{$key}_nav_url", '' ),
		];
		if ( $office['address'] || $office['phone'] ) {
			$out[] = $office;
		}
	}
	return $out;
}

/**
 * Header and footer links (spec v6 §6), in the spec's order. Fixed in code so the order can't drift.
 * Each: [label, url, is_current].
 *
 * @return array<int, array{0:string, 1:string, 2:bool}>
 */
function log_nav_items(): array {
	$page = static fn( string $slug ) => ( $p = get_page_by_path( $slug ) ) ? get_permalink( $p ) : home_url( "/$slug/" );
	return [
		[ 'פרויקטים', get_post_type_archive_link( 'log_project' ), is_post_type_archive( 'log_project' ) || is_singular( 'log_project' ) ],
		[ 'חברת הבקרה', $page( 'control' ), is_page( 'control' ) ],
		[ 'אודות', $page( 'about' ), is_page( 'about' ) ],
		[ 'מרכז ידע', get_post_type_archive_link( 'log_knowledge' ), is_post_type_archive( 'log_knowledge' ) || is_singular( 'log_knowledge' ) ],
		[ 'קריירה', get_post_type_archive_link( 'log_job' ), is_post_type_archive( 'log_job' ) || is_singular( 'log_job' ) ],
		[ 'צור קשר', $page( 'contact' ), is_page( 'contact' ) ],
	];
}

/**
 * The sheet index (מפתח גיליונות): Israel's national grid with every public project as a point,
 * drawn from the projects' real coordinates. Five reference cities give the eye a hold.
 * One unit = 1 km of ITM; the frame spans E 130–270 km, N 380–800 km.
 */
function log_sheet_index_svg(): string {
	$e0 = 130; $e1 = 270; $n0 = 380; $n1 = 800; $w = $e1 - $e0; $h = $n1 - $n0;
	$x  = static fn( float $e ) => round( $e / 1000 - $e0, 2 );
	$y  = static fn( float $n ) => round( $n1 - $n / 1000, 2 );

	$grid = '';
	for ( $e = $e0; $e <= $e1; $e += 20 ) {
		$grid .= sprintf( '<line x1="%1$d" y1="0" x2="%1$d" y2="%2$d"/>', $e - $e0, $h );
	}
	for ( $n = $n0; $n <= $n1; $n += 20 ) {
		$grid .= sprintf( '<line x1="0" y1="%1$d" x2="%2$d" y2="%1$d"/>', $n1 - $n, $w );
	}
	$labels = '';
	for ( $n = 400; $n < $n1; $n += 100 ) {
		$labels .= sprintf( '<text x="-3" y="%d" text-anchor="end" dominant-baseline="middle">%d</text>', $n1 - $n, $n );
	}
	for ( $e = 150; $e < $e1; $e += 50 ) {
		$labels .= sprintf( '<text x="%d" y="%d" text-anchor="middle">%d</text>', $e - $e0, $h + 9, $e );
	}

	$cities = '';
	foreach ( [ 'חיפה' => [ 32.794, 34.989 ], 'תל אביב' => [ 32.08, 34.78 ], 'ירושלים' => [ 31.778, 35.235 ], 'באר שבע' => [ 31.252, 34.791 ], 'אילת' => [ 29.557, 34.951 ] ] as $name => [ $lat, $lng ] ) {
		[ $e, $n ] = log_itm( $lat, $lng );
		$cities .= sprintf( '<g class="sheet-index__city"><circle cx="%1$s" cy="%2$s" r="1.1"/><text x="%3$s" y="%2$s" dominant-baseline="middle">%4$s</text></g>', $x( $e ), $y( $n ), $x( $e ) - 3, esc_html( $name ) );
	}

	$dots = '';
	$count = 0;
	foreach ( log_public_projects() as $p ) {
		$itm = log_itm( log_field( 'lat', $p->ID ), log_field( 'lng', $p->ID ) );
		if ( ! $itm || log_field( 'is_national', $p->ID ) ) {
			continue;
		}
		$dots .= sprintf( '<circle cx="%s" cy="%s" r="1.9"/>', $x( $itm[0] ), $y( $itm[1] ) );
		$count++;
	}

	return sprintf(
		'<svg class="sheet-index__svg" viewBox="-14 -6 %1$d %2$d" role="img" aria-label="%3$s"><g class="sheet-index__grid">%4$s</g><rect class="sheet-index__frame" x="0" y="0" width="%5$d" height="%6$d"/><g class="sheet-index__labels">%7$s</g>%8$s<g class="sheet-index__dots">%9$s</g></svg>',
		$w + 20, $h + 20,
		esc_attr( sprintf( 'מפתח גיליונות: %d פרויקטים של לוג על רשת ישראל, מהגליל ועד אילת', $count ) ),
		$grid, $w, $h, $labels, $cities, $dots
	);
}

/** Whether any client logo is approved for the site (the clients block hides for visitors when none is). */
function log_has_clients(): bool {
	static $has = null;
	if ( $has === null ) {
		$has = (bool) get_posts( [ 'post_type' => 'log_client', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'logo_approved', 'meta_value' => '1' ] );
	}
	return $has;
}

/** An empty block shows to editors (with what goes there) and disappears for visitors. */
function log_show_empty(): bool {
	return current_user_can( 'edit_pages' );
}
