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
 * The route number of the current page: its place in the header's order (01 פרויקטים … 06 צור קשר), the same
 * number its row carries in the menu. Item pages take their area's number; home is 00; anything else has none.
 */
function log_route_number(): ?int {
	if ( is_front_page() ) {
		return 0;
	}
	foreach ( log_nav_items() as $i => [ , , $current ] ) {
		if ( $current ) {
			return $i + 1;
		}
	}
	return null;
}

/** A route shield (works orange, navy keyline) for a heading; the current page's number by default. */
function log_route_shield( ?int $n = -1 ): string {
	$n = $n === -1 ? log_route_number() : $n;
	return $n === null ? '' : sprintf( '<span class="shield" aria-hidden="true">%02d</span>', $n );
}

/** The distance plate at the end of a title band ("94 פרויקטים"); nothing when there is nothing to count. */
function log_sheet_label( string $extra = '' ): string {
	return $extra !== '' ? sprintf( '<p class="sheet-label">%s</p>', esc_html( $extra ) ) : '';
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
 * The menu's distance column: what lies down each road, in the same order as log_nav_items().
 * Counts are live; a road with nothing to count gets a short line instead.
 *
 * @return array<int, string>
 */
function log_nav_distances(): array {
	static $out = null; // the menu and the footer both read it
	if ( $out !== null ) {
		return $out;
	}
	$plural = static fn( int $n, string $one, string $many ) => $n === 1 ? $one : sprintf( $many, number_format_i18n( $n ) );
	$jobs   = count( get_posts( [ 'post_type' => 'log_job', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => 'is_open', 'meta_value' => '1', 'no_found_rows' => true ] ) );
	$items  = (int) ( wp_count_posts( 'log_knowledge' )->publish ?? 0 );
	$phone  = log_offices()[0]['phone'] ?? '';
	return $out = [
		$plural( count( log_public_projects() ), 'פרויקט אחד', '%s פרויקטים' ),
		'הבקרה הלאומית בהתחדשות עירונית',
		'האנשים, הערכים, המשרדים',
		$items ? $plural( $items, 'פריט אחד', '%s פריטים' ) : 'מאמרים, ניירות עמדה, חדשות',
		$jobs ? $plural( $jobs, 'משרה פתוחה אחת', '%s משרות פתוחות' ) : 'אין משרות פתוחות כרגע',
		$phone ?: 'תל אביב · ירושלים',
	];
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
