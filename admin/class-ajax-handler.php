<?php
/**
 * AJAX handlers for the plugin.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper\Admin;

/**
 * Handles AJAX requests for scraping and importing.
 */
class Ajax_Handler {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Handle scrape request.
	 */
	public function handle_scrape() {
		// Verify nonce.
		check_ajax_referer( 'image_scraper_nonce', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'image-scraper' ) )
			);
		}

		// Get and sanitize input.
		$target_url   = isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '';
		$target_class = isset( $_POST['target_class'] ) ? sanitize_text_field( wp_unslash( $_POST['target_class'] ) ) : '';

		// Validate URL.
		if ( empty( $target_url ) || ! filter_var( $target_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Please provide a valid URL.', 'image-scraper' ) )
			);
		}

		// Get scraping method from settings.
		$options = get_option( 'image_scraper_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';

		// Create scraper instance based on selected method.
		if ( $method === 'firecrawl' ) {
			// Check if API key is configured.
			$api_key = isset( $options['firecrawl_api_key'] ) ? $options['firecrawl_api_key'] : '';
			if ( empty( $api_key ) ) {
				wp_send_json_error(
					array( 'message' => __( 'Firecrawl API key not configured. Please configure it in settings or switch to Simple Mode.', 'image-scraper' ) )
				);
			}
			
			// Initialize Firecrawl API.
			$scraper = new \Image_Scraper\Firecrawl_Api();
		} else {
			// Initialize HTML Scraper (Simple Mode).
			$scraper = new \Image_Scraper\Html_Scraper();
		}

		// Scrape the URL.
		$images = $scraper->scrape_url( $target_url, $target_class );

		// Check for errors.
		if ( is_wp_error( $images ) ) {
			wp_send_json_error(
				array( 'message' => $images->get_error_message() )
			);
		}

		// Return success with images.
		wp_send_json_success(
			array(
				'images'       => $images,
				'images_count' => count( $images ),
			)
		);
	}

	/**
	 * Handle API key validation request.
	 */
	public function handle_validate_api() {
		// Verify nonce.
		check_ajax_referer( 'image_scraper_nonce', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'image-scraper' ) )
			);
		}

		// Get scraping method from settings.
		$options = get_option( 'image_scraper_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';

		// Only validate if using Firecrawl.
		if ( $method !== 'firecrawl' ) {
			wp_send_json_error(
				array( 'message' => __( 'API validation is only available when using Firecrawl method.', 'image-scraper' ) )
			);
		}

		// Initialize Firecrawl API.
		$api = new \Image_Scraper\Firecrawl_Api();

		// Validate the API key.
		$result = $api->validate_api_key();

		// Check for errors.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		// Return success.
		wp_send_json_success(
			array( 'message' => __( 'API key is valid and working!', 'image-scraper' ) )
		);
	}

	/**
	 * Handle import request.
	 */
	public function handle_import() {
		// Verify nonce.
		check_ajax_referer( 'image_scraper_nonce', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'image-scraper' ) )
			);
		}

		// Get images data.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$images_json = isset( $_POST['images'] ) ? wp_unslash( $_POST['images'] ) : '';
		$images      = json_decode( wp_json_encode( $images_json ), true );

		if ( empty( $images ) || ! is_array( $images ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No images provided for import.', 'image-scraper' ) )
			);
		}

		// Get global/default options.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$options_json = isset( $_POST['options'] ) ? wp_unslash( $_POST['options'] ) : '';
		$options_raw  = json_decode( wp_json_encode( $options_json ), true );

		// Sanitize global options.
		$global_options = array(
			'convert_format'  => isset( $options_raw['convert_format'] ) ? sanitize_text_field( $options_raw['convert_format'] ) : '',
			'max_width'       => isset( $options_raw['max_width'] ) ? absint( $options_raw['max_width'] ) : 0,
			'max_filesize'    => isset( $options_raw['max_filesize'] ) ? absint( $options_raw['max_filesize'] ) : 0,
			'filename_prefix' => isset( $options_raw['filename_prefix'] ) ? sanitize_file_name( $options_raw['filename_prefix'] ) : '',
			'image_alt'       => isset( $options_raw['image_alt'] ) ? sanitize_text_field( $options_raw['image_alt'] ) : '',
			'image_title'     => isset( $options_raw['image_title'] ) ? sanitize_text_field( $options_raw['image_title'] ) : '',
		);

		// Process each image with merged options (individual settings override global).
		foreach ( $images as $index => &$image ) {
			if ( isset( $image['individual_settings'] ) && is_array( $image['individual_settings'] ) ) {
				$individual = $image['individual_settings'];
				
				// Merge individual settings into the image - individual overrides global.
				if ( ! empty( $individual['filename'] ) ) {
					$image['custom_filename'] = sanitize_file_name( $individual['filename'] );
				}
				if ( ! empty( $individual['alt'] ) ) {
					$image['custom_alt'] = sanitize_text_field( $individual['alt'] );
				}
				if ( ! empty( $individual['title'] ) ) {
					$image['custom_title'] = sanitize_text_field( $individual['title'] );
				}
				if ( ! empty( $individual['format'] ) ) {
					$image['custom_format'] = sanitize_text_field( $individual['format'] );
				}
				if ( ! empty( $individual['max_width'] ) ) {
					$image['custom_max_width'] = absint( $individual['max_width'] );
				}
				if ( ! empty( $individual['max_size'] ) ) {
					$image['custom_max_size'] = absint( $individual['max_size'] );
				}
				
				// Clean up the individual_settings key.
				unset( $image['individual_settings'] );
			}
		}

		// Initialize Media Importer with global options.
		$importer = new \Image_Scraper\Media_Importer( $global_options );

		// Import images.
		$result = $importer->import_images( $images );

		// Return results.
		wp_send_json_success( $result );
	}
}
