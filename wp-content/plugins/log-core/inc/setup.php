<?php
/**
 * One-click site setup (Tools → LOG Setup). Idempotent: run it as often as you like.
 * Creates the system pages from spec §4, the three menus, sets the static front page,
 * seeds taxonomy terms and flushes rewrites.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_management_page( 'LOG Setup', 'LOG Setup', 'manage_options', 'log-setup', 'log_setup_page' );
} );

function log_setup_page(): void {
	$ran = false;
	if ( isset( $_POST['log_setup'] ) && check_admin_referer( 'log_setup' ) ) {
		$ran = log_run_setup();
	}
	echo '<div class="wrap"><h1>LOG Setup</h1>';
	if ( $ran ) {
		echo '<div class="notice notice-success"><p>הושלם. עמודים, תפריטים ועמוד בית מוגדרים.</p><ul>';
		foreach ( $ran as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '<p>יוצר את עמודי המערכת מהאפיון, שלושה תפריטים (ראשי, כפתורים צמודים, תחתון), מונחי טקסונומיה ועמוד בית סטטי. עמודים ותפריטים קיימים לא נפגעים.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'log_setup' );
	submit_button( 'הרצת Setup', 'primary', 'log_setup' );
	echo '</form></div>';
}

/**
 * Pages keyed by slug: [title, template]. Archive URLs (/projects, /knowledge, /careers)
 * belong to the CPTs, so they get no page.
 */
function log_system_pages(): array {
	return [
		'home'          => [ 'בית', '' ],
		'control'       => [ 'חברת הבקרה', 'page-control.php' ],
		'about'         => [ 'אודות והצוות', 'page-about.php' ],
		'contact'       => [ 'צור קשר', 'page-contact.php' ],
		'accessibility' => [ 'הצהרת נגישות', '' ],
		'privacy'       => [ 'מדיניות פרטיות', '' ],
		'cookies'       => [ 'מדיניות עוגיות', '' ],
	];
}

function log_run_setup(): array {
	$log = [];
	log_register_post_types();
	log_register_taxonomies();
	log_seed_terms();
	$log[] = 'מונחי טקסונומיה: תחומים, שלבים, יחידות ארגוניות, סוגי פריטים';

	$ids = [];
	foreach ( log_system_pages() as $slug => [ $title, $template ] ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$ids[ $slug ] = wp_insert_post( [
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
			] );
			$log[] = "נוצר עמוד: $title (/$slug)";
		} else {
			$ids[ $slug ] = $page->ID;
		}
		// The template meta is what ACF's "page template" location rule looks at.
		if ( $template && get_page_template_slug( $ids[ $slug ] ) !== $template ) {
			update_post_meta( $ids[ $slug ], '_wp_page_template', $template );
		}
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'page_for_posts', 0 );
	update_option( 'wp_page_for_privacy_policy', $ids['privacy'] );
	$log[] = 'עמוד הבית הוגדר כעמוד סטטי';

	$archive = static fn( string $type ) => [
		'menu-item-type'      => 'post_type_archive',
		'menu-item-object'    => $type,
		'menu-item-status'    => 'publish',
	];
	$page = static fn( int $id ) => [
		'menu-item-type'      => 'post_type',
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $id,
		'menu-item-status'    => 'publish',
	];

	$menus = [
		'primary' => [ 'תפריט ראשי', [
			[ 'פרויקטים', $archive( 'log_project' ) ],
			[ 'חברת הבקרה', $page( $ids['control'] ) ],
			[ 'אודות', $page( $ids['about'] ) ],
			[ 'מרכז ידע', $archive( 'log_knowledge' ) ],
			[ 'קריירה', $archive( 'log_job' ) ],
			[ 'צור קשר', $page( $ids['contact'] ) ],
		] ],
		'twin' => [ 'כפתורים צמודים', [
			[ 'ניהול פרויקטים', $archive( 'log_project' ) ],
			[ 'חברת הבקרה', $page( $ids['control'] ) ],
		] ],
		'footer' => [ 'תפריט תחתון', [
			[ 'הצהרת נגישות', $page( $ids['accessibility'] ) ],
			[ 'מדיניות פרטיות', $page( $ids['privacy'] ) ],
			[ 'מדיניות עוגיות', $page( $ids['cookies'] ) ],
		] ],
	];

	$locations = get_theme_mod( 'nav_menu_locations', [] );
	foreach ( $menus as $location => [ $name, $items ] ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $name );
			foreach ( $items as $i => [ $title, $args ] ) {
				wp_update_nav_menu_item( $menu_id, 0, $args + [
					'menu-item-title'    => $title,
					'menu-item-position' => $i + 1,
				] );
			}
			$log[] = "נוצר תפריט: $name";
		} else {
			$menu_id = $menu->term_id;
		}
		$locations[ $location ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	$log[] = 'תפריטים שויכו למיקומים בתבנית';

	// Default sample content WP ships with is noise on a company site.
	foreach ( [ 'hello-world' => 'post', 'sample-page' => 'page' ] as $slug => $type ) {
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p ) {
			wp_trash_post( $p->ID );
		}
	}

	// Yoast titles for the CPT archives (its default is "X Archive").
	$yoast = get_option( 'wpseo_titles' );
	if ( is_array( $yoast ) ) {
		$yoast['title-ptarchive-log_project']   = 'הפרויקטים שלנו %%sep%% %%sitename%%';
		$yoast['metadesc-ptarchive-log_project'] = 'כל הפרויקטים של לוג ניהול פרויקטים: תחומים, קני מידה ופריסה ארצית, על המפה ובגלריה.';
		$yoast['title-ptarchive-log_job']       = 'קריירה ומשרות פתוחות %%sep%% %%sitename%%';
		$yoast['metadesc-ptarchive-log_job']    = 'בואו לעבוד בלוג: המשרות הפתוחות, מסלול הגיוס והערכים שלנו.';
		$yoast['title-ptarchive-log_knowledge'] = 'מרכז ידע %%sep%% %%sitename%%';
		$yoast['metadesc-ptarchive-log_knowledge'] = 'ניירות עמדה, הודעות לעיתונות ומאחורי הקלעים של התכנון העירוני בישראל.';
		$yoast['title-search-wpseo']            = 'תוצאות חיפוש עבור %%searchphrase%% %%sep%% %%sitename%%';
		$yoast['title-404-wpseo']               = 'העמוד לא נמצא %%sep%% %%sitename%%';
		update_option( 'wpseo_titles', $yoast );
		$log[] = 'כותרות Yoast לארכיונים, חיפוש ו-404';
	}

	flush_rewrite_rules();
	$log[] = 'Rewrite rules רועננו';
	return $log;
}
