<?php
/**
 * Plugin Name: LOG Core
 * Description: Content model for log-pm.co.il — projects, jobs, knowledge, people, clients, the map endpoint and structured data. Survives a theme change.
 * Version: 0.1.0
 * Author: com-mando
 * Text Domain: log-core
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'LOG_CORE_VERSION', '0.1.0' );
define( 'LOG_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOG_CORE_URL', plugin_dir_url( __FILE__ ) );

require LOG_CORE_DIR . 'inc/post-types.php';
require LOG_CORE_DIR . 'inc/taxonomies.php';
require LOG_CORE_DIR . 'inc/scale.php';
require LOG_CORE_DIR . 'inc/acf.php';
require LOG_CORE_DIR . 'inc/rest-map.php';
require LOG_CORE_DIR . 'inc/schema.php';
require LOG_CORE_DIR . 'inc/setup.php';

register_activation_hook( __FILE__, function () {
	log_register_post_types();
	log_register_taxonomies();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
