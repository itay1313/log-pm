<?php
/**
 * LOG theme bootstrap.
 */

defined( 'ABSPATH' ) || exit;

define( 'LOG_THEME_VERSION', '0.1.0' );
define( 'LOG_THEME_DIR', get_template_directory() );
define( 'LOG_THEME_URI', get_template_directory_uri() );

require LOG_THEME_DIR . '/inc/setup.php';
require LOG_THEME_DIR . '/inc/assets.php';
require LOG_THEME_DIR . '/inc/helpers.php';
require LOG_THEME_DIR . '/inc/cleanup.php';
