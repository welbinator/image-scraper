<?php
/**
 * The core plugin class.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version     = IMAGE_SCRAPER_VERSION;
		$this->plugin_name = 'image-scraper';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-loader.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-i18n.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-firecrawl-api.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-html-scraper.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'includes/class-media-importer.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'admin/class-admin.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'admin/class-settings.php';
		require_once IMAGE_SCRAPER_PLUGIN_DIR . 'admin/class-ajax-handler.php';

		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Admin\Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Settings.
		$plugin_settings = new Admin\Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

		// AJAX handlers.
		$ajax_handler = new Admin\Ajax_Handler( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_image_scraper_scrape', $ajax_handler, 'handle_scrape' );
		$this->loader->add_action( 'wp_ajax_image_scraper_import', $ajax_handler, 'handle_import' );
		$this->loader->add_action( 'wp_ajax_image_scraper_validate_api', $ajax_handler, 'handle_validate_api' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
