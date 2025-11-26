<?php
/**
 * Fired during plugin activation.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Set default options if they don't exist.
		$default_options = array(
			'scraping_method'   => 'simple',
			'firecrawl_api_key' => '',
			'max_images'        => 50,
			'timeout'           => 30,
		);

		if ( ! get_option( 'image_scraper_settings' ) ) {
			add_option( 'image_scraper_settings', $default_options );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
