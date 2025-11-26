<?php
/**
 * Direct HTML scraping without external API.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * Handles direct HTML scraping using WordPress HTTP API.
 */
class Html_Scraper {

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
		$settings      = get_option( 'image_scraper_settings', array() );
		$this->timeout = isset( $settings['timeout'] ) ? absint( $settings['timeout'] ) : 30;
	}

	/**
	 * Scrape a URL and extract images.
	 *
	 * @param string $url          The URL to scrape.
	 * @param string $target_class Optional CSS class to filter images.
	 * @return array|WP_Error Array of image data or WP_Error on failure.
	 */
	public function scrape_url( $url, $target_class = '' ) {
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', __( 'Invalid URL provided.', 'image-scraper' ) );
		}

		// Fetch HTML content.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => $this->timeout,
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'headers'    => array(
					'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					'Accept-Language' => 'en-US,en;q=0.9',
					'Cache-Control'   => 'no-cache',
				),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Request failed: %s', 'image-scraper' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// Check response status.
		if ( $status_code !== 200 ) {
			return new \WP_Error(
				'http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'HTTP error %d: Could not fetch the page.', 'image-scraper' ),
					$status_code
				)
			);
		}

		$html = wp_remote_retrieve_body( $response );

		if ( empty( $html ) ) {
			return new \WP_Error( 'empty_response', __( 'Received empty response from server.', 'image-scraper' ) );
		}

		// Extract images from HTML.
		$images = $this->extract_images_from_html( $html, $url, $target_class );

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

			// Also check srcset and data-src for lazy-loaded images.
			if ( empty( $src ) ) {
				$src = $img->getAttribute( 'data-src' );
			}
			if ( empty( $src ) ) {
				$srcset = $img->getAttribute( 'srcset' );
				if ( ! empty( $srcset ) ) {
					// Parse srcset and get the largest image.
					$src = $this->parse_srcset( $srcset );
				}
			}

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

			// Skip common placeholder/tracking images.
			if ( $this->is_placeholder_image( $src ) ) {
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
	 * Parse srcset attribute and return largest image URL.
	 *
	 * @param string $srcset The srcset attribute value.
	 * @return string Image URL.
	 */
	private function parse_srcset( $srcset ) {
		$sources = explode( ',', $srcset );
		$largest = '';
		$max_width = 0;

		foreach ( $sources as $source ) {
			$parts = preg_split( '/\s+/', trim( $source ) );
			if ( ! empty( $parts[0] ) ) {
				$url = $parts[0];
				$width = 0;

				// Extract width if specified (e.g., "1200w").
				if ( isset( $parts[1] ) && preg_match( '/(\d+)w/', $parts[1], $matches ) ) {
					$width = intval( $matches[1] );
				}

				if ( $width > $max_width ) {
					$max_width = $width;
					$largest = $url;
				} elseif ( empty( $largest ) ) {
					$largest = $url;
				}
			}
		}

		return $largest;
	}

	/**
	 * Check if URL is likely a placeholder/tracking image.
	 *
	 * @param string $url The image URL.
	 * @return bool True if placeholder, false otherwise.
	 */
	private function is_placeholder_image( $url ) {
		$placeholder_patterns = array(
			'/1x1/',
			'/pixel\.(gif|png|jpg)/',
			'/transparent\.(gif|png)/',
			'/spacer\.(gif|png)/',
			'/blank\.(gif|png|jpg)/',
			'/tracking/',
			'/analytics/',
		);

		foreach ( $placeholder_patterns as $pattern ) {
			if ( preg_match( $pattern, strtolower( $url ) ) ) {
				return true;
			}
		}

		return false;
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
