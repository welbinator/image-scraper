<?php
/**
 * Media library importer with image processing.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper;

/**
 * Handles importing images to WordPress media library with processing.
 */
class Media_Importer {

	/**
	 * Import options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Errors encountered during import.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor.
	 *
	 * @param array $options Import options.
	 */
	public function __construct( $options = array() ) {
		$this->options = wp_parse_args(
			$options,
			array(
				'convert_format'  => '',
				'max_width'       => 0,
				'max_filesize'    => 0,
				'filename_prefix' => '',
				'image_alt'       => '',
				'image_title'     => '',
			)
		);

		// Ensure WP image functions are available.
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
	}

	/**
	 * Import multiple images.
	 *
	 * @param array $images Array of image data with 'url', 'filename', 'alt', etc.
	 * @return array Results with 'imported_count', 'total_count', 'errors'.
	 */
	public function import_images( $images ) {
		$imported_count = 0;
		$total_count    = count( $images );
		$this->errors   = array();

		foreach ( $images as $index => $image ) {
			// Pass the index for sequential naming when prefix is used.
			$image['import_index'] = $index;
			$result = $this->import_single_image( $image );
			
			if ( ! is_wp_error( $result ) ) {
				$imported_count++;
			} else {
				$this->errors[] = sprintf(
					/* translators: 1: filename, 2: error message */
					__( '%1$s: %2$s', 'image-scraper' ),
					isset( $image['filename'] ) ? $image['filename'] : __( 'Unknown file', 'image-scraper' ),
					$result->get_error_message()
				);
			}
		}

		return array(
			'imported_count' => $imported_count,
			'total_count'    => $total_count,
			'errors'         => $this->errors,
		);
	}

	/**
	 * Import a single image.
	 *
	 * @param array $image Image data.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	private function import_single_image( $image ) {
		if ( empty( $image['url'] ) ) {
			return new \WP_Error( 'no_url', __( 'No image URL provided.', 'image-scraper' ) );
		}

		// Download image with retry logic.
		$temp_file = $this->download_with_retry( $image['url'], 3 );

		if ( is_wp_error( $temp_file ) ) {
			return new \WP_Error(
				'download_failed',
				sprintf(
					/* translators: 1: image URL, 2: error message */
					__( 'Could not download %1$s - %2$s', 'image-scraper' ),
					esc_url( $image['url'] ),
					$temp_file->get_error_message()
				)
			);
		}

		// Process the image (resize, convert, compress).
		$processed_file = $this->process_image( $temp_file, $image );

		if ( is_wp_error( $processed_file ) ) {
			@unlink( $temp_file );
			return $processed_file;
		}

		// Generate final filename.
		$filename = $this->generate_filename( $image );

		// Prepare file array for media_handle_sideload.
		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $processed_file,
		);

		// Import to media library.
		$attachment_id = media_handle_sideload( $file_array, 0 );

		// Clean up temp files.
		@unlink( $temp_file );
		if ( $processed_file !== $temp_file ) {
			@unlink( $processed_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set metadata.
		$this->set_image_metadata( $attachment_id, $image );

		return $attachment_id;
	}

	/**
	 * Process image (resize, convert format, compress).
	 *
	 * @param string $file_path Path to the image file.
	 * @param array  $image     Image data.
	 * @return string|WP_Error Path to processed file or WP_Error.
	 */
	private function process_image( $file_path, $image ) {
		// Load image with WP_Image_Editor.
		$editor = wp_get_image_editor( $file_path );

		if ( is_wp_error( $editor ) ) {
			return new \WP_Error(
				'image_load_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Could not load image: %s', 'image-scraper' ),
					$editor->get_error_message()
				)
			);
		}

		// Resize if max_width is set.
		if ( ! empty( $this->options['max_width'] ) && $this->options['max_width'] > 0 ) {
			$size = $editor->get_size();
			
			if ( $size['width'] > $this->options['max_width'] ) {
				$editor->resize( $this->options['max_width'], null, false );
			}
		}

		// Set initial quality.
		$quality = 90;
		$editor->set_quality( $quality );

		// Determine output format.
		$output_format = $this->get_output_format( $file_path );

		// Save to temp file.
		$temp_output = wp_tempnam();
		$saved       = $editor->save( $temp_output, $output_format );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$output_file = $saved['path'];

		// Compress to max filesize if needed.
		if ( ! empty( $this->options['max_filesize'] ) && $this->options['max_filesize'] > 0 ) {
			$output_file = $this->compress_to_filesize( $output_file, $this->options['max_filesize'], $output_format );
			
			if ( is_wp_error( $output_file ) ) {
				@unlink( $temp_output );
				return $output_file;
			}
		}

		return $output_file;
	}

	/**
	 * Compress image to target file size.
	 *
	 * @param string $file_path      Path to the image file.
	 * @param int    $max_size_kb    Maximum file size in KB.
	 * @param string $output_format  Output mime type.
	 * @return string|WP_Error Path to compressed file or WP_Error.
	 */
	private function compress_to_filesize( $file_path, $max_size_kb, $output_format ) {
		$max_size_bytes = $max_size_kb * 1024;
		$current_size   = filesize( $file_path );

		// Already under limit.
		if ( $current_size <= $max_size_bytes ) {
			return $file_path;
		}

		$editor = wp_get_image_editor( $file_path );

		if ( is_wp_error( $editor ) ) {
			return $file_path; // Return original if we can't compress.
		}

		// Try reducing quality iteratively.
		$quality     = 85;
		$min_quality = 40;
		$attempts    = 0;
		$max_attempts = 10;

		while ( $current_size > $max_size_bytes && $quality >= $min_quality && $attempts < $max_attempts ) {
			$editor->set_quality( $quality );
			
			$temp_file = wp_tempnam();
			$saved     = $editor->save( $temp_file, $output_format );

			if ( is_wp_error( $saved ) ) {
				@unlink( $temp_file );
				break;
			}

			$new_size = filesize( $saved['path'] );

			// If we achieved the target, use this file.
			if ( $new_size <= $max_size_bytes ) {
				@unlink( $file_path );
				return $saved['path'];
			}

			// Update for next iteration.
			$current_size = $new_size;
			@unlink( $file_path );
			$file_path = $saved['path'];
			
			$quality -= 5;
			$attempts++;
		}

		// If we couldn't get it small enough, return the best we got.
		if ( $current_size > $max_size_bytes ) {
			return new \WP_Error(
				'compression_failed',
				sprintf(
					/* translators: 1: current size in KB, 2: target size in KB */
					__( 'Could not compress to %2$d KB (best achieved: %1$d KB).', 'image-scraper' ),
					round( $current_size / 1024 ),
					$max_size_kb
				)
			);
		}

		return $file_path;
	}

	/**
	 * Get output format based on options.
	 *
	 * @param string $original_file Original file path.
	 * @return string Mime type.
	 */
	private function get_output_format( $original_file ) {
		// If conversion is requested, use that format.
		if ( ! empty( $this->options['convert_format'] ) ) {
			switch ( $this->options['convert_format'] ) {
				case 'webp':
					return 'image/webp';
				case 'jpeg':
				case 'jpg':
					return 'image/jpeg';
				case 'png':
					return 'image/png';
			}
		}

		// Otherwise, keep original format.
		$mime_type = wp_check_filetype( $original_file )['type'];
		return $mime_type ? $mime_type : 'image/jpeg';
	}

	/**
	 * Generate filename with prefix.
	 *
	 * @param array $image Image data.
	 * @return string Filename.
	 */
	private function generate_filename( $image ) {
		$has_prefix = ! empty( $this->options['filename_prefix'] );
		
		// Determine file extension.
		$extension = 'jpg';
		
		if ( ! empty( $this->options['convert_format'] ) ) {
			// Use the conversion format extension.
			$extension = $this->options['convert_format'];
		} elseif ( isset( $image['filename'] ) ) {
			// Extract extension from original filename.
			$pathinfo = pathinfo( $image['filename'] );
			$extension = isset( $pathinfo['extension'] ) ? $pathinfo['extension'] : 'jpg';
		}

		// If prefix is set, use sequential naming.
		if ( $has_prefix ) {
			$prefix = sanitize_file_name( $this->options['filename_prefix'] );
			$index = isset( $image['import_index'] ) ? $image['import_index'] : 0;
			
			// First image: [prefix].ext
			// Second image: [prefix]_1.ext
			// Third image: [prefix]_2.ext
			if ( $index === 0 ) {
				$filename = $prefix . '.' . $extension;
			} else {
				$filename = $prefix . '_' . $index . '.' . $extension;
			}
		} else {
			// No prefix - use original filename or generate one.
			$filename = isset( $image['filename'] ) ? $image['filename'] : 'image-' . time() . '.jpg';
			
			// Update extension if format was converted.
			if ( ! empty( $this->options['convert_format'] ) ) {
				$pathinfo = pathinfo( $filename );
				$filename = $pathinfo['filename'] . '.' . $extension;
			}
		}

		return $filename;
	}

	/**
	 * Set image metadata (alt text, title, etc.).
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $image         Image data.
	 */
	private function set_image_metadata( $attachment_id, $image ) {
		// Determine alt text.
		$alt = ! empty( $this->options['image_alt'] ) 
			? $this->options['image_alt'] 
			: ( isset( $image['alt'] ) ? $image['alt'] : '' );

		if ( ! empty( $alt ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}

		// Determine title.
		$title = ! empty( $this->options['image_title'] ) 
			? $this->options['image_title'] 
			: ( isset( $image['alt'] ) ? $image['alt'] : '' );

		if ( ! empty( $title ) ) {
			wp_update_post(
				array(
					'ID'         => $attachment_id,
					'post_title' => sanitize_text_field( $title ),
				)
			);
		}
	}

	/**
	 * Download image with retry logic.
	 *
	 * @param string $url          The image URL to download.
	 * @param int    $max_retries  Maximum number of retry attempts.
	 * @return string|WP_Error Path to downloaded file or WP_Error on failure.
	 */
	private function download_with_retry( $url, $max_retries = 3 ) {
		$attempt = 0;
		$last_error = null;

		while ( $attempt < $max_retries ) {
			$attempt++;

			// Download image to temp file with longer timeout.
			$temp_file = download_url( $url, 60 );

			// Success - return the file.
			if ( ! is_wp_error( $temp_file ) ) {
				return $temp_file;
			}

			// Store the error.
			$last_error = $temp_file;

			// If this isn't the last attempt, wait a bit before retrying.
			if ( $attempt < $max_retries ) {
				// Exponential backoff: 1s, 2s, 4s.
				sleep( pow( 2, $attempt - 1 ) );
			}
		}

		// All retries failed - return the last error with retry info.
		return new \WP_Error(
			'download_failed_after_retries',
			sprintf(
				/* translators: 1: number of attempts, 2: error message */
				__( 'Failed after %1$d attempts: %2$s', 'image-scraper' ),
				$max_retries,
				$last_error->get_error_message()
			)
		);
	}
}
