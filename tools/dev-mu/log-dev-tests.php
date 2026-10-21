<?php
/**
 * Dev-only test page (mounted into Playground by tools/dev.sh as a mu-plugin; never deployed).
 * Tools → LOG Dev tests: run the Instagram importer on tools/fixtures/instagram-sample.json twice
 * and show created/updated/skipped, then the merged news feed.
 */

defined( 'ABSPATH' ) || exit;

// Local only: show PHP errors instead of WordPress's generic "critical error" page.
if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) { define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ); }
@ini_set( 'display_errors', '1' );
error_reporting( E_ALL & ~E_DEPRECATED );

add_action( 'admin_menu', function () {
	add_management_page( 'LOG Dev tests', 'LOG Dev tests', 'manage_options', 'log-dev-tests', function () {
		echo '<div class="wrap"><h1>LOG Dev tests</h1>';
		if ( isset( $_POST['run'] ) && check_admin_referer( 'log_dev' ) ) {
			$fixture = json_decode( file_get_contents( WP_CONTENT_DIR . '/mu-plugins/instagram-sample.json' ), true );
			$first   = log_instagram_import_items( $fixture['data'] );
			$second  = log_instagram_import_items( $fixture['data'] );
			echo '<pre id="result">first: ' . wp_json_encode( $first ) . "\nsecond: " . wp_json_encode( $second ) . "\n";
			echo 'last media error: ' . esc_html( (string) get_option( 'log_instagram_last_media_error', '' ) ) . "\n";
			$probe = wp_remote_get( 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Hadera_aerial_view.jpg/640px-Hadera_aerial_view.jpg', [ 'timeout' => 15 ] );
			$probe2 = wp_remote_get( 'https://picsum.photos/seed/log/640/480.jpg', [ 'timeout' => 15 ] );
			echo 'probe picsum: ' . ( is_wp_error( $probe2 ) ? $probe2->get_error_message() : wp_remote_retrieve_response_code( $probe2 ) . ' ' . wp_remote_retrieve_header( $probe2, 'content-type' ) ) . "\n";
			echo 'wikimedia body: ' . esc_html( substr( strip_tags( (string) wp_remote_retrieve_body( $probe ) ), 0, 160 ) ) . "\n";
			echo 'outbound probe: ' . ( is_wp_error( $probe ) ? $probe->get_error_message() : wp_remote_retrieve_response_code( $probe ) . ' ' . strlen( wp_remote_retrieve_body( $probe ) ) . ' bytes' ) . "\n";
			foreach ( log_instagram_items( 8 ) as $it ) {
				echo wp_date( 'Y-m-d', $it['date'] ) . ' | ' . $it['text'] . ' | img=' . ( $it['image'] ? 'yes' : 'no' ) . "\n";
			}
			echo '</pre>';
		}
		if ( isset( $_POST['arcgis'] ) && check_admin_referer( 'log_dev' ) ) {
			$file = WP_CONTENT_DIR . '/mu-plugins/blue_lines_main.geojson';
			if ( ! file_exists( $file ) ) {
				echo '<pre id="arcgis-result">no private fixture mounted (tools/fixtures/private/blue_lines_main.geojson)</pre>';
			} else {
				$res = log_import_run( json_decode( file_get_contents( $file ), true ), false );
				// Local only: publish and approve everything that has a location, so the hero has data.
				$ids = get_posts( [ 'post_type' => 'log_project', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );
				$approved = 0;
				foreach ( $ids as $id ) {
					if ( ! log_field( 'lat', $id ) ) { continue; }
					wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
					log_set_field( 'approved', 1, $id );
					$approved++;
				}
				log_flush_map_cache();
				echo '<pre id="arcgis-result">' . esc_html( wp_json_encode( [ 'summary' => array_diff_key( $res, [ 'rows' => 1 ] ), 'approved' => $approved ], JSON_UNESCAPED_UNICODE ) ) . '</pre>';
			}
		}
		if ( isset( $_POST['migrate'] ) && check_admin_referer( 'log_dev' ) ) {
			// Rebuild the pre-v6 shape (log_city terms, no authority/stage terms), then run the v6 migration.
			register_taxonomy( 'log_city', [ 'log_project' ], [ 'public' => false, 'rewrite' => false ] );
			$ids = get_posts( [ 'post_type' => 'log_project', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );
			foreach ( $ids as $id ) {
				$auth = wp_get_object_terms( $id, 'log_authority', [ 'fields' => 'names' ] );
				if ( $auth ) {
					wp_set_object_terms( $id, $auth[0], 'log_city', false );
				}
				wp_set_object_terms( $id, [], 'log_authority', false );
				wp_set_object_terms( $id, [], 'log_stage', false );
			}
			$before = [ 'city_terms' => (int) wp_count_terms( [ 'taxonomy' => 'log_city', 'hide_empty' => false ] ) ];
			update_option( 'log_schema_version', 1 );
			log_migrate_v2();
			update_option( 'log_schema_version', LOG_SCHEMA_VERSION );
			log_flush_map_cache();
			$stages = [];
			foreach ( get_terms( [ 'taxonomy' => 'log_stage', 'hide_empty' => false ] ) as $t ) {
				$stages[ $t->slug ] = $t->count;
			}
			$after = [
				'city_terms_left'  => (int) wp_count_terms( [ 'taxonomy' => 'log_city', 'hide_empty' => false ] ),
				'authority_terms'  => (int) wp_count_terms( [ 'taxonomy' => 'log_authority', 'hide_empty' => true ] ),
				'projects_without_authority' => count( array_filter( $ids, static fn( $id ) => ! has_term( '', 'log_authority', $id ) ) ),
				'stages'           => $stages,
				'units'            => wp_list_pluck( log_ordered_terms( 'log_unit' ), 'name' ),
				'knowledge_types'  => wp_list_pluck( get_terms( [ 'taxonomy' => 'log_knowledge_type', 'hide_empty' => false ] ), 'name', 'slug' ),
			];
			echo '<pre id="migrate-result">' . esc_html( wp_json_encode( [ 'before' => $before, 'after' => $after ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</pre>';
		}
		if ( isset( $_POST['fullpage'] ) && check_admin_referer( 'log_dev' ) ) {
			// One full page to test with: "החווה החקלאית" gets a domain, a story and full_page.
			$post = get_page_by_path( sanitize_title( 'החווה-החקלאית' ), OBJECT, 'log_project' );
			$out  = [ 'found' => (bool) $post ];
			if ( $post ) {
				wp_set_object_terms( $post->ID, 'urban-renewal', 'log_domain', false );
				log_set_field( 'full_page', 1, $post->ID );
				$out['missing_before_story'] = log_project_page_missing( $post->ID );
				$out['has_page_before_story'] = log_project_has_page( $post->ID );
				log_set_field( 'challenge', 'שכונה ותיקה, בעלות מפוצלת וצורך בתכנית אחת לכל המתחם.', $post->ID );
				log_set_field( 'year', 2018, $post->ID );
				log_flush_map_cache();
				$out['has_page'] = log_project_has_page( $post->ID );
				$out['permalink'] = rawurldecode( get_permalink( $post ) );
				$out['map_url']   = log_project_map_url( $post->ID );
				$out['years']     = log_project_years( $post->ID );
				$out['from_param_slug'] = log_project_from_param( rawurldecode( $post->post_name ) );
			}
			echo '<pre id="fullpage-result">' . esc_html( wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</pre>';
		}
		if ( isset( $_POST['samples'] ) && check_admin_referer( 'log_dev' ) ) {
			// Local sample content for the v6 pages: two jobs (one closed), knowledge items, people.
			$mk = static function ( string $type, string $title, array $fields = [], array $terms = [], string $content = '', string $excerpt = '' ): int {
				$id = wp_insert_post( [ 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content, 'post_excerpt' => $excerpt ] );
				foreach ( $fields as $k => $v ) {
					log_set_field( $k, $v, $id );
				}
				foreach ( $terms as $tax => $slug ) {
					wp_set_object_terms( $id, $slug, $tax, false );
				}
				return $id;
			};
			$ids = [
				$mk( 'log_job', 'מנהל/ת פרויקטים בהתחדשות עירונית', [ 'is_open' => 1, 'team' => 'התחדשות עירונית', 'location' => 'tel-aviv', 'scope' => 'full', 'job_number' => 'LOG-114', 'about' => 'ניהול תכניות פינוי-בינוי מול רשויות ומוסדות תכנון.', 'what_you_do' => "ניהול תכניות מקצה לקצה\nעבודה מול הוועדה המחוזית\nתיאום יועצים", 'requirements' => "תואר בתכנון ערים או אדריכלות\nשלוש שנות ניסיון", 'advantage' => 'היכרות עם מבא"ת', 'whats_waiting' => "צוות של 12 מתכננים\nמשרד בתל אביב", 'team_size' => '12', 'reports_to' => 'ראש צוות התחדשות' ] ),
				$mk( 'log_job', 'אנליסט/ית נתונים לחברת הבקרה', [ 'is_open' => 0, 'team' => 'חברת הבקרה', 'location' => 'jerusalem', 'scope' => 'full' ] ),
				$mk( 'log_knowledge', 'איך מקדמים תכנית מתאר בעיר בינונית', [ 'featured' => 1 ], [ 'log_knowledge_type' => 'article' ], '<p>מאמר לדוגמה.</p>', 'מה למדנו משלוש תכניות מתאר בשנה האחרונה.' ),
				$mk( 'log_knowledge', 'לוג בכלכליסט: הבקרה על ההתחדשות העירונית', [ 'is_control' => 1, 'outlet' => 'כלכליסט', 'external_url' => 'https://www.calcalist.co.il/' ], [ 'log_knowledge_type' => 'media' ], '', 'ראיון על תמונת המצב הלאומית.' ),
				$mk( 'log_person', 'נועה לוי', [ 'role' => 'מנכ"לית' ], [ 'log_unit' => 'management' ] ),
				$mk( 'log_person', 'דן כהן', [ 'role' => 'אנליסט בקרה' ], [ 'log_unit' => 'control' ] ),
			];
			echo '<pre id="samples-result">' . esc_html( wp_json_encode( $ids ) ) . '</pre>';
		}
		if ( isset( $_POST['arcgis_sync'] ) && check_admin_referer( 'log_dev' ) ) {
			echo '<pre id="arcgis-sync-result">' . esc_html( wp_json_encode( log_dev_arcgis_sync_test(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</pre>';
		}
		echo '<form method="post">';
		wp_nonce_field( 'log_dev' );
		submit_button( 'ArcGIS sync: mocked layer (cron, rename, removal, failure email)', 'secondary', 'arcgis_sync' );
		submit_button( 'Spec v6: sample jobs, knowledge, people', 'secondary', 'samples' );
		submit_button( 'Spec v6: give one project a full page', 'secondary', 'fullpage' );
		submit_button( 'Spec v6 migration: rebuild pre-v6 data and migrate', 'secondary', 'migrate' );
		submit_button( 'Run Instagram import on fixture (twice)', 'primary', 'run' );
		submit_button( 'Import private ArcGIS GeoJSON + approve all (local only)', 'secondary', 'arcgis' );
		echo '</form></div>';
	} );
} );

/**
 * Spec v6 §18: the ArcGIS sync against a mocked layer (pre_http_request), fed from the private export.
 * The mock behaves like ArcGIS Online: POST only, maxRecordCount 40 (so paging runs), token required,
 * errors inside a 200. Mail is captured with pre_wp_mail. Ends with a clean full sync and the settings cleared.
 */
function log_dev_arcgis_sync_test(): array {
	$file = WP_CONTENT_DIR . '/mu-plugins/blue_lines_main.geojson';
	if ( ! file_exists( $file ) ) {
		return [ 'error' => 'no private fixture mounted (tools/fixtures/private/blue_lines_main.geojson)' ];
	}
	$all   = json_decode( file_get_contents( $file ), true );
	$layer = 'https://arcgis.test/lDcqcwePoeu47saG/arcgis/rest/services/blue_lines_main/FeatureServer/0';
	$mock  = [ 'features' => $all['features'], 'mode' => 'ok', 'calls' => [], 'tokens' => 0, 'expire_once' => false ];
	$mail  = [];

	$http = function ( $pre, $args, $url ) use ( &$mock ) {
		if ( ! str_starts_with( $url, 'https://arcgis.test/' ) && ! str_contains( $url, '/sharing/rest/generateToken' ) ) {
			return $pre;
		}
		$json = static fn( $body, int $code = 200 ) => [ 'headers' => [], 'body' => wp_json_encode( $body ), 'response' => [ 'code' => $code, 'message' => '' ], 'cookies' => [], 'filename' => null ];
		$b    = (array) ( $args['body'] ?? [] );
		$mock['calls'][] = [ 'method' => $args['method'] ?? 'GET', 'url' => $url, 'token' => $b['token'] ?? null, 'offset' => $b['resultOffset'] ?? null ];
		if ( str_contains( $url, 'generateToken' ) ) {
			$mock['tokens']++;
			return $json( [ 'token' => 'tok' . $mock['tokens'], 'expires' => ( time() + 7200 ) * 1000, 'ssl' => true ] );
		}
		if ( $mock['mode'] === 'http500' ) {
			return $json( [ 'oops' => 1 ], 500 );
		}
		if ( ( $args['method'] ?? '' ) !== 'POST' || empty( $b['token'] ) ) {
			return $json( [ 'error' => [ 'code' => 499, 'message' => 'Token Required' ] ] );
		}
		if ( $mock['expire_once'] ) {
			$mock['expire_once'] = false;
			return $json( [ 'error' => [ 'code' => 498, 'message' => 'Invalid token.' ] ] );
		}
		$offset = (int) $b['resultOffset'];
		$page   = array_slice( $mock['features'], $offset, min( 40, (int) $b['resultRecordCount'] ) );
		$out    = [ 'type' => 'FeatureCollection', 'features' => $page ];
		if ( $offset + count( $page ) < count( $mock['features'] ) ) {
			$out['properties'] = [ 'exceededTransferLimit' => true ];
		}
		return $json( $out );
	};
	$mailer = function ( $null, $atts ) use ( &$mail ) {
		$mail[] = [ 'to' => $atts['to'], 'subject' => $atts['subject'], 'body' => $atts['message'] ];
		return true;
	};
	add_filter( 'pre_http_request', $http, 10, 3 );
	add_filter( 'pre_wp_mail', $mailer, 10, 2 );

	foreach ( [ 'arcgis_layer_url' => $layer, 'arcgis_username' => 'log_reader', 'arcgis_password' => 's3cret', 'arcgis_api_key' => '', 'arcgis_alert_email' => 'alerts@log-pm.test' ] as $k => $v ) {
		log_set_field( $k, $v, 'option' );
	}
	delete_transient( 'log_arcgis_token' );
	delete_transient( 'log_arcgis_syncing' );
	$out = [ 'cron' => [ 'next' => wp_date( 'D j.n.Y H:i', (int) wp_next_scheduled( 'log_arcgis_sync' ) ), 'schedule' => wp_get_schedule( 'log_arcgis_sync' ) ] ];
	$res = static fn( array $r ) => array_intersect_key( $r, array_flip( [ 'trigger', 'ok', 'skipped', 'message' ] ) );

	// 1. Cron run, with paging (99 features / 40 per page) and a token.
	do_action( 'log_arcgis_sync' );
	$out['1_cron'] = $res( get_option( 'log_arcgis_sync' ) ) + [
		'requests' => array_map( static fn( $c ) => $c['method'] . ' ' . basename( $c['url'] ) . ' offset=' . $c['offset'] . ' token=' . $c['token'], $mock['calls'] ),
	];

	// Pick an in-progress plan to remove and another to rename; give the renamed one editorial content.
	$by_oid = array_flip( log_arcgis_known_projects() );
	$remove = $rename = null;
	foreach ( $mock['features'] as $i => $f ) {
		$oid = (string) $f['properties']['OBJECTID'];
		if ( ( $f['properties']['סטטוס'] ?? '' ) === '01' && isset( $by_oid[ $oid ] ) ) {
			if ( $remove === null ) {
				$remove = $i;
			} elseif ( $rename === null ) {
				$rename = $i;
				break;
			}
		}
	}
	$removed_id = $by_oid[ (string) $mock['features'][ $remove ]['properties']['OBJECTID'] ];
	$renamed_id = $by_oid[ (string) $mock['features'][ $rename ]['properties']['OBJECTID'] ];
	wp_update_post( [ 'ID' => $renamed_id, 'post_status' => 'publish' ] );
	log_set_field( 'challenge', 'סיפור שנכתב במערכת התוכן.', $renamed_id );
	log_set_field( 'approved', 1, $renamed_id );
	log_set_field( 'full_page', 1, $renamed_id );
	log_set_field( 'city', 'עיר שתוקנה ידנית', $renamed_id );
	$slug_before = get_post_field( 'post_name', $renamed_id );

	// 2. Manual run: one plan renamed in the source, one removed, and the token expires mid-run.
	$mock['features'][ $rename ]['properties']['שם_התוכנית'] .= ' (שם חדש)';
	$mock['features'][ $rename ]['properties']['יחד_מוצע']    = '';
	array_splice( $mock['features'], $remove, 1 );
	$mock['expire_once'] = true;
	$tokens_before       = $mock['tokens'];
	$r = log_arcgis_sync( 'manual' );
	$out['2_manual'] = $res( $r ) + [
		'closed'         => $r['closed'] ?? null,
		'token_renewed'  => $mock['tokens'] - $tokens_before,
		'removed'        => [ 'id' => $removed_id, 'status' => log_field( 'status', $removed_id ), 'stage' => wp_get_post_terms( $removed_id, 'log_stage', [ 'fields' => 'slugs' ] ), 'post_status' => get_post_status( $removed_id ), 'removed_at' => (bool) get_post_meta( $removed_id, 'arcgis_removed_at', true ) ],
		'renamed'        => [
			'id'            => $renamed_id,
			'title'         => get_the_title( $renamed_id ),
			'slug_kept'     => get_post_field( 'post_name', $renamed_id ) === $slug_before,
			'units'         => log_field( 'units', $renamed_id ),
			'story_kept'    => log_field( 'challenge', $renamed_id ) === 'סיפור שנכתב במערכת התוכן.',
			'approved'      => (bool) log_field( 'approved', $renamed_id ),
			'full_page'     => (bool) log_field( 'full_page', $renamed_id ),
			'city_kept'     => log_field( 'city', $renamed_id ) === 'עיר שתוקנה ידנית',
			'synced_fields' => get_post_meta( $renamed_id, 'arcgis_fields', true ),
		],
	];

	// 3. Failure: the layer answers HTTP 500. Nothing changes, the admin gets an email.
	$mock['features'][0]['properties']['שם_התוכנית'] = 'לא אמור להיכתב';
	$snap = [ 'last_success' => get_option( 'log_arcgis_last_success' ), 'title0' => get_the_title( $by_oid[ (string) $mock['features'][0]['properties']['OBJECTID'] ] ) ];
	$mock['mode'] = 'http500';
	do_action( 'log_arcgis_sync' );
	$out['3_failure'] = $res( get_option( 'log_arcgis_sync' ) ) + [
		'data_untouched'  => get_option( 'log_arcgis_last_success' ) === $snap['last_success'] && get_the_title( $by_oid[ (string) $mock['features'][0]['properties']['OBJECTID'] ] ) === $snap['title0'],
		'mail'            => $mail,
	];

	// 4. A layer that suddenly returns 10 plans (a filtered view): refused, nothing closed.
	$mock['mode']     = 'ok';
	$mock['features'] = array_slice( $all['features'], 0, 10 );
	$mail             = [];
	$r = log_arcgis_sync( 'manual' );
	$out['4_shrunk'] = $res( $r ) + [ 'mails' => count( $mail ), 'removed_now' => count( array_filter( array_keys( log_arcgis_known_projects() ), static fn( $id ) => get_post_meta( $id, 'arcgis_removed_at', true ) ) ) ];

	// 5. Not configured: the weekly run waits quietly.
	log_set_field( 'arcgis_layer_url', '', 'option' );
	$mail = [];
	$r = log_arcgis_sync( 'cron' );
	$out['5_unconfigured'] = $res( $r ) + [ 'mails' => count( $mail ) ];

	// Restore: a clean full sync brings the removed plan back with its source status, then clear the settings.
	log_set_field( 'arcgis_layer_url', $layer, 'option' );
	$mock['features'] = $all['features'];
	$r = log_arcgis_sync( 'manual' );
	$out['6_restore'] = $res( $r ) + [ 'removed_back' => [ 'status' => log_field( 'status', $removed_id ), 'removed_at' => (bool) get_post_meta( $removed_id, 'arcgis_removed_at', true ) ] ];
	$out['edit_links'] = [ 'renamed' => get_edit_post_link( $renamed_id, 'raw' ), 'removed' => get_edit_post_link( $removed_id, 'raw' ) ];

	remove_filter( 'pre_http_request', $http, 10 );
	remove_filter( 'pre_wp_mail', $mailer, 10 );
	foreach ( [ 'arcgis_layer_url', 'arcgis_username', 'arcgis_password', 'arcgis_alert_email' ] as $k ) {
		log_set_field( $k, '', 'option' );
	}
	delete_transient( 'log_arcgis_token' );
	return $out;
}
