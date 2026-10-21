<?php
/**
 * ArcGIS sync (spec v6 §18 "מקורות וסנכרון").
 *
 * Pulls LOG's layer `blue_lines_main` straight from its REST query endpoint (f=geojson), server-side,
 * once a week and on demand (Tools → ייבוא מ-ArcGIS → "סנכרון עכשיו"). Every feature goes through
 * the same importer as the manual upload (log_import_run in inc/import.php), so field mapping lives
 * in one place.
 *
 * - Credentials (API key, or ArcGIS username + password) live in the settings page, ArcGIS tab.
 *   They never reach the browser: the password fields render empty, and tokens stay in a transient.
 * - On failure nothing is written: the site keeps showing the last approved data, the error is logged
 *   and stored, and the admin gets an email.
 * - A plan that disappears from the layer is "closed in the source": an in-progress plan becomes
 *   status 02 (הושלם) and stays on the site. Taking it off the site is a separate, explicit act.
 * - In the project edit screen, everything the last sync wrote is read-only and marked "מסונכרן מ-ArcGIS".
 */

defined( 'ABSPATH' ) || exit;

// blue_lines_main is layer 0 of this service (checked 21/10/2026: readable without a token, 99 plans).
const LOG_ARCGIS_LAYER  = 'https://services-eu1.arcgis.com/lDcqcwePoeu47saG/ArcGIS/rest/services/שכבת_תוכניות___ראשי_gdb/FeatureServer/0';
const LOG_ARCGIS_PORTAL = 'https://www.arcgis.com';
const LOG_ARCGIS_PAGE   = 1000; // features per request; ArcGIS Online's default maxRecordCount is 1000–2000

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

/** The ArcGIS settings, trimmed. layer_url is empty until someone saves the ArcGIS tab. */
function log_arcgis_settings(): array {
	$get = static fn( string $name ) => trim( (string) log_option( $name, '' ) );
	return [
		'layer_url'   => $get( 'arcgis_layer_url' ),
		'api_key'     => $get( 'arcgis_api_key' ),
		'username'    => $get( 'arcgis_username' ),
		'password'    => (string) log_option( 'arcgis_password', '' ),
		'portal'      => rtrim( $get( 'arcgis_portal' ) ?: LOG_ARCGIS_PORTAL, '/' ),
		'alert_email' => $get( 'arcgis_alert_email' ),
	];
}

function log_arcgis_auth_mode( array $s ): string {
	if ( $s['api_key'] !== '' ) {
		return 'api_key';
	}
	return $s['username'] !== '' && $s['password'] !== '' ? 'user' : 'none';
}

/** Layer or query URL → the query endpoint. The service name is Hebrew, so non-ASCII parts are percent-encoded. */
function log_arcgis_query_url( string $layer ): string {
	$layer = untrailingslashit( preg_replace( '/[?#].*$/', '', $layer ) );
	$layer = preg_replace_callback( '/[^\x00-\x7F]+/', static fn( array $m ) => rawurlencode( $m[0] ), $layer );
	return str_ends_with( $layer, '/query' ) ? $layer : $layer . '/query';
}

/** Secret fields: the stored value is never rendered into the form; an empty submit keeps it. */
function log_arcgis_secret_fields(): array {
	return [ 'field_ls_arcgis_api_key' => 'arcgis_api_key', 'field_ls_arcgis_password' => 'arcgis_password' ];
}

foreach ( log_arcgis_secret_fields() as $key => $name ) {
	add_filter( "acf/prepare_field/key=$key", function ( $field ) use ( $name ) {
		if ( $field && log_option( $name ) ) {
			$field['placeholder']  = 'שמור. השאירו ריק כדי לא לשנות';
			$field['instructions'] = trim( ( $field['instructions'] ?? '' ) . ' כדי למחוק את הערך השמור, הזינו מקף (-).' );
		}
		if ( $field ) {
			$field['value'] = '';
		}
		return $field;
	} );
	add_filter( "acf/update_value/key=$key", function ( $value ) use ( $name ) {
		if ( ! isset( $_POST['acf'] ) ) {
			return $value; // log_set_field from code or from the fallback form: write as given
		}
		$value = trim( (string) $value );
		if ( $value === '-' ) {
			delete_transient( 'log_arcgis_token' );
			return '';
		}
		if ( $value === '' ) {
			return get_option( "options_$name", '' );
		}
		delete_transient( 'log_arcgis_token' );
		return $value;
	} );
}

/* -------------------------------------------------------------------------
 * Fetch
 * ---------------------------------------------------------------------- */

/** A token for username/password auth, cached server-side until shortly before it expires. */
function log_arcgis_token( array $s, bool $fresh = false ): string|WP_Error {
	$cached = get_transient( 'log_arcgis_token' );
	if ( ! $fresh && is_string( $cached ) && $cached !== '' ) {
		return $cached;
	}
	$res = wp_remote_post( $s['portal'] . '/sharing/rest/generateToken', [
		'timeout' => 30,
		'body'    => [
			'username'   => $s['username'],
			'password'   => $s['password'],
			'client'     => 'referer',
			'referer'    => home_url( '/' ),
			'expiration' => 120,
			'f'          => 'json',
		],
	] );
	$body = log_arcgis_decode( $res, 'כניסה ל-ArcGIS' );
	if ( is_wp_error( $body ) ) {
		return $body;
	}
	if ( empty( $body['token'] ) ) {
		return new WP_Error( 'arcgis_token', 'ArcGIS לא החזיר טוקן. בדקו את שם המשתמש, הסיסמה וכתובת הפורטל.' );
	}
	$ttl = isset( $body['expires'] ) ? (int) ( $body['expires'] / 1000 ) - time() - 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;
	set_transient( 'log_arcgis_token', $body['token'], max( MINUTE_IN_SECONDS, $ttl ) );
	return (string) $body['token'];
}

/** HTTP response → decoded JSON, or a WP_Error that says what went wrong in plain Hebrew. */
function log_arcgis_decode( $res, string $what ): array|WP_Error {
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'arcgis_http', "$what: שגיאת רשת: " . $res->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	// ArcGIS reports errors in the JSON body, usually with a 200 and sometimes with a 4xx.
	if ( ! isset( $body['error'] ) && ( $code < 200 || $code >= 300 ) ) {
		return new WP_Error( 'arcgis_http', "$what: השרת החזיר HTTP $code." );
	}
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'arcgis_json', "$what: התשובה אינה JSON." );
	}
	if ( isset( $body['error'] ) ) {
		$err  = (array) $body['error'];
		$msg  = trim( ( $err['message'] ?? 'שגיאה' ) . ' ' . implode( ' ', (array) ( $err['details'] ?? [] ) ) );
		return new WP_Error( 'arcgis_api', "$what: ArcGIS: $msg (קוד " . (int) ( $err['code'] ?? 0 ) . ')', [ 'code' => (int) ( $err['code'] ?? 0 ) ] );
	}
	return $body;
}

/**
 * The whole layer as one GeoJSON FeatureCollection, paging through the query endpoint.
 * Uses POST so the token never ends up in a URL or a server log.
 */
function log_arcgis_fetch( array $s ): array|WP_Error {
	$mode    = log_arcgis_auth_mode( $s );
	$token   = '';
	$retried = false;
	if ( $mode === 'api_key' ) {
		$token = $s['api_key'];
	} elseif ( $mode === 'user' ) {
		$token = log_arcgis_token( $s );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
	}

	$features = [];
	$offset   = 0;
	for ( $page = 0; $page < 50; $page++ ) {
		$body = [
			'where'             => '1=1',
			'outFields'         => '*',
			'returnGeometry'    => 'true',
			'outSR'             => '4326',
			'orderByFields'     => 'OBJECTID',
			'resultOffset'      => $offset,
			'resultRecordCount' => LOG_ARCGIS_PAGE,
			'f'                 => 'geojson',
		];
		if ( $token !== '' ) {
			$body['token'] = $token;
		}
		$res  = wp_remote_post( log_arcgis_query_url( $s['layer_url'] ), [
			'timeout' => 60,
			'headers' => [ 'Referer' => home_url( '/' ) ],
			'body'    => $body,
		] );
		$data = log_arcgis_decode( $res, 'שאילתת השכבה' );

		// Expired or rejected token (498/499): get a new one once and retry the same page.
		if ( is_wp_error( $data ) && $mode === 'user' && ! $retried && in_array( $data->get_error_data()['code'] ?? 0, [ 498, 499 ], true ) ) {
			$retried = true;
			$token   = log_arcgis_token( $s, true );
			if ( is_wp_error( $token ) ) {
				return $token;
			}
			$page--;
			continue;
		}
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( ( $data['type'] ?? '' ) !== 'FeatureCollection' || ! isset( $data['features'] ) || ! is_array( $data['features'] ) ) {
			return new WP_Error( 'arcgis_format', 'שאילתת השכבה: התשובה אינה GeoJSON מסוג FeatureCollection.' );
		}
		array_push( $features, ...$data['features'] );
		$more = ! empty( $data['exceededTransferLimit'] ) || ! empty( $data['properties']['exceededTransferLimit'] );
		if ( ! $more || ! $data['features'] ) {
			return [ 'type' => 'FeatureCollection', 'features' => $features ];
		}
		$offset += count( $data['features'] );
	}
	return new WP_Error( 'arcgis_paging', 'שאילתת השכבה: יותר מדי עמודים, הסנכרון נעצר.' );
}

/* -------------------------------------------------------------------------
 * Run
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'log_arcgis_sync' ) ) {
		// Sunday night, before the work week.
		$first = ( new DateTimeImmutable( 'next sunday 03:00', wp_timezone() ) )->getTimestamp();
		wp_schedule_event( $first, 'weekly', 'log_arcgis_sync' );
	}
} );
add_action( 'log_arcgis_sync', fn() => log_arcgis_sync( 'cron' ) );

/**
 * One sync. Returns the stored result:
 * [ time, trigger, ok, message, counts?, closed?, error? ].
 */
function log_arcgis_sync( string $trigger = 'manual' ): array {
	$s = log_arcgis_settings();
	if ( $s['layer_url'] === '' ) {
		// Not set up yet: nothing to alert about, the weekly run just waits.
		return log_arcgis_store( [ 'trigger' => $trigger, 'ok' => false, 'skipped' => true, 'message' => 'לא הוגדרה כתובת שכבה (הגדרות לוג → ArcGIS).' ] );
	}
	if ( get_transient( 'log_arcgis_syncing' ) ) {
		return [ 'time' => time(), 'trigger' => $trigger, 'ok' => false, 'skipped' => true, 'message' => 'סנכרון אחר רץ כרגע. נסו שוב בעוד כמה דקות.' ];
	}
	set_transient( 'log_arcgis_syncing', 1, 15 * MINUTE_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 );
	}

	try {
		$data = log_arcgis_fetch( $s );
		if ( ! is_wp_error( $data ) ) {
			$data = log_arcgis_sanity( $data );
		}
		if ( is_wp_error( $data ) ) {
			return log_arcgis_fail( $trigger, $data->get_error_message() );
		}

		$result  = log_import_run( $data, false );
		$present = [];
		foreach ( $result['rows'] as $row ) {
			if ( $row['objectid'] !== '' ) {
				$present[] = (string) $row['objectid'];
			}
		}
		$closed = log_arcgis_close_missing( $present );
		if ( $closed ) {
			log_flush_map_cache();
		}

		$c = $result['counts'];
		update_option( 'log_arcgis_last_success', time(), false );
		return log_arcgis_store( [
			'trigger' => $trigger,
			'ok'      => true,
			'counts'  => $c,
			'closed'  => $closed,
			'message' => sprintf( '%d רשומות בשכבה: נוצרו %d · עודכנו %d · דולגו %d · נסגרו במקור %d', count( $data['features'] ), $c['created'], $c['updated'], $c['skipped'], count( $closed ) ),
		] );
	} catch ( Throwable $e ) {
		return log_arcgis_fail( $trigger, 'שגיאה פנימית: ' . $e->getMessage() );
	} finally {
		delete_transient( 'log_arcgis_syncing' );
	}
}

/**
 * Refuse data that would wreck the site: no features, or far fewer than the site already has from ArcGIS
 * (a filtered view or the wrong layer). Nothing is written in that case.
 */
function log_arcgis_sanity( array $data ): array|WP_Error {
	$count = count( $data['features'] );
	if ( $count === 0 ) {
		return new WP_Error( 'arcgis_empty', 'השכבה החזירה 0 רשומות. הסנכרון לא הוחל.' );
	}
	$known = count( array_filter( array_keys( log_arcgis_known_projects() ), static fn( int $id ) => ! get_post_meta( $id, 'arcgis_removed_at', true ) ) );
	if ( $known >= 10 && $count < $known / 2 ) {
		return new WP_Error( 'arcgis_shrunk', sprintf( 'השכבה החזירה %d רשומות, לעומת %d פרויקטים מ-ArcGIS באתר. ייתכן שזו תצוגה מסוננת או שכבה אחרת. הסנכרון לא הוחל.', $count, $known ) );
	}
	return $data;
}

/** post ID → OBJECTID for every project that came from ArcGIS. */
function log_arcgis_known_projects(): array {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.ID, m.meta_value FROM $wpdb->posts p JOIN $wpdb->postmeta m ON m.post_id = p.ID AND m.meta_key = 'arcgis_id'
		 WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit') AND m.meta_value <> ''",
		'log_project'
	) );
	$out = [];
	foreach ( $rows as $r ) {
		$out[ (int) $r->ID ] = (string) $r->meta_value;
	}
	return $out;
}

/**
 * Plans no longer in the layer: in-progress ones become "הושלם" and stay on the site.
 * Stopped/cancelled plans keep their status. Returns [ post_id => title ] of the plans closed now.
 */
function log_arcgis_close_missing( array $present ): array {
	$present = array_flip( $present );
	$closed  = [];
	foreach ( log_arcgis_known_projects() as $post_id => $objectid ) {
		if ( isset( $present[ $objectid ] ) || get_post_meta( $post_id, 'arcgis_removed_at', true ) ) {
			continue;
		}
		update_post_meta( $post_id, 'arcgis_removed_at', time() );
		if ( log_field( 'status', $post_id, '01' ) === '01' ) {
			log_set_field( 'status', '02', $post_id );
			log_project_recompute_derived( $post_id );
			$closed[ $post_id ] = get_the_title( $post_id );
		}
	}
	return $closed;
}

function log_arcgis_fail( string $trigger, string $error ): array {
	error_log( '[log-core] ArcGIS sync failed (' . $trigger . '): ' . $error );
	$result = log_arcgis_store( [ 'trigger' => $trigger, 'ok' => false, 'error' => $error, 'message' => $error ] );
	log_arcgis_alert( $result );
	return $result;
}

/** Email the admin. The site keeps the last approved data, so the email says so. */
function log_arcgis_alert( array $result ): bool {
	$s    = log_arcgis_settings();
	$to   = is_email( $s['alert_email'] ) ? $s['alert_email'] : get_option( 'admin_email' );
	$last = (int) get_option( 'log_arcgis_last_success', 0 );
	$body = implode( "\n\n", [
		'הסנכרון מ-ArcGIS נכשל ב-' . wp_date( 'j.n.Y H:i', $result['time'] ) . ( $result['trigger'] === 'cron' ? ' (ריצה שבועית).' : ' (הופעל ידנית).' ),
		'השגיאה: ' . $result['error'],
		'האתר ממשיך להציג את הנתונים המאושרים האחרונים' . ( $last ? ', מהסנכרון המוצלח של ' . wp_date( 'j.n.Y H:i', $last ) : '' ) . '. שום דבר לא נמחק או נדרס.',
		'לבדיקה ולהרצה חוזרת: ' . admin_url( 'tools.php?page=log-arcgis-import' ),
	] );
	return wp_mail( $to, '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] הסנכרון מ-ArcGIS נכשל', $body );
}

/** Keep the last result and a short history (last 12 runs) for the Tools page. */
function log_arcgis_store( array $result ): array {
	$result += [ 'time' => time() ];
	update_option( 'log_arcgis_sync', $result, false );
	$log = get_option( 'log_arcgis_sync_log', [] );
	$log = is_array( $log ) ? $log : [];
	array_unshift( $log, array_diff_key( $result, [ 'closed' => 1 ] ) + [ 'closed_count' => count( $result['closed'] ?? [] ) ] );
	update_option( 'log_arcgis_sync_log', array_slice( $log, 0, 12 ), false );
	return $result;
}

/* -------------------------------------------------------------------------
 * Tools → ייבוא מ-ArcGIS: status, "סנכרון עכשיו", and a fallback settings form
 * ---------------------------------------------------------------------- */

/** Handles the panel's forms. Called from log_import_page() before any output. */
function log_arcgis_handle_post(): ?array {
	if ( ! current_user_can( 'manage_options' ) ) {
		return null;
	}
	if ( isset( $_POST['log_arcgis_sync_now'] ) && check_admin_referer( 'log_arcgis_sync' ) ) {
		return log_arcgis_sync( 'manual' );
	}
	if ( isset( $_POST['log_arcgis_save'] ) && check_admin_referer( 'log_arcgis_settings' ) ) {
		$plain = [
			'arcgis_layer_url'   => esc_url_raw( wp_unslash( $_POST['arcgis_layer_url'] ?? '' ) ),
			'arcgis_username'    => sanitize_text_field( wp_unslash( $_POST['arcgis_username'] ?? '' ) ),
			'arcgis_portal'      => esc_url_raw( wp_unslash( $_POST['arcgis_portal'] ?? '' ) ),
			'arcgis_alert_email' => sanitize_email( wp_unslash( $_POST['arcgis_alert_email'] ?? '' ) ),
		];
		foreach ( $plain as $name => $value ) {
			log_set_field( $name, $value, 'option' );
		}
		foreach ( log_arcgis_secret_fields() as $name ) {
			$value = trim( (string) wp_unslash( $_POST[ $name ] ?? '' ) );
			if ( $value !== '' ) {
				log_set_field( $name, $value === '-' ? '' : $value, 'option' );
			}
		}
		delete_transient( 'log_arcgis_token' );
		return [ 'saved' => true ];
	}
	return null;
}

function log_arcgis_panel( ?array $posted ): void {
	$s      = log_arcgis_settings();
	$last   = get_option( 'log_arcgis_sync' );
	$next   = wp_next_scheduled( 'log_arcgis_sync' );
	$okTime = (int) get_option( 'log_arcgis_last_success', 0 );
	$date   = static fn( int $t ) => wp_date( 'j.n.Y H:i', $t );
	$modes  = [ 'api_key' => 'מפתח API', 'user' => 'שם משתמש וסיסמה', 'none' => 'בלי הזדהות (שכבה ציבורית)' ];

	if ( ! empty( $posted['saved'] ) ) {
		echo '<div class="notice notice-success"><p>הגדרות החיבור נשמרו.</p></div>';
	} elseif ( $posted ) {
		$class = $posted['ok'] ? 'notice-success' : ( empty( $posted['skipped'] ) ? 'notice-error' : 'notice-warning' );
		$head  = $posted['ok'] ? 'הסנכרון הושלם.' : ( empty( $posted['skipped'] ) ? 'הסנכרון נכשל. שום דבר לא נכתב, ונשלחה התראה במייל.' : 'הסנכרון לא רץ.' );
		echo '<div class="notice ' . esc_attr( $class ) . '"><p><strong>' . esc_html( $head ) . '</strong> ' . esc_html( $posted['message'] ) . '</p>';
		if ( ! empty( $posted['closed'] ) ) {
			echo '<p>הוסרו מהשכבה וסומנו "הושלם": ' . esc_html( implode( ' · ', $posted['closed'] ) ) . '</p>';
		}
		echo '</div>';
	}

	echo '<h2>סנכרון מהשכבה</h2>';
	echo '<p>האתר מושך את <code>blue_lines_main</code> ישירות מ-ArcGIS פעם בשבוע, וכאן אפשר להריץ סנכרון בכל רגע. ';
	echo 'הסנכרון מעדכן רק שדות שמגיעים מ-ArcGIS. אם הוא נכשל, האתר ממשיך להציג את הנתונים האחרונים ונשלחת התראה במייל.</p>';

	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">שכבה</th><td>' . ( $s['layer_url'] !== '' ? '<code dir="ltr">' . esc_html( $s['layer_url'] ) . '</code>' : '<em>לא הוגדרה</em>' ) . '</td></tr>';
	echo '<tr><th scope="row">הזדהות</th><td>' . esc_html( $modes[ log_arcgis_auth_mode( $s ) ] ) . '</td></tr>';
	echo '<tr><th scope="row">ריצה אחרונה</th><td>';
	if ( is_array( $last ) ) {
		$state = $last['ok'] ? 'הצליחה' : ( empty( $last['skipped'] ) ? 'נכשלה' : 'לא רצה' );
		echo esc_html( $date( (int) $last['time'] ) . ' · ' . ( $last['trigger'] === 'cron' ? 'שבועית' : 'ידנית' ) . ' · ' . $state );
		echo '<br><span class="description">' . esc_html( $last['message'] ) . '</span>';
	} else {
		echo '<em>עדיין לא רץ</em>';
	}
	echo '</td></tr>';
	echo '<tr><th scope="row">סנכרון מוצלח אחרון</th><td>' . esc_html( $okTime ? $date( $okTime ) : '—' ) . '</td></tr>';
	echo '<tr><th scope="row">הריצה השבועית הבאה</th><td>' . esc_html( $next ? $date( $next ) : 'לא מתוזמנת' ) . '</td></tr>';
	echo '</tbody></table>';

	echo '<form method="post">';
	wp_nonce_field( 'log_arcgis_sync' );
	submit_button( 'סנכרון עכשיו', 'primary', 'log_arcgis_sync_now', false, $s['layer_url'] === '' ? [ 'disabled' => 'disabled' ] : [] );
	echo '</form>';

	$log = get_option( 'log_arcgis_sync_log', [] );
	if ( is_array( $log ) && count( $log ) > 1 ) {
		echo '<details style="margin-top:1em"><summary>ריצות קודמות</summary><table class="widefat striped" style="margin-top:.5em"><tbody>';
		foreach ( $log as $run ) {
			echo '<tr><td style="width:9em">' . esc_html( $date( (int) $run['time'] ) ) . '</td><td style="width:5em">' . esc_html( $run['trigger'] === 'cron' ? 'שבועית' : 'ידנית' ) . '</td>';
			echo '<td>' . esc_html( ( $run['ok'] ? '✓ ' : '✗ ' ) . $run['message'] ) . '</td></tr>';
		}
		echo '</tbody></table></details>';
	}

	// The ArcGIS tab lives on the ACF options page. Without ACF Pro there is no such page, so the same
	// settings are editable here (same option names, so they carry over once ACF Pro is installed).
	if ( function_exists( 'acf_add_options_page' ) ) {
		echo '<p>פרטי החיבור: <a href="' . esc_url( admin_url( 'admin.php?page=log-settings' ) ) . '">הגדרות לוג → ArcGIS</a>.</p>';
		return;
	}
	$secret = static fn( string $name ) => log_option( $name ) ? 'שמור. השאירו ריק כדי לא לשנות, מקף (-) מוחק' : '';
	echo '<h2>פרטי החיבור</h2><form method="post">';
	wp_nonce_field( 'log_arcgis_settings' );
	echo '<table class="form-table" role="presentation"><tbody>';
	$row = static function ( string $name, string $label, string $type, string $value, string $placeholder = '', string $help = '' ): void {
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input class="regular-text" dir="ltr" type="' . esc_attr( $type ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	$row( 'arcgis_layer_url', 'כתובת השכבה', 'url', $s['layer_url'] ?: LOG_ARCGIS_LAYER, '', 'השכבה blue_lines_main היא שכבה 0 בשירות שכבת_תוכניות___ראשי_gdb (…/FeatureServer/0).' );
	$row( 'arcgis_api_key', 'מפתח API', 'password', '', $secret( 'arcgis_api_key' ), 'מפתח עם הרשאת קריאה לשכבה. אם יש מפתח, שם המשתמש והסיסמה לא בשימוש.' );
	$row( 'arcgis_username', 'שם משתמש', 'text', $s['username'] );
	$row( 'arcgis_password', 'סיסמה', 'password', '', $secret( 'arcgis_password' ) );
	$row( 'arcgis_portal', 'כתובת הפורטל', 'url', $s['portal'] === LOG_ARCGIS_PORTAL ? '' : $s['portal'], LOG_ARCGIS_PORTAL, 'רק ל-ArcGIS Enterprise. ב-ArcGIS Online השאירו ריק.' );
	$row( 'arcgis_alert_email', 'מייל להתראות', 'email', $s['alert_email'], get_option( 'admin_email' ), 'לכאן נשלחת התראה כשסנכרון נכשל. ריק: מייל מנהל האתר.' );
	echo '</tbody></table>';
	submit_button( 'שמירת פרטי החיבור', 'secondary', 'log_arcgis_save' );
	echo '</form>';
}

/* -------------------------------------------------------------------------
 * Project edit screen: synced fields are read-only
 * ---------------------------------------------------------------------- */

/**
 * Field names the last sync wrote for this project (plus "title" and "log_authority").
 * Projects imported before the list was recorded get the importer's standard set.
 */
function log_arcgis_synced_fields( int $post_id ): array {
	if ( ! $post_id || get_post_type( $post_id ) !== 'log_project' || (string) get_post_meta( $post_id, 'arcgis_id', true ) === '' ) {
		return [];
	}
	$fields = get_post_meta( $post_id, 'arcgis_fields', true );
	if ( ! is_array( $fields ) ) {
		$fields = [ 'title', 'arcgis_id', 'plan_number', 'units', 'milestone', 'status', 'plan_type', 'institution', 'district' ];
		if ( get_post_meta( $post_id, 'boundary', true ) ) {
			array_push( $fields, 'lat', 'lng', 'dunam', 'boundary' );
		}
		if ( has_term( '', 'log_authority', $post_id ) ) {
			$fields[] = 'log_authority';
		}
	}
	// A plan removed from the layer keeps the "הושלם" the sync gave it.
	if ( get_post_meta( $post_id, 'arcgis_removed_at', true ) ) {
		$fields[] = 'status';
	}
	return array_values( array_unique( $fields ) );
}

/** The project being edited, from ACF's render or save context. */
function log_arcgis_current_post_id(): int {
	global $post;
	if ( isset( $_POST['post_ID'] ) ) {
		return (int) $_POST['post_ID'];
	}
	return $post instanceof WP_Post ? (int) $post->ID : 0;
}

add_filter( 'acf/prepare_field', function ( $field ) {
	if ( ! $field || ! is_admin() || empty( $field['name'] ) || ! str_starts_with( $field['key'] ?? '', 'field_lp_' ) ) {
		return $field;
	}
	$post_id = log_arcgis_current_post_id();
	$synced  = log_arcgis_synced_fields( $post_id );
	if ( ! $synced ) {
		return $field;
	}
	// ACF renders inputs named acf[field_…]; the plain name is the last segment.
	$name = $field['_name'] ?? $field['name'];
	$note = 'מסונכרן מ-ArcGIS';
	if ( $name === 'stage' && in_array( 'milestone', $synced, true ) && log_stage_from_milestone( log_field( 'milestone', $post_id ) ?: null ) ) {
		$note = 'נגזר מאבן הדרך שמסונכרנת מ-ArcGIS';
	} elseif ( ! in_array( $name, $synced, true ) ) {
		return $field;
	}
	if ( in_array( $field['type'], [ 'select', 'post_object', 'true_false', 'radio', 'checkbox' ], true ) ) {
		$field['disabled'] = 1;
	} else {
		$field['readonly'] = 1;
	}
	$field['instructions']      = '<span class="log-synced-note">' . $note . '</span>' . ( empty( $field['instructions'] ) ? '' : ' ' . $field['instructions'] );
	$field['wrapper']['class']  = trim( ( $field['wrapper']['class'] ?? '' ) . ' log-synced' );
	return $field;
} );

/** The edit form can't change synced fields, whatever is posted (ACF saves at priority 10). */
add_action( 'acf/save_post', function ( $post_id ) {
	if ( ! is_numeric( $post_id ) || empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
		return;
	}
	foreach ( log_arcgis_synced_fields( (int) $post_id ) as $name ) {
		$key = log_acf_field_key( $name, (int) $post_id );
		if ( $key ) {
			unset( $_POST['acf'][ $key ] );
		}
	}
	if ( in_array( 'milestone', log_arcgis_synced_fields( (int) $post_id ), true ) && log_stage_from_milestone( log_field( 'milestone', (int) $post_id ) ?: null ) ) {
		unset( $_POST['acf']['field_lp_stage'] );
	}
}, 1 );

/** Same for the title, from the edit form and quick edit. The importer's own wp_update_post is not a form post. */
add_filter( 'wp_insert_post_data', function ( array $data, array $postarr ) {
	$action = $_POST['action'] ?? '';
	if ( ( $data['post_type'] ?? '' ) !== 'log_project' || empty( $postarr['ID'] ) || ! in_array( $action, [ 'editpost', 'inline-save' ], true ) ) {
		return $data;
	}
	if ( in_array( 'title', log_arcgis_synced_fields( (int) $postarr['ID'] ), true ) ) {
		$data['post_title'] = wp_slash( get_post_field( 'post_title', (int) $postarr['ID'], 'raw' ) );
	}
	return $data;
}, 10, 2 );

add_action( 'add_meta_boxes_log_project', function ( WP_Post $post ) {
	if ( (string) get_post_meta( $post->ID, 'arcgis_id', true ) === '' ) {
		return;
	}
	add_meta_box( 'log_arcgis', 'ArcGIS', 'log_arcgis_meta_box', 'log_project', 'side', 'high' );
	// The authority comes from the layer: shown in the ArcGIS box instead of the editable term box.
	if ( in_array( 'log_authority', log_arcgis_synced_fields( $post->ID ), true ) ) {
		remove_meta_box( 'tagsdiv-log_authority', 'log_project', 'side' );
	}
} );

function log_arcgis_meta_box( WP_Post $post ): void {
	$synced  = log_arcgis_synced_fields( $post->ID );
	$at      = (int) get_post_meta( $post->ID, 'synced_at', true );
	$removed = (int) get_post_meta( $post->ID, 'arcgis_removed_at', true );
	$auth    = wp_get_post_terms( $post->ID, 'log_authority', [ 'fields' => 'names' ] );

	echo '<p>OBJECTID <strong>' . esc_html( (string) get_post_meta( $post->ID, 'arcgis_id', true ) ) . '</strong></p>';
	echo '<p>סונכרן לאחרונה: <strong>' . esc_html( $at ? wp_date( 'j.n.Y H:i', $at ) : 'עוד לא' ) . '</strong></p>';
	if ( $removed ) {
		echo '<p class="log-synced-warn">הוסרה מהשכבה ב-' . esc_html( wp_date( 'j.n.Y', $removed ) ) . '. הפרויקט נשאר באתר; להסרה, בטלו את "מאושר לפרסום".</p>';
	}
	if ( in_array( 'log_authority', $synced, true ) && $auth && ! is_wp_error( $auth ) ) {
		echo '<p>רשות: <strong>' . esc_html( implode( ', ', $auth ) ) . '</strong></p>';
	}
	echo '<p class="description">השם והשדות המסומנים <span class="log-synced-note">מסונכרן מ-ArcGIS</span> מתעדכנים מהשכבה בכל סנכרון, ולכן נעולים כאן. ';
	echo 'לתיקון, ערכו אותם ב-ArcGIS. הסיפור, המדיה, העיר והאישורים הם שלכם.</p>';
	echo '<p><a href="' . esc_url( admin_url( 'tools.php?page=log-arcgis-import' ) ) . '">סטטוס הסנכרון</a></p>';
}

add_action( 'admin_head-post.php', function () {
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'log_project' ) {
		return;
	}
	$synced = log_arcgis_synced_fields( log_arcgis_current_post_id() );
	?>
	<style>
		.log-synced-note { display: inline-block; padding: 1px 6px; border-radius: 3px; background: #e7eef3; color: #1d4b63; font-size: 11px; font-style: normal; }
		.acf-field.log-synced input[readonly], .acf-field.log-synced textarea[readonly] { background: #f6f7f7; color: #50575e; }
		.log-synced-warn { color: #8a4b00; }
	</style>
	<?php if ( in_array( 'title', $synced, true ) ) : ?>
	<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var t = document.getElementById( 'title' );
			if ( ! t ) { return; }
			t.readOnly = true;
			t.title = 'מסונכרן מ-ArcGIS';
			t.style.background = '#f6f7f7';
		} );
	</script>
	<?php endif;
} );
