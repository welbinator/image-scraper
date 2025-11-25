<?php
/**
 * Define the internationalization functionality.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'image-scraper',
			false,
			dirname( IMAGE_SCRAPER_BASENAME ) . '/languages/'
		);
	}
}
