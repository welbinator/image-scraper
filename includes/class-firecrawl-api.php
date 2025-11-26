<?php
/**
 * Firecrawl API integration.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * Handles communication with the Firecrawl API.
 */
class Firecrawl_Api {

	/**
	 * Firecrawl API base URL.
	 *
	 * @var string
	 */
	private $api_base_url = 'https://api.firecrawl.dev/v1';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings       = get_option( 'image_scraper_settings', array() );
		$this->api_key  = isset( $settings['firecrawl_api_key'] ) ? $settings['firecrawl_api_key'] : '';
		$this->timeout  = isset( $settings['timeout'] ) ? absint( $settings['timeout'] ) : 30;
	}

	/**
	 * Validate API key by making a test request.
	 *
	 * @return true|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate_api_key() {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'API key is empty.', 'image-scraper' ) );
		}

		// Simple validation - check if it's not empty and has reasonable length.
		if ( strlen( $this->api_key ) < 10 ) {
			return new \WP_Error( 'invalid_api_key', __( 'API key appears to be invalid (too short).', 'image-scraper' ) );
		}

		// Make a test request to validate the API key.
		$response = wp_remote_get(
			$this->api_base_url . '/scrape',
			array(
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
			)
		);

		// Check for network errors.
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'image-scraper' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// 401 = invalid API key, 405 = valid key but wrong method (expected for GET on /scrape).
		// 200 would also indicate valid key.
		if ( $status_code === 401 || $status_code === 403 ) {
			return new \WP_Error( 'invalid_api_key', __( 'API key is invalid or unauthorized.', 'image-scraper' ) );
		}

		// Any other response means the API key was accepted.
		return true;
	}

	/**
	 * Scrape a URL and extract images.
	 *
	 * @param string $url          The URL to scrape.
	 * @param string $target_class Optional CSS class to filter images.
	 * @return array|WP_Error Array of image data or WP_Error on failure.
	 */
	public function scrape_url( $url, $target_class = '' ) {
		// Quick validation.
		if ( empty( $this->api_key ) || strlen( $this->api_key ) < 10 ) {
			return new \WP_Error( 'no_api_key', __( 'Firecrawl API key is not configured.', 'image-scraper' ) );
		}

		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', __( 'Invalid URL provided.', 'image-scraper' ) );
		}

		// Prepare request body.
		$body = array(
			'url'     => $url,
			'formats' => array( 'html', 'markdown' ),
		);

		// Make API request.
		$response = wp_remote_post(
			$this->api_base_url . '/scrape',
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'API request failed: %s', 'image-scraper' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		// Check response status.
		if ( $status_code !== 200 ) {
			$error_message = isset( $data['error'] ) ? $data['error'] : __( 'Unknown API error', 'image-scraper' );
			return new \WP_Error( 'api_error', $error_message );
		}

		// Check if we got valid data.
		if ( ! isset( $data['data'] ) || ! isset( $data['data']['html'] ) ) {
			return new \WP_Error( 'invalid_response', __( 'Invalid API response format.', 'image-scraper' ) );
		}

		// Extract images from HTML.
		$images = $this->extract_images_from_html( $data['data']['html'], $url, $target_class );

		return $images;
	}

	/**
	 * Extract images from HTML content.
	 *
	 * @param string $html         The HTML content.
	 * @param string $base_url     The base URL for resolving relative URLs.
	 * @param string $target_class Optional CSS class to filter images.
	 * @return array Array of image data.
	 */
	private function extract_images_from_html( $html, $base_url, $target_class = '' ) {
		if ( empty( $html ) ) {
			return array();
		}

		// Suppress libxml errors.
		libxml_use_internal_errors( true );

		// Create DOMDocument.
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Clear errors.
		libxml_clear_errors();

		$images = array();
		$xpath  = new \DOMXPath( $dom );

		// Build XPath query.
		if ( ! empty( $target_class ) ) {
			// Target images with specific class.
			$query = "//img[contains(concat(' ', normalize-space(@class), ' '), ' " . esc_attr( $target_class ) . " ')]";
		} else {
			// All images.
			$query = '//img';
		}

		$img_elements = $xpath->query( $query );

		if ( ! $img_elements || $img_elements->length === 0 ) {
			return array();
		}

		$settings   = get_option( 'image_scraper_settings', array() );
		$max_images = isset( $settings['max_images'] ) ? absint( $settings['max_images'] ) : 50;
		$count      = 0;

		foreach ( $img_elements as $img ) {
			if ( $count >= $max_images ) {
				break;
			}

			$src = $img->getAttribute( 'src' );

			// Skip empty or data URIs.
			if ( empty( $src ) || strpos( $src, 'data:' ) === 0 ) {
				continue;
			}

			// Resolve relative URLs.
			$src = $this->resolve_url( $src, $base_url );

			// Validate URL.
			if ( ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// Get image attributes.
			$alt    = $img->getAttribute( 'alt' );
			$width  = $img->getAttribute( 'width' );
			$height = $img->getAttribute( 'height' );

			// Generate filename from URL.
			$filename = $this->generate_filename_from_url( $src );

			$images[] = array(
				'url'      => $src,
				'alt'      => $alt,
				'width'    => $width ? absint( $width ) : null,
				'height'   => $height ? absint( $height ) : null,
				'filename' => $filename,
			);

			$count++;
		}

		return $images;
	}

	/**
	 * Resolve relative URLs to absolute.
	 *
	 * @param string $url      The URL to resolve.
	 * @param string $base_url The base URL.
	 * @return string Resolved URL.
	 */
	private function resolve_url( $url, $base_url ) {
		// Already absolute.
		if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return $url;
		}

		// Protocol-relative URL.
		if ( strpos( $url, '//' ) === 0 ) {
			$parsed_base = wp_parse_url( $base_url );
			return $parsed_base['scheme'] . ':' . $url;
		}

		$parsed_base = wp_parse_url( $base_url );

		// Root-relative URL.
		if ( strpos( $url, '/' ) === 0 ) {
			return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
		}

		// Relative URL.
		$base_path = isset( $parsed_base['path'] ) ? dirname( $parsed_base['path'] ) : '';
		return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $base_path . '/' . $url;
	}

	/**
	 * Generate filename from URL.
	 *
	 * @param string $url The image URL.
	 * @return string Filename.
	 */
	private function generate_filename_from_url( $url ) {
		$parsed = wp_parse_url( $url );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';
		
		if ( empty( $path ) ) {
			return 'image-' . time() . '.jpg';
		}

		$filename = basename( $path );

		// Remove query strings.
		$filename = strtok( $filename, '?' );

		// Sanitize filename.
		$filename = sanitize_file_name( $filename );

		// Ensure we have an extension.
		if ( ! preg_match( '/\.(jpg|jpeg|png|gif|webp|svg)$/i', $filename ) ) {
			$filename .= '.jpg';
		}

		return $filename;
	}
}
