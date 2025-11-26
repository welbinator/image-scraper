<?php
/**
 * Plugin Name:       Image Scraper
 * Plugin URI:        https://github.com/yourusername/image-scraper
 * Description:       Scrape images from websites using Firecrawl API and add them to your WordPress media library.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       image-scraper
 * Domain Path:       /languages
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'IMAGE_SCRAPER_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'IMAGE_SCRAPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'IMAGE_SCRAPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'IMAGE_SCRAPER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class The fully-qualified class name.
 */
function image_scraper_autoloader( $class ) {
	// Project-specific namespace prefix.
	$prefix = 'Image_Scraper\\';

	// Base directory for the namespace prefix.
	$base_dir = IMAGE_SCRAPER_PLUGIN_DIR . 'includes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class, $len );

	// Replace namespace separators with directory separators, and append .php.
	$file = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
}

spl_autoload_register( __NAMESPACE__ . '\\image_scraper_autoloader' );

/**
 * The code that runs during plugin activation.
 */
function activate_image_scraper() {
	require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-activator.php';
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_image_scraper() {
	require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-deactivator.php';
	Deactivator::deactivate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_image_scraper' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_image_scraper' );

/**
 * Initialize the plugin.
 */
function run_image_scraper() {
	require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-core.php';
	$plugin = new Core();
	$plugin->run();
}

run_image_scraper();
