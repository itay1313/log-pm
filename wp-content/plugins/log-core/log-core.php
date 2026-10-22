<?php
/**
 * Plugin Name: LOG Core
 * Description: Content model for log-pm.co.il — projects, jobs, knowledge, people, clients, the map endpoint and structured data. Survives a theme change.
 * Version: 0.1.0
 * Author: com-mando
 * Author URI: https://itaycode.com
 * Text Domain: log-core
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'LOG_CORE_VERSION', '0.1.0' );
define( 'LOG_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOG_CORE_URL', plugin_dir_url( __FILE__ ) );

require LOG_CORE_DIR . 'inc/post-types.php';
require LOG_CORE_DIR . 'inc/taxonomies.php';
require LOG_CORE_DIR . 'inc/stage.php';
require LOG_CORE_DIR . 'inc/projects.php';
require LOG_CORE_DIR . 'inc/migrate.php';
require LOG_CORE_DIR . 'inc/acf.php';
require LOG_CORE_DIR . 'inc/rest-map.php';
require LOG_CORE_DIR . 'inc/schema.php';
require LOG_CORE_DIR . 'inc/setup.php';
require LOG_CORE_DIR . 'inc/import.php';
require LOG_CORE_DIR . 'inc/arcgis-sync.php';
require LOG_CORE_DIR . 'inc/forms.php';
require LOG_CORE_DIR . 'inc/search.php';
require LOG_CORE_DIR . 'inc/social.php';
require LOG_CORE_DIR . 'inc/admin-list.php';
require LOG_CORE_DIR . 'inc/coming-soon.php';

register_activation_hook( __FILE__, function () {
	log_register_post_types();
	log_register_taxonomies();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'log_instagram_sync' );
	wp_clear_scheduled_hook( 'log_arcgis_sync' );
	flush_rewrite_rules();
} );
