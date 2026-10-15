<?php
/**
 * Forms (spec §13–14): contact and job application.
 *
 * Every submission is stored as a private `log_lead` post first, then emailed. If mail fails the
 * lead is still in the admin. Spam defence: honeypot + minimum fill time + per-IP rate limit.
 * Source tracking (UTM, referrer, landing page) is stored only when the visitor consented.
 *
 *   POST /wp-json/log/v1/contact   (JSON)
 *   POST /wp-json/log/v1/apply     (multipart, with a CV file)
 */

defined( 'ABSPATH' ) || exit;

const LOG_FORM_MIN_SECONDS = 4;
const LOG_FORM_MAX_PER_HOUR = 6;
const LOG_CV_MAX_BYTES = 5 * MB_IN_BYTES;

function log_contact_topics(): array {
	return [
		'business' => [ 'פיתוח עסקי ושיתופי פעולה', 'email' ],
		'control'  => [ 'חברת הבקרה', 'email_control' ],
		'jobs'     => [ 'גיוס', 'email_jobs' ],
		'finance'  => [ 'הנהלת חשבונות', 'email' ],
		'press'    => [ 'תקשורת', 'email' ],
		'other'    => [ 'אחר', 'email' ],
	];
}

add_action( 'init', function () {
	register_post_type( 'log_lead', [
		'labels' => [
			'name'          => 'פניות',
			'singular_name' => 'פנייה',
			'all_items'     => 'כל הפניות',
			'edit_item'     => 'פנייה',
			'not_found'     => 'אין פניות',
		],
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 25,
		'menu_icon'           => 'dashicons-email-alt',
		'capability_type'     => 'post',
		'capabilities'        => [ 'create_posts' => 'do_not_allow' ],
		'map_meta_cap'        => true,
		'supports'            => [ 'title' ],
		'exclude_from_search' => true,
	] );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'log/v1', '/contact', [
		'methods'             => 'POST',
		'callback'            => 'log_rest_contact',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'log/v1', '/apply', [
		'methods'             => 'POST',
		'callback'            => 'log_rest_apply',
		'permission_callback' => '__return_true',
	] );
} );

/**
 * Shared guards. Returns a WP_Error or null.
 */
function log_form_guard( WP_REST_Request $r ): ?WP_Error {
	if ( ! empty( $r['website'] ) ) { // honeypot
		return new WP_Error( 'spam', 'משהו השתבש.', [ 'status' => 400 ] );
	}
	$started = (int) $r['started'];
	if ( ! $started || time() - $started < LOG_FORM_MIN_SECONDS ) {
		return new WP_Error( 'too_fast', 'נראה שהטופס נשלח מהר מדי. נסו שוב.', [ 'status' => 400 ] );
	}
	if ( empty( $r['consent'] ) ) {
		return new WP_Error( 'consent', 'צריך לאשר את מדיניות הפרטיות כדי לשלוח.', [ 'status' => 400 ] );
	}
	$key   = 'log_form_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
	$count = (int) get_transient( $key );
	if ( $count >= LOG_FORM_MAX_PER_HOUR ) {
		return new WP_Error( 'rate', 'נשלחו יותר מדי פניות מהכתובת הזו. נסו שוב בעוד שעה.', [ 'status' => 429 ] );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return null;
}

function log_form_field_errors( array $values, array $rules ): array {
	$errors = [];
	foreach ( $rules as $field => [ $label, $required, $type ] ) {
		$v = trim( (string) ( $values[ $field ] ?? '' ) );
		if ( $required && $v === '' ) {
			$errors[ $field ] = "חסר: $label";
			continue;
		}
		if ( $v === '' ) {
			continue;
		}
		if ( $type === 'email' && ! is_email( $v ) ) {
			$errors[ $field ] = 'כתובת המייל לא נראית תקינה';
		}
		if ( $type === 'phone' && ! preg_match( '/^[\d\s\-+()]{7,20}$/', $v ) ) {
			$errors[ $field ] = 'מספר הטלפון לא נראה תקין';
		}
		if ( $type === 'url' && ! filter_var( $v, FILTER_VALIDATE_URL ) ) {
			$errors[ $field ] = 'הקישור לא נראה תקין';
		}
	}
	return $errors;
}

function log_form_source( WP_REST_Request $r ): array {
	if ( empty( $r['consent'] ) ) {
		return [];
	}
	$src = [];
	foreach ( [ 'utm_source', 'utm_medium', 'utm_campaign', 'referrer', 'landing' ] as $k ) {
		if ( ! empty( $r[ $k ] ) ) {
			$src[ $k ] = sanitize_text_field( substr( (string) $r[ $k ], 0, 300 ) );
		}
	}
	return $src;
}

function log_store_lead( string $type, string $title, array $data, array $source, ?int $attachment = null ): int {
	$id = wp_insert_post( [
		'post_type'   => 'log_lead',
		'post_status' => 'private',
		'post_title'  => $title,
	] );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_lead_type', $type );
	update_post_meta( $id, '_lead_data', $data );
	update_post_meta( $id, '_lead_source', $source );
	update_post_meta( $id, '_lead_ip', sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	if ( $attachment ) {
		update_post_meta( $id, '_lead_attachment', $attachment );
		wp_update_post( [ 'ID' => $attachment, 'post_parent' => $id ] );
	}
	return $id;
}

function log_send_lead_mail( string $to, string $subject, array $rows, ?string $attachment_path = null ): bool {
	$lines = [];
	foreach ( $rows as $label => $value ) {
		if ( $value === '' || $value === null ) {
			continue;
		}
		$lines[] = "$label: $value";
	}
	$body    = implode( "\n", $lines ) . "\n\n— נשלח מהאתר log-pm.co.il";
	$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
	if ( ! empty( $rows['מייל'] ) && is_email( $rows['מייל'] ) ) {
		$headers[] = 'Reply-To: ' . $rows['מייל'];
	}
	return wp_mail( $to, $subject, $body, $headers, $attachment_path ? [ $attachment_path ] : [] );
}

function log_rest_contact( WP_REST_Request $r ) {
	if ( $err = log_form_guard( $r ) ) {
		return $err;
	}
	$topics = log_contact_topics();
	$errors = log_form_field_errors( $r->get_params(), [
		'name'    => [ 'שם', true, 'text' ],
		'email'   => [ 'מייל', true, 'email' ],
		'phone'   => [ 'טלפון', false, 'phone' ],
		'message' => [ 'הודעה', true, 'text' ],
	] );
	$topic = sanitize_key( $r['topic'] ?? 'other' );
	if ( ! isset( $topics[ $topic ] ) ) {
		$errors['topic'] = 'בחרו נושא';
	}
	if ( $errors ) {
		return new WP_Error( 'validation', 'יש כמה שדות לתקן.', [ 'status' => 422, 'fields' => $errors ] );
	}

	$data = [
		'name'    => sanitize_text_field( $r['name'] ),
		'org'     => sanitize_text_field( $r['org'] ?? '' ),
		'phone'   => sanitize_text_field( $r['phone'] ?? '' ),
		'email'   => sanitize_email( $r['email'] ),
		'topic'   => $topic,
		'message' => sanitize_textarea_field( $r['message'] ),
		'project' => (int) ( $r['project'] ?? 0 ),
	];
	$source = log_form_source( $r );
	$lead   = log_store_lead( 'contact', $data['name'] . ' · ' . $topics[ $topic ][0], $data, $source );

	$to = log_option( $topics[ $topic ][1] ) ?: log_option( 'email' ) ?: get_option( 'admin_email' );
	$rows = [
		'נושא'    => $topics[ $topic ][0],
		'שם'      => $data['name'],
		'ארגון'   => $data['org'],
		'טלפון'   => $data['phone'],
		'מייל'    => $data['email'],
		'הודעה'   => $data['message'],
		'פרויקט'  => $data['project'] ? get_the_title( $data['project'] ) . ' (' . get_permalink( $data['project'] ) . ')' : '',
		'מקור'    => $source ? wp_json_encode( $source, JSON_UNESCAPED_UNICODE ) : '',
	];
	log_send_lead_mail( $to, 'פנייה מהאתר: ' . $topics[ $topic ][0] . ' · ' . $data['name'], $rows );

	return rest_ensure_response( [ 'ok' => true, 'lead' => $lead, 'message' => 'תודה, קיבלנו. נחזור אליכם בהקדם.' ] );
}

function log_rest_apply( WP_REST_Request $r ) {
	if ( $err = log_form_guard( $r ) ) {
		return $err;
	}
	$errors = log_form_field_errors( $r->get_params(), [
		'name'     => [ 'שם', true, 'text' ],
		'phone'    => [ 'טלפון', true, 'phone' ],
		'email'    => [ 'מייל', true, 'email' ],
		'linkedin' => [ 'LinkedIn', false, 'url' ],
	] );

	$files = $r->get_file_params();
	$cv    = $files['cv'] ?? null;
	if ( ! $cv || empty( $cv['tmp_name'] ) || $cv['error'] !== UPLOAD_ERR_OK ) {
		$errors['cv'] = 'צרפו קורות חיים (PDF או Word, עד 5MB)';
	} elseif ( $cv['size'] > LOG_CV_MAX_BYTES ) {
		$errors['cv'] = 'הקובץ גדול מ-5MB';
	} else {
		$check = wp_check_filetype_and_ext( $cv['tmp_name'], $cv['name'], [
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		] );
		if ( empty( $check['ext'] ) ) {
			$errors['cv'] = 'רק PDF או Word';
		}
	}
	if ( $errors ) {
		return new WP_Error( 'validation', 'יש כמה שדות לתקן.', [ 'status' => 422, 'fields' => $errors ] );
	}

	// Store the CV outside the public uploads listing: private attachment, random name.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	add_filter( 'upload_dir', 'log_cv_upload_dir' );
	$_FILES['cv']['name'] = wp_generate_password( 12, false ) . '.' . $check['ext'];
	$attachment = media_handle_upload( 'cv', 0, [ 'post_status' => 'private' ], [ 'test_form' => false ] );
	remove_filter( 'upload_dir', 'log_cv_upload_dir' );
	if ( is_wp_error( $attachment ) ) {
		return new WP_Error( 'upload', 'לא הצלחנו לשמור את הקובץ. נסו שוב.', [ 'status' => 500 ] );
	}

	$job  = (int) ( $r['job'] ?? 0 );
	$data = [
		'name'       => sanitize_text_field( $r['name'] ),
		'phone'      => sanitize_text_field( $r['phone'] ),
		'email'      => sanitize_email( $r['email'] ),
		'linkedin'   => esc_url_raw( $r['linkedin'] ?? '' ),
		'heard'      => sanitize_text_field( $r['heard'] ?? '' ),
		'job'        => $job,
		'keep'       => ! empty( $r['keep'] ),
	];
	$source = log_form_source( $r );
	$title  = $data['name'] . ' · ' . ( $job ? get_the_title( $job ) : 'מועמדות כללית' );
	$lead   = log_store_lead( 'apply', $title, $data, $source, (int) $attachment );

	$to   = log_option( 'email_jobs' ) ?: log_option( 'email' ) ?: get_option( 'admin_email' );
	$rows = [
		'משרה'          => $job ? get_the_title( $job ) . ' (' . get_permalink( $job ) . ')' : 'מועמדות כללית',
		'שם'            => $data['name'],
		'טלפון'         => $data['phone'],
		'מייל'          => $data['email'],
		'LinkedIn'      => $data['linkedin'],
		'איך שמעו עלינו' => $data['heard'],
		'שמירה למשרות עתידיות' => $data['keep'] ? 'כן' : 'לא',
		'מקור'          => $source ? wp_json_encode( $source, JSON_UNESCAPED_UNICODE ) : '',
	];
	log_send_lead_mail( $to, 'מועמדות: ' . $title, $rows, get_attached_file( $attachment ) );

	return rest_ensure_response( [ 'ok' => true, 'lead' => $lead, 'message' => 'תודה! קורות החיים אצלנו. אם יש התאמה, נחזור אליכם.' ] );
}

function log_cv_upload_dir( array $dir ): array {
	$dir['subdir'] = '/cv' . $dir['subdir'];
	$dir['path']   = $dir['basedir'] . $dir['subdir'];
	$dir['url']    = $dir['baseurl'] . $dir['subdir'];
	return $dir;
}

/**
 * Block directory listing and direct access to CVs; WordPress serves them to logged-in editors only.
 */
add_action( 'init', function () {
	$dir = wp_upload_dir()['basedir'] . '/cv';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$ht = "$dir/.htaccess";
	if ( ! file_exists( $ht ) ) {
		file_put_contents( $ht, "Require all denied\n" );
	}
	if ( ! file_exists( "$dir/index.html" ) ) {
		file_put_contents( "$dir/index.html", '' );
	}
} );

/**
 * Admin view of a lead: read-only details, with a download link for the CV.
 */
add_action( 'add_meta_boxes_log_lead', function () {
	add_meta_box( 'log_lead_details', 'פרטי הפנייה', function ( WP_Post $post ) {
		$data   = (array) get_post_meta( $post->ID, '_lead_data', true );
		$source = (array) get_post_meta( $post->ID, '_lead_source', true );
		$labels = [
			'name' => 'שם', 'org' => 'ארגון', 'phone' => 'טלפון', 'email' => 'מייל', 'topic' => 'נושא',
			'message' => 'הודעה', 'project' => 'פרויקט', 'linkedin' => 'LinkedIn', 'heard' => 'איך שמעו',
			'job' => 'משרה', 'keep' => 'שמירה למשרות עתידיות',
		];
		echo '<table class="widefat striped"><tbody>';
		foreach ( $data as $k => $v ) {
			if ( $k === 'topic' ) {
				$v = log_contact_topics()[ $v ][0] ?? $v;
			} elseif ( in_array( $k, [ 'project', 'job' ], true ) ) {
				$v = $v ? get_the_title( (int) $v ) : '—';
			} elseif ( $k === 'keep' ) {
				$v = $v ? 'כן' : 'לא';
			}
			printf( '<tr><th style="width:180px">%s</th><td>%s</td></tr>', esc_html( $labels[ $k ] ?? $k ), nl2br( esc_html( (string) $v ) ) );
		}
		if ( $att = get_post_meta( $post->ID, '_lead_attachment', true ) ) {
			printf( '<tr><th>קורות חיים</th><td><a href="%s">הורדה</a></td></tr>', esc_url( wp_get_attachment_url( (int) $att ) ) );
		}
		if ( $source ) {
			printf( '<tr><th>מקור הגעה</th><td><code>%s</code></td></tr>', esc_html( wp_json_encode( $source, JSON_UNESCAPED_UNICODE ) ) );
		}
		printf( '<tr><th>תאריך</th><td>%s</td></tr>', esc_html( get_the_date( 'd/m/Y H:i', $post ) ) );
		echo '</tbody></table>';
	}, 'log_lead', 'normal', 'high' );
} );

add_filter( 'manage_log_lead_posts_columns', function ( array $cols ): array {
	return [ 'cb' => $cols['cb'], 'title' => 'פנייה', 'lead_type' => 'סוג', 'lead_email' => 'מייל', 'date' => 'תאריך' ];
} );
add_action( 'manage_log_lead_posts_custom_column', function ( string $col, int $id ) {
	$data = (array) get_post_meta( $id, '_lead_data', true );
	if ( $col === 'lead_type' ) {
		echo get_post_meta( $id, '_lead_type', true ) === 'apply' ? 'מועמדות' : 'צור קשר';
	} elseif ( $col === 'lead_email' ) {
		echo esc_html( $data['email'] ?? '' );
	}
}, 10, 2 );
