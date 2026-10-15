<?php
/**
 * Tools → ייבוא מ-ArcGIS.
 * Reads a GeoJSON export of LOG's ArcGIS layer `blue_lines_main` and upserts log_project posts.
 * Field mapping, coded domains and geometry rules: docs/arcgis-mapping.md.
 *
 * Rules:
 * - Match by ACF `arcgis_id` (= OBJECTID); fallback match on `plan_number` when OBJECTID is missing.
 * - New posts are drafts with approved=0. Re-imports touch only ArcGIS-sourced fields,
 *   never story/media/approved/featured.
 * - Geometry: centroid → lat/lng, Douglas-Peucker simplification → boundary, geodesic area → dunam.
 *   Shape__Area is ignored (Web Mercator inflates it ~1.4× at this latitude).
 */

defined( 'ABSPATH' ) || exit;

const LOG_IMPORT_MAX_BYTES = 20 * 1024 * 1024;
const LOG_IMPORT_TOLERANCE = 0.00005; // degrees, ≈5 m

add_action( 'admin_menu', function () {
	add_management_page( 'ייבוא מ-ArcGIS', 'ייבוא מ-ArcGIS', 'manage_options', 'log-arcgis-import', 'log_import_page' );
} );

/* -------------------------------------------------------------------------
 * Coded domains → stored values.
 * The GeoJSON export carries codes ("02"), not labels. Each table also accepts
 * the label itself, in case the file was exported with labels.
 * ---------------------------------------------------------------------- */

function log_import_milestone_codes(): array {
	return [
		'01' => 'טרום תכנון',
		'02' => 'טרום סטטוטורי',
		'03' => 'המלצה להפקדה',
		'04' => 'קליטה במחוזית',
		'05' => 'החלטה להפקדה',
		'06' => 'פרסום הפקדה',
		'07' => 'דיון בהתנגדויות',
		'08' => 'החלטה למתן תוקף',
		'09' => 'מתן תוקף',
		'11' => 'אחר',
	];
}

function log_import_status_codes(): array {
	return [ '01' => 'בתהליך', '02' => 'הושלם', '03' => 'הופסק', '99' => 'מבוטל' ];
}

/**
 * מזמין_העבודה: code → [client name, body_type]. The full domain from the layer definition
 * (scanned 15/10/2026, docs/arcgis-mapping.md), with display names cleaned up for the site.
 * Code 00 ("אחר") deliberately has no client. Filter: log_arcgis_client_labels.
 */
function log_import_client_codes(): array {
	return apply_filters( 'log_arcgis_client_labels', [
		'99' => [ 'הרשות הממשלתית להתחדשות עירונית', 'government' ],
		'14' => [ 'רשות מקרקעי ישראל', 'government' ],
		'2'  => [ 'משרד הבינוי והשיכון', 'government' ],
		'3'  => [ 'מנהל התכנון', 'government' ],
		'18' => [ 'דירה להשכיר', 'government' ],
		'8'  => [ 'עיריית בת ים', 'authority' ],
		'9'  => [ 'עיריית חדרה', 'authority' ],
		'10' => [ 'עיריית חולון', 'authority' ],
		'11' => [ 'עיריית נתיבות', 'authority' ],
		'16' => [ 'המועצה האזורית רמת נגב', 'authority' ],
		'4'  => [ 'אאורה', 'developer' ],
		'5'  => [ 'תדהר', 'developer' ],
		'6'  => [ 'לוינסקי-עופר', 'developer' ],
		'7'  => [ 'חנן מור', 'developer' ],
		'12' => [ 'צרפתי', 'developer' ],
		'15' => [ 'תצפית', 'developer' ],
		'17' => [ 'אביב שני', 'developer' ],
	] );
}

/**
 * Some features carry the label instead of the code (and one carries a label that isn't in the
 * domain at all). Map labels back to the same client the code would give, so one body = one client.
 */
function log_import_client_from_label( string $label ): ?array {
	$label = trim( $label );
	foreach ( log_import_client_codes() as [ $name, $type ] ) {
		if ( $name === $label ) {
			return [ $name, $type ];
		}
	}
	$aliases = [ 'מינהל התכנון' => '3', 'רמ"י' => '14' ];
	if ( isset( $aliases[ $label ] ) ) {
		return log_import_client_codes()[ $aliases[ $label ] ];
	}
	if ( str_starts_with( $label, 'יזם - ' ) ) {
		return [ trim( mb_substr( $label, 6 ) ), 'developer' ];
	}
	if ( str_starts_with( $label, 'רשות מקומית - ' ) ) {
		return [ 'עיריית ' . trim( mb_substr( $label, 14 ) ), 'authority' ];
	}
	return null;
}

function log_import_client_body_type( string $code, string $kind ): string {
	if ( in_array( $code, [ '8', '9', '10', '11', '16' ], true ) ) {
		return 'authority';
	}
	if ( in_array( $code, [ '4', '5', '6', '7', '12', '15', '17' ], true ) ) {
		return 'developer';
	}
	return match ( $kind ) {
		'01', 'ציבורי' => 'government',
		'02', 'פרטי'  => 'developer',
		default        => 'other',
	};
}

/** "2" → "02", 99 → "99", "" → "". Labels pass through untouched. */
function log_import_code( $value ): string {
	$value = trim( (string) ( $value ?? '' ) );
	if ( $value === '' || ! ctype_digit( $value ) ) {
		return $value;
	}
	return str_pad( $value, 2, '0', STR_PAD_LEFT );
}

/** Resolve a code-or-label against a table; returns the code, or null when unknown. */
function log_import_resolve( $value, array $table ): ?string {
	$code = log_import_code( $value );
	if ( $code === '' ) {
		return null;
	}
	if ( isset( $table[ $code ] ) ) {
		return $code;
	}
	$found = array_search( $code, $table, true );
	return $found === false ? null : (string) $found;
}

/** Free-text domains (district, plan type, institution): keep the label if it is one of the ACF choices. */
function log_import_choice( $value, array $choices ): ?string {
	$value = trim( (string) ( $value ?? '' ) );
	if ( $value === '' ) {
		return null;
	}
	$norm = str_replace( [ '״', '”', '“' ], '"', $value );
	foreach ( $choices as $choice ) {
		if ( str_replace( '״', '"', $choice ) === $norm ) {
			return $choice;
		}
	}
	return null;
}

/* -------------------------------------------------------------------------
 * Geometry
 * ---------------------------------------------------------------------- */

/** Web Mercator → WGS84, in case the export was not reprojected. */
function log_import_to_wgs84( array $pt ): array {
	[ $x, $y ] = $pt;
	if ( abs( $x ) <= 180 && abs( $y ) <= 90 ) {
		return [ (float) $x, (float) $y ];
	}
	$r   = 6378137.0;
	$lng = rad2deg( $x / $r );
	$lat = rad2deg( 2 * atan( exp( $y / $r ) ) - M_PI / 2 );
	return [ $lng, $lat ];
}

/** Returns a list of polygons, each a list of rings, each a list of [lng, lat]. */
function log_import_polygons( ?array $geometry ): array {
	if ( ! $geometry || empty( $geometry['coordinates'] ) ) {
		return [];
	}
	$polys = match ( $geometry['type'] ?? '' ) {
		'Polygon'      => [ $geometry['coordinates'] ],
		'MultiPolygon' => $geometry['coordinates'],
		default        => [],
	};
	$out = [];
	foreach ( $polys as $rings ) {
		$clean = [];
		foreach ( $rings as $ring ) {
			$pts = [];
			foreach ( $ring as $pt ) {
				if ( is_array( $pt ) && count( $pt ) >= 2 && is_numeric( $pt[0] ) && is_numeric( $pt[1] ) ) {
					$pts[] = log_import_to_wgs84( $pt );
				}
			}
			if ( count( $pts ) >= 4 ) {
				$clean[] = $pts;
			}
		}
		if ( $clean ) {
			$out[] = $clean;
		}
	}
	return $out;
}

/** Planar shoelace area in degree², signed. Enough for centroid weighting of small polygons. */
function log_import_planar_area( array $ring ): float {
	$sum = 0.0;
	$n   = count( $ring );
	for ( $i = 0; $i < $n - 1; $i++ ) {
		$sum += $ring[ $i ][0] * $ring[ $i + 1 ][1] - $ring[ $i + 1 ][0] * $ring[ $i ][1];
	}
	return $sum / 2;
}

/** Area-weighted centroid of the outer rings → [lat, lng]. */
function log_import_centroid( array $polygons ): ?array {
	$cx = 0.0;
	$cy = 0.0;
	$ca = 0.0;
	foreach ( $polygons as $rings ) {
		$ring = $rings[0];
		$a    = log_import_planar_area( $ring );
		if ( abs( $a ) < 1e-12 ) {
			continue;
		}
		$x = 0.0;
		$y = 0.0;
		$n = count( $ring );
		for ( $i = 0; $i < $n - 1; $i++ ) {
			$f  = $ring[ $i ][0] * $ring[ $i + 1 ][1] - $ring[ $i + 1 ][0] * $ring[ $i ][1];
			$x += ( $ring[ $i ][0] + $ring[ $i + 1 ][0] ) * $f;
			$y += ( $ring[ $i ][1] + $ring[ $i + 1 ][1] ) * $f;
		}
		$cx += $x / ( 6 * $a ) * abs( $a );
		$cy += $y / ( 6 * $a ) * abs( $a );
		$ca += abs( $a );
	}
	if ( $ca <= 0 ) {
		return null;
	}
	return [ round( $cy / $ca, 6 ), round( $cx / $ca, 6 ) ];
}

/**
 * Geodesic ring area in m² on a sphere (Chamberlain & Duquette, as in turf.js).
 * Sign follows ring orientation; callers take abs().
 */
function log_import_ring_area( array $ring ): float {
	$n = count( $ring );
	if ( $n < 3 ) {
		return 0.0;
	}
	$total = 0.0;
	for ( $i = 0; $i < $n; $i++ ) {
		$p1 = $ring[ $i ];
		$p2 = $ring[ ( $i + 1 ) % $n ];
		$p3 = $ring[ ( $i + 2 ) % $n ];
		$total += ( deg2rad( $p3[0] ) - deg2rad( $p1[0] ) ) * sin( deg2rad( $p2[1] ) );
	}
	return $total * 6378137.0 * 6378137.0 / 2;
}

/** Total area in m²: outer rings minus holes. Rings are closed (first == last), so drop the duplicate. */
function log_import_area_m2( array $polygons ): float {
	$area = 0.0;
	foreach ( $polygons as $rings ) {
		foreach ( $rings as $i => $ring ) {
			$open = $ring;
			if ( $open[0] == $open[ count( $open ) - 1 ] ) {
				array_pop( $open );
			}
			$a = abs( log_import_ring_area( $open ) );
			$area += $i === 0 ? $a : -$a;
		}
	}
	return max( 0.0, $area );
}

/** Douglas-Peucker on a closed ring, iterative. Keeps at least a triangle. */
function log_import_simplify_ring( array $ring, float $tolerance ): array {
	$n = count( $ring );
	if ( $n <= 4 ) {
		return $ring;
	}
	$keep  = array_fill( 0, $n, false );
	$keep[0] = $keep[ $n - 1 ] = true;
	$stack = [ [ 0, $n - 1 ] ];
	$tol2  = $tolerance * $tolerance;

	while ( $stack ) {
		[ $first, $last ] = array_pop( $stack );
		$max   = 0.0;
		$index = -1;
		for ( $i = $first + 1; $i < $last; $i++ ) {
			$d = log_import_seg_dist2( $ring[ $i ], $ring[ $first ], $ring[ $last ] );
			if ( $d > $max ) {
				$max   = $d;
				$index = $i;
			}
		}
		if ( $index > 0 && $max > $tol2 ) {
			$keep[ $index ] = true;
			$stack[]        = [ $first, $index ];
			$stack[]        = [ $index, $last ];
		}
	}

	$out = [];
	foreach ( $ring as $i => $pt ) {
		if ( $keep[ $i ] ) {
			$out[] = $pt;
		}
	}
	// A closed ring with the first point repeated needs ≥4 entries; otherwise keep the original.
	return count( $out ) >= 4 ? $out : $ring;
}

/** Squared perpendicular distance from p to segment a–b (planar, in degrees). */
function log_import_seg_dist2( array $p, array $a, array $b ): float {
	$x  = $a[0];
	$y  = $a[1];
	$dx = $b[0] - $x;
	$dy = $b[1] - $y;
	if ( $dx != 0 || $dy != 0 ) {
		$t = ( ( $p[0] - $x ) * $dx + ( $p[1] - $y ) * $dy ) / ( $dx * $dx + $dy * $dy );
		if ( $t > 1 ) {
			$x = $b[0];
			$y = $b[1];
		} elseif ( $t > 0 ) {
			$x += $dx * $t;
			$y += $dy * $t;
		}
	}
	$dx = $p[0] - $x;
	$dy = $p[1] - $y;
	return $dx * $dx + $dy * $dy;
}

/** Simplified GeoJSON geometry (Polygon or MultiPolygon) with 6-decimal coordinates. */
function log_import_simplified_geometry( array $polygons, float $tolerance ): array {
	$out = [];
	foreach ( $polygons as $rings ) {
		$simple = [];
		foreach ( $rings as $ring ) {
			$simple[] = array_map(
				static fn( array $pt ) => [ round( $pt[0], 6 ), round( $pt[1], 6 ) ],
				log_import_simplify_ring( $ring, $tolerance )
			);
		}
		$out[] = $simple;
	}
	return count( $out ) === 1
		? [ 'type' => 'Polygon', 'coordinates' => $out[0] ]
		: [ 'type' => 'MultiPolygon', 'coordinates' => $out ];
}

/** Everything the importer derives from one feature's geometry, or null when it has none. */
function log_import_geometry( ?array $geometry ): ?array {
	$polygons = log_import_polygons( $geometry );
	if ( ! $polygons ) {
		return null;
	}
	$points = 0;
	foreach ( $polygons as $rings ) {
		foreach ( $rings as $ring ) {
			$points += count( $ring );
		}
	}
	$simplified = log_import_simplified_geometry( $polygons, LOG_IMPORT_TOLERANCE );
	$kept       = 0;
	foreach ( log_import_polygons( $simplified ) as $rings ) {
		foreach ( $rings as $ring ) {
			$kept += count( $ring );
		}
	}
	return [
		'centroid'   => log_import_centroid( $polygons ),
		'area_m2'    => log_import_area_m2( $polygons ),
		'boundary'   => wp_json_encode( $simplified, JSON_UNESCAPED_SLASHES ),
		'points'     => $points,
		'kept'       => $kept,
	];
}

/**
 * The only domain inference the data supports (docs/arcgis-mapping.md): client 99 → urban renewal,
 * plan type מתארית → master plans. Applied on first import only; editors own the domain afterwards.
 */
function log_import_infer_domain( string $client_code, string $plan_type ): ?string {
	if ( $client_code === '99' ) {
		return 'urban-renewal';
	}
	if ( $plan_type === 'מתארית' ) {
		return 'master-plans';
	}
	return null;
}

/* -------------------------------------------------------------------------
 * Upsert
 * ---------------------------------------------------------------------- */

function log_import_find_project( string $arcgis_id, string $plan_number ): ?int {
	$find = static function ( string $key, string $value ): ?int {
		if ( $value === '' ) {
			return null;
		}
		$ids = get_posts( [
			'post_type'      => 'log_project',
			'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [ [ 'key' => $key, 'value' => $value ] ],
		] );
		return $ids ? (int) $ids[0] : null;
	};
	// The plan number is only a fallback for features without an OBJECTID: LOG's layer has two
	// different plans sharing one number (602-1306075), and matching on it would merge them.
	if ( $arcgis_id !== '' ) {
		return $find( 'arcgis_id', $arcgis_id );
	}
	return $find( 'plan_number', $plan_number );
}

/** Find-or-create a log_client by name. Returns the post ID, or 0 in a dry run when it would be created. */
function log_import_client( string $name, string $body_type, bool $dry, array &$notes ): int {
	$existing = get_posts( [
		'post_type'      => 'log_client',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'title'          => $name,
	] );
	if ( $existing ) {
		return (int) $existing[0];
	}
	$notes[] = "לקוח חדש: $name";
	if ( $dry ) {
		return 0;
	}
	$id = wp_insert_post( [
		'post_type'   => 'log_client',
		'post_status' => 'publish',
		'post_title'  => $name,
	], true );
	if ( is_wp_error( $id ) ) {
		$notes[] = 'יצירת הלקוח נכשלה: ' . $id->get_error_message();
		return 0;
	}
	log_set_field( 'body_type', $body_type, $id );
	return (int) $id;
}

/**
 * Import one feature. Returns a summary row:
 * [ 'action' => created|updated|skipped, 'title', 'objectid', 'post_id', 'notes' => string[] ].
 */
function log_import_feature( array $feature, bool $dry ): array {
	$p     = $feature['properties'] ?? [];
	$notes = [];
	$get   = static fn( string $key ) => isset( $p[ $key ] ) ? trim( (string) $p[ $key ] ) : '';

	$objectid = $get( 'OBJECTID' );
	if ( $objectid === '' && isset( $feature['id'] ) && is_scalar( $feature['id'] ) ) {
		$objectid = (string) $feature['id'];
	}
	$title       = $get( 'שם_התוכנית' );
	$plan_number = $get( 'מספר_התוכנית' );
	$row         = [ 'action' => 'skipped', 'title' => $title, 'objectid' => $objectid, 'post_id' => 0, 'notes' => &$notes ];

	if ( $title === '' ) {
		$notes[] = 'אין שם תכנית';
		return $row;
	}
	if ( $objectid === '' && $plan_number === '' ) {
		$notes[] = 'אין OBJECTID ואין מספר תכנית';
		return $row;
	}

	// Scalar fields.
	$fields = [
		'plan_number' => $plan_number,
		'units'       => $get( 'יחד_מוצע' ) === '' ? null : (int) $get( 'יחד_מוצע' ),
	];

	$city = $get( 'רשות_מקומית' );
	if ( $city === 'לא רלוונטי' ) {
		$city = '';
	}
	$fields['city'] = $city;

	$milestone = log_import_resolve( $get( 'אבן_דרך' ), log_import_milestone_codes() );
	if ( $milestone === null && $get( 'אבן_דרך' ) !== '' ) {
		$notes[] = 'אבן דרך לא מזוהה: ' . $get( 'אבן_דרך' );
	}
	$fields['milestone'] = $milestone ?? '';

	$status = log_import_resolve( $get( 'סטטוס' ), log_import_status_codes() );
	if ( $status === null && $get( 'סטטוס' ) !== '' ) {
		$notes[] = 'סטטוס לא מזוהה: ' . $get( 'סטטוס' );
	}
	$fields['status'] = $status ?? '01';

	foreach ( [
		'plan_type'   => [ 'סוג_תכנית', [ 'מפורטת', 'מתארית', 'מדיניות', 'אחר' ] ],
		'institution' => [ 'M_Tichnun', [ 'ועדה מחוזית', 'ועדה מקומית', 'ותמ"ל' ] ],
		'district'    => [ 'מחוז', [ 'דרום', 'תל אביב', 'מרכז', 'צפון', 'חיפה', 'ירושלים' ] ],
	] as $field => [ $source, $choices ] ) {
		$value = log_import_choice( $get( $source ), $choices );
		if ( $value === null && $get( $source ) !== '' ) {
			$notes[] = "$source לא מזוהה: " . $get( $source );
		}
		$fields[ $field ] = $value ?? '';
	}

	// Client.
	$client_raw  = $get( 'מזמין_העבודה' );
	$client_name = '';
	$body_type   = 'other';
	$code        = '';
	if ( $client_raw !== '' ) {
		$code  = ctype_digit( $client_raw ) ? ( ltrim( $client_raw, '0' ) ?: '0' ) : $client_raw;
		$table = log_import_client_codes();
		if ( isset( $table[ $code ] ) ) {
			[ $client_name, $body_type ] = $table[ $code ];
		} elseif ( ! ctype_digit( $client_raw ) ) {
			// Label export: normalise to the same client the code would give.
			[ $client_name, $body_type ] = log_import_client_from_label( $client_raw )
				?? [ $client_raw, log_import_client_body_type( '', log_import_code( $get( 'סוג_מזמין' ) ) ) ];
		} elseif ( $code === '0' ) {
			$notes[] = 'מזמין "אחר", הלקוח לא שויך';
		} else {
			$notes[] = "מזמין לא מזוהה (קוד $code), הלקוח לא שויך";
		}
		if ( $client_name !== '' && $body_type === 'other' ) {
			$body_type = log_import_client_body_type( $code, log_import_code( $get( 'סוג_מזמין' ) ) );
		}
	}

	// Geometry.
	$geo = log_import_geometry( $feature['geometry'] ?? null );
	if ( $geo ) {
		if ( $geo['centroid'] ) {
			[ $fields['lat'], $fields['lng'] ] = $geo['centroid'];
		}
		$fields['dunam']    = (int) round( $geo['area_m2'] / 1000 );
		$fields['boundary'] = $geo['boundary'];
		$notes[]            = sprintf( 'גיאומטריה: %d → %d נקודות, %s דונם', $geo['points'], $geo['kept'], number_format_i18n( $fields['dunam'] ) );
	} else {
		$notes[] = 'אין גיאומטריה';
	}

	$post_id        = log_import_find_project( $objectid, $plan_number );
	$row['action']  = $post_id ? 'updated' : 'created';
	$domain         = $post_id ? null : log_import_infer_domain( $code, $fields['plan_type'] );
	if ( $domain ) {
		$term    = get_term_by( 'slug', $domain, 'log_domain' );
		$notes[] = 'תחום: ' . ( $term ? $term->name : $domain );
	}
	$row['post_id'] = (int) $post_id;
	if ( $post_id ) {
		$notes[] = 'תכנית קיימת #' . $post_id;
	}

	$client_id = $client_name !== '' ? log_import_client( $client_name, $body_type, $dry, $notes ) : 0;
	if ( $city !== '' && ! term_exists( $city, 'log_city' ) ) {
		$notes[] = "עיר חדשה: $city";
	}

	if ( $dry ) {
		return $row;
	}

	if ( $post_id ) {
		wp_update_post( [ 'ID' => $post_id, 'post_title' => $title ] );
	} else {
		$post_id = wp_insert_post( [
			'post_type'   => 'log_project',
			'post_status' => 'draft',
			'post_title'  => $title,
		], true );
		if ( is_wp_error( $post_id ) ) {
			$row['action'] = 'skipped';
			$notes[]       = 'יצירת הפוסט נכשלה: ' . $post_id->get_error_message();
			return $row;
		}
		log_set_field( 'approved', 0, $post_id );
		$row['post_id'] = (int) $post_id;
		if ( $domain && term_exists( $domain, 'log_domain' ) ) {
			wp_set_object_terms( $post_id, $domain, 'log_domain', false );
		}
	}

	if ( $objectid !== '' ) {
		$fields['arcgis_id'] = $objectid;
	}
	foreach ( $fields as $name => $value ) {
		log_set_field( $name, $value, $post_id );
	}
	if ( $client_id ) {
		log_set_field( 'client', $client_id, $post_id );
	}
	if ( $city !== '' ) {
		wp_set_object_terms( $post_id, $city, 'log_city', false );
	}
	log_project_recompute_derived( (int) $post_id );

	return $row;
}

/** Runs the whole file. Returns [ 'rows' => [...], 'counts' => [created, updated, skipped] ]. */
function log_import_run( array $collection, bool $dry ): array {
	$rows   = [];
	$counts = [ 'created' => 0, 'updated' => 0, 'skipped' => 0 ];
	foreach ( $collection['features'] ?? [] as $i => $feature ) {
		if ( ! is_array( $feature ) ) {
			continue;
		}
		$row    = log_import_feature( $feature, $dry );
		$row['index'] = $i + 1;
		$rows[] = $row;
		$counts[ $row['action'] ]++;
	}
	if ( ! $dry ) {
		log_flush_map_cache();
	}
	return [ 'rows' => $rows, 'counts' => $counts ];
}

/* -------------------------------------------------------------------------
 * Admin page
 * ---------------------------------------------------------------------- */

function log_import_read_upload(): array|WP_Error {
	$file = $_FILES['log_geojson'] ?? null;
	if ( ! $file || ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) === UPLOAD_ERR_NO_FILE ) {
		return new WP_Error( 'no_file', 'לא נבחר קובץ.' );
	}
	if ( $file['error'] !== UPLOAD_ERR_OK ) {
		return new WP_Error( 'upload', 'העלאה נכשלה (קוד ' . (int) $file['error'] . '). בדקו את מגבלת upload_max_filesize בשרת.' );
	}
	if ( $file['size'] > LOG_IMPORT_MAX_BYTES ) {
		return new WP_Error( 'too_big', 'הקובץ גדול מ-20MB.' );
	}
	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'upload', 'הקובץ לא התקבל.' );
	}
	$json = file_get_contents( $file['tmp_name'] );
	$data = json_decode( $json, true );
	if ( ! is_array( $data ) || ( $data['type'] ?? '' ) !== 'FeatureCollection' || ! isset( $data['features'] ) ) {
		return new WP_Error( 'format', 'זה לא GeoJSON מסוג FeatureCollection.' );
	}
	return $data;
}

function log_import_page(): void {
	$result = null;
	$error  = null;
	$dry    = true;

	if ( isset( $_POST['log_import'] ) && check_admin_referer( 'log_arcgis_import' ) ) {
		$dry  = ! empty( $_POST['log_dry_run'] );
		$data = log_import_read_upload();
		if ( is_wp_error( $data ) ) {
			$error = $data->get_error_message();
		} else {
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 );
			}
			$result = log_import_run( $data, $dry );
		}
	}

	echo '<div class="wrap"><h1>ייבוא מ-ArcGIS</h1>';
	if ( $error ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	}
	if ( $result ) {
		log_import_render_summary( $result, $dry );
	}

	echo '<p>קובץ GeoJSON שיוצא מהשכבה <code>blue_lines_main</code> (ArcGIS Online → Export → GeoJSON). ';
	echo 'תכניות מזוהות לפי OBJECTID, ובלעדיו לפי מספר תכנית. תכניות חדשות נוצרות כטיוטה ולא מאושרות לפרסום; ';
	echo 'ייבוא חוזר מעדכן רק את שדות ה-ArcGIS ולא נוגע בסיפור, במדיה או באישור הפרסום. ';
	echo 'הפירוט המלא ב-<code>docs/arcgis-mapping.md</code>.</p>';
	echo '<form method="post" enctype="multipart/form-data">';
	wp_nonce_field( 'log_arcgis_import' );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row"><label for="log_geojson">קובץ GeoJSON</label></th><td>';
	echo '<input type="file" name="log_geojson" id="log_geojson" accept=".geojson,.json,application/geo+json,application/json" required> ';
	echo '<p class="description">עד 20MB. מגבלת השרת: ' . esc_html( size_format( wp_max_upload_size() ) ) . '.</p></td></tr>';
	echo '<tr><th scope="row">הרצה יבשה</th><td><label><input type="checkbox" name="log_dry_run" value="1" checked> ';
	echo 'רק להציג מה היה קורה, בלי לכתוב כלום</label></td></tr>';
	echo '</tbody></table>';
	submit_button( 'ייבוא', 'primary', 'log_import' );
	echo '</form></div>';
}

function log_import_render_summary( array $result, bool $dry ): void {
	$c      = $result['counts'];
	$labels = [ 'created' => 'נוצרו', 'updated' => 'עודכנו', 'skipped' => 'דולגו' ];
	$class  = $dry ? 'notice-info' : 'notice-success';
	$head   = $dry ? 'הרצה יבשה, לא נכתב כלום.' : 'הייבוא הושלם ומטמון המפה נוקה.';

	echo "<div class=\"notice $class\"><p><strong>" . esc_html( $head ) . '</strong> ';
	echo esc_html( sprintf( 'נוצרו %d · עודכנו %d · דולגו %d', $c['created'], $c['updated'], $c['skipped'] ) );
	echo '</p></div>';

	echo '<table class="widefat striped" style="margin-bottom:2em"><thead><tr>';
	echo '<th style="width:3em">#</th><th style="width:6em">OBJECTID</th><th>תכנית</th><th style="width:6em">פעולה</th><th>הערות</th>';
	echo '</tr></thead><tbody>';
	foreach ( $result['rows'] as $row ) {
		$title = $row['title'] !== '' ? $row['title'] : '(ללא שם)';
		if ( $row['post_id'] ) {
			$title = '<a href="' . esc_url( get_edit_post_link( $row['post_id'] ) ) . '">' . esc_html( $title ) . '</a>';
		} else {
			$title = esc_html( $title );
		}
		echo '<tr><td>' . (int) $row['index'] . '</td><td>' . esc_html( $row['objectid'] ) . '</td><td>' . $title . '</td>';
		echo '<td>' . esc_html( $labels[ $row['action'] ] ) . '</td>';
		echo '<td>' . esc_html( implode( ' · ', $row['notes'] ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/* -------------------------------------------------------------------------
 * Projects list: an "ArcGIS" column so imported plans are told apart from hand-made ones.
 * ---------------------------------------------------------------------- */

add_filter( 'manage_log_project_posts_columns', function ( array $columns ): array {
	$out = [];
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( $key === 'title' ) {
			$out['log_arcgis'] = 'ArcGIS';
		}
	}
	return $out;
} );

add_action( 'manage_log_project_posts_custom_column', function ( string $column, int $post_id ): void {
	if ( $column !== 'log_arcgis' ) {
		return;
	}
	$id = log_field( 'arcgis_id', $post_id );
	if ( $id === null ) {
		echo '<span aria-hidden="true">—</span>';
		return;
	}
	$status = log_import_status_codes()[ log_field( 'status', $post_id, '01' ) ] ?? '';
	echo '<span title="OBJECTID">#' . esc_html( $id ) . '</span>';
	if ( $status ) {
		echo '<br><span class="description">' . esc_html( $status ) . '</span>';
	}
}, 10, 2 );

add_filter( 'manage_edit-log_project_sortable_columns', function ( array $columns ): array {
	$columns['log_arcgis'] = 'log_arcgis';
	return $columns;
} );

add_action( 'pre_get_posts', function ( WP_Query $q ): void {
	if ( is_admin() && $q->is_main_query() && $q->get( 'orderby' ) === 'log_arcgis' ) {
		$q->set( 'meta_key', 'arcgis_id' );
		$q->set( 'orderby', 'meta_value_num' );
	}
} );
