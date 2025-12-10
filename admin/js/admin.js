/**
 * Admin area JavaScript for Image Scraper plugin.
 *
 * @package Image_Scraper
 */

(function( $ ) {
	'use strict';

	var scrapedImages = [];

	$(document).ready(function() {
		
		/**
		 * Test API key connection.
		 */
		$('#test-api-key').on('click', function() {
			var $button = $(this);
			var $result = $('#api-test-result');

			// Disable button and show loading.
			$button.prop('disabled', true).text('Testing...');
			$result.html('<span style="color: #999;">Validating API key...</span>');

			// Make AJAX request.
			$.ajax({
				url: imageScraperAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'image_scraper_validate_api',
					nonce: imageScraperAdmin.nonce
				},
				success: function(response) {
					$button.prop('disabled', false).text('Test API Connection');

					if (response.success) {
						$result.html('<span style="color: #46b450; font-weight: 600;">✓ ' + escapeHtml(response.data.message) + '</span>');
					} else {
						$result.html('<span style="color: #dc3232; font-weight: 600;">✗ ' + escapeHtml(response.data.message) + '</span>');
					}

					// Clear message after 5 seconds.
					setTimeout(function() {
						$result.fadeOut(function() {
							$(this).html('').show();
						});
					}, 5000);
				},
				error: function() {
					$button.prop('disabled', false).text('Test API Connection');
					$result.html('<span style="color: #dc3232; font-weight: 600;">✗ Network error occurred.</span>');
				}
			});
		});

		/**
		 * Enable/disable test button when API key field changes.
		 */
		$('#firecrawl_api_key').on('input', function() {
			var hasValue = $(this).val().trim().length > 0;
			$('#test-api-key').prop('disabled', !hasValue);
		});

		/**
		 * Toggle class selector input visibility.
		 */
		$('#target_class_toggle').on('change', function() {
			if ($(this).is(':checked')) {
				$('#target_class_wrapper').slideDown();
			} else {
				$('#target_class_wrapper').slideUp();
				$('#target_class').val('');
			}
		});

		/**
		 * Toggle convert format dropdown visibility.
		 */
		$('#convert_format_toggle').on('change', function() {
			if ($(this).is(':checked')) {
				$('#convert_format_wrapper').slideDown();
			} else {
				$('#convert_format_wrapper').slideUp();
			}
		});

		/**
		 * Handle initial scrape form submission.
		 */
		$('#image-scraper-form').on('submit', function(e) {
			e.preventDefault();

			var $form = $(this);
			var $submitBtn = $form.find('input[type="submit"]');
			var $progress = $('#scraping-progress');
			var $results = $('#scraping-results');
			var targetUrl = $('#target_url').val();
			var targetClass = $('#target_class_toggle').is(':checked') ? $('#target_class').val() : '';

			// Validate URL.
			if (!targetUrl) {
				alert('Please enter a valid URL.');
				return;
			}

			// Clean up class name (remove leading dot if present).
			if (targetClass) {
				targetClass = targetClass.replace(/^\.+/, '');
			}

			// Hide previous results.
			$results.hide();
			$('#import-results').hide();

			// Disable submit button and show progress.
			$submitBtn.prop('disabled', true);
			$progress.show();
			$('.progress-message').text('Scraping images from URL...');

			// Prepare data.
			var data = {
				action: 'image_scraper_scrape',
				nonce: imageScraperAdmin.nonce,
				target_url: targetUrl,
				target_class: targetClass
			};

			// Make AJAX request.
			$.ajax({
				url: imageScraperAdmin.ajax_url,
				type: 'POST',
				data: data,
				success: function(response) {
					$progress.hide();
					$submitBtn.prop('disabled', false);

					if (response.success) {
						scrapedImages = response.data.images || [];
						displayImagePreviews(response.data);
					} else {
						displayScrapeError(response.data.message || 'An error occurred while scraping.');
					}
				},
				error: function(xhr, status, error) {
					$progress.hide();
					$submitBtn.prop('disabled', false);
					displayScrapeError('Network error: ' + error);
				}
			});
		});

		/**
		 * Display image previews after scraping.
		 *
		 * @param {Object} data The response data.
		 */
		function displayImagePreviews(data) {
			var $results = $('#scraping-results');
			var $preview = $('#scraped-images-preview');
			var $summary = $results.find('.images-found-message');

			if (!data.images || data.images.length === 0) {
				$summary.html('<div class="notice notice-warning"><p>No images found at the specified URL.</p></div>');
				$preview.html('');
				$results.show();
				return;
			}

			// Display summary.
			$summary.html('<div class="notice notice-success"><p>Found ' + data.images.length + ' image(s). Review and configure import options below.</p></div>');

			// Display image grid.
			var html = '';
			data.images.forEach(function(image, index) {
				html += '<div class="scraped-image-item" data-index="' + index + '">';
				html += '<div class="image-checkbox-wrapper">';
				html += '<label>';
				html += '<input type="checkbox" class="image-select-checkbox" data-index="' + index + '" checked>';
				html += '<span class="checkbox-label">Import</span>';
				html += '</label>';
				html += '</div>';
				html += '<img src="' + escapeHtml(image.url) + '" alt="' + escapeHtml(image.alt || 'Image ' + (index + 1)) + '" loading="lazy">';
				html += '<p class="image-filename">' + escapeHtml(image.filename || 'image-' + (index + 1)) + '</p>';
				if (image.width && image.height) {
					html += '<p class="image-dimensions">' + image.width + ' × ' + image.height + 'px</p>';
				}
				html += '<button type="button" class="button button-small edit-image-settings" data-index="' + index + '">Edit Settings</button>';
				
				// Individual settings form (hidden by default)
				html += '<div class="individual-settings" style="display: none;">';
				html += '<h4>Individual Settings</h4>';
				html += '<table class="form-table-compact">';
				
				// Filename prefix
				html += '<tr>';
				html += '<td><label>Filename:</label></td>';
				html += '<td><input type="text" class="img-filename" placeholder="Leave empty to use original" /></td>';
				html += '</tr>';
				
				// Alt text
				html += '<tr>';
				html += '<td><label>Alt Text:</label></td>';
				html += '<td><input type="text" class="img-alt" placeholder="Leave empty for default" /></td>';
				html += '</tr>';
				
				// Title
				html += '<tr>';
				html += '<td><label>Title:</label></td>';
				html += '<td><input type="text" class="img-title" placeholder="Leave empty for default" /></td>';
				html += '</tr>';
				
				// Format
				html += '<tr>';
				html += '<td><label>Format:</label></td>';
				html += '<td><select class="img-format">';
				html += '<option value="">Keep Original</option>';
				html += '<option value="webp">WebP</option>';
				html += '<option value="jpeg">JPEG</option>';
				html += '<option value="png">PNG</option>';
				html += '</select></td>';
				html += '</tr>';
				
				// Max width
				html += '<tr>';
				html += '<td><label>Max Width:</label></td>';
				html += '<td><input type="number" class="img-max-width small-text" placeholder="px" min="1" /></td>';
				html += '</tr>';
				
				// Max filesize
				html += '<tr>';
				html += '<td><label>Max Size:</label></td>';
				html += '<td><input type="number" class="img-max-size small-text" placeholder="KB" min="1" /></td>';
				html += '</tr>';
				
				html += '</table>';
				html += '<button type="button" class="button button-small close-settings">Close</button>';
				html += '</div>';
				
				html += '</div>';
			});

			$preview.html(html);
			$results.show();

			// Initialize button text with count.
			updateSelectedCount();

			// Scroll to results.
			$('html, body').animate({
				scrollTop: $results.offset().top - 50
			}, 500);
		}

		/**
		 * Display scraping error.
		 *
		 * @param {string} message The error message.
		 */
		function displayScrapeError(message) {
			var $results = $('#scraping-results');
			var $summary = $results.find('.images-found-message');
			var $preview = $('#scraped-images-preview');

			var html = '<div class="notice notice-error"><p>';
			html += escapeHtml(message);
			html += '</p></div>';

			$summary.html(html);
			$preview.html('');
			$results.show();
		}

		/**
		 * Handle select/deselect all images.
		 */
		$(document).on('change', '.image-select-checkbox', function() {
			var $item = $(this).closest('.scraped-image-item');
			if ($(this).is(':checked')) {
				$item.removeClass('image-deselected');
			} else {
				$item.addClass('image-deselected');
			}
			updateSelectedCount();
		});

		/**
		 * Toggle individual image settings.
		 */
		$(document).on('click', '.edit-image-settings', function() {
			var $item = $(this).closest('.scraped-image-item');
			var $settings = $item.find('.individual-settings');
			
			// Close other open settings
			$('.individual-settings').not($settings).slideUp();
			$('.edit-image-settings').not(this).text('Edit Settings');
			
			// Toggle this one
			$settings.slideToggle();
			if ($settings.is(':visible')) {
				$(this).text('Close Settings');
			} else {
				$(this).text('Edit Settings');
			}
		});

		/**
		 * Close individual settings.
		 */
		$(document).on('click', '.close-settings', function() {
			var $item = $(this).closest('.scraped-image-item');
			$item.find('.individual-settings').slideUp();
			$item.find('.edit-image-settings').text('Edit Settings');
		});

		/**
		 * Update selected image count.
		 */
		function updateSelectedCount() {
			var total = $('.image-select-checkbox').length;
			var selected = $('.image-select-checkbox:checked').length;
			var $button = $('#add-to-media-library');
			
			if (selected === 0) {
				$button.prop('disabled', true);
				$button.text('No Images Selected');
			} else {
				$button.prop('disabled', false);
				if (selected === total) {
					$button.text('Add to Media Library (' + selected + ')');
				} else {
					$button.text('Add to Media Library (' + selected + ' of ' + total + ')');
				}
			}
		}

		/**
		 * Handle import to media library.
		 */
		$('#import-options-form').on('submit', function(e) {
			e.preventDefault();

			if (scrapedImages.length === 0) {
				alert('No images to import.');
				return;
			}

			// Get selected images with their individual settings.
			var selectedImages = [];
			$('.image-select-checkbox:checked').each(function() {
				var index = $(this).data('index');
				var $item = $(this).closest('.scraped-image-item');
				
				if (scrapedImages[index]) {
					var image = $.extend({}, scrapedImages[index]);
					
					// Get individual settings for this image (if any)
					var individualFilename = $item.find('.img-filename').val().trim();
					var individualAlt = $item.find('.img-alt').val().trim();
					var individualTitle = $item.find('.img-title').val().trim();
					var individualFormat = $item.find('.img-format').val();
					var individualMaxWidth = $item.find('.img-max-width').val();
					var individualMaxSize = $item.find('.img-max-size').val();
					
					// Store individual settings in the image object
					image.individual_settings = {
						filename: individualFilename,
						alt: individualAlt,
						title: individualTitle,
						format: individualFormat,
						max_width: individualMaxWidth,
						max_size: individualMaxSize
					};
					
					selectedImages.push(image);
				}
			});

			if (selectedImages.length === 0) {
				alert('Please select at least one image to import.');
				return;
			}

			var $form = $(this);
			var $submitBtn = $('#add-to-media-library');
			var $progress = $('#import-progress');
			var $results = $('#scraping-results');
			var $finalResults = $('#import-results');

			// Gather global/default options.
			var options = {
				convert_format: $('#convert_format_toggle').is(':checked') ? $('#convert_format').val() : '',
				max_width: $('#max_width').val() || '',
				max_filesize: $('#max_filesize').val() || '',
				filename_prefix: $('#filename_prefix').val() || '',
				image_alt: $('#image_alt').val() || '',
				image_title: $('#image_title').val() || ''
			};

			// Disable submit button and show progress.
			$submitBtn.prop('disabled', true);
			$results.hide();
			$progress.show();
			$('.progress-message').text('Importing images to media library...');

			// Prepare data with selected images.
			var data = {
				action: 'image_scraper_import',
				nonce: imageScraperAdmin.nonce,
				images: selectedImages,
				options: options
			};

			// Make AJAX request.
			$.ajax({
				url: imageScraperAdmin.ajax_url,
				type: 'POST',
				data: data,
				success: function(response) {
					$progress.hide();
					$submitBtn.prop('disabled', false);

					if (response.success) {
						displayImportResults(response.data);
					} else {
						displayImportError(response.data.message || 'An error occurred during import.');
					}
				},
				error: function(xhr, status, error) {
					$progress.hide();
					$submitBtn.prop('disabled', false);
					displayImportError('Network error: ' + error);
				}
			});
		});

		/**
		 * Display import results.
		 *
		 * @param {Object} data The response data.
		 */
		function displayImportResults(data) {
			var $results = $('#import-results');
			var $content = $results.find('.results-content');

			var html = '<div class="notice notice-success"><p>';
			html += 'Successfully imported ' + data.imported_count + ' of ' + data.total_count + ' images!';
			html += '</p></div>';

			if (data.errors && data.errors.length > 0) {
				html += '<div class="notice notice-warning"><p><strong>Some images could not be imported:</strong></p><ul>';
				data.errors.forEach(function(error) {
					html += '<li>' + escapeHtml(error) + '</li>';
				});
				html += '</ul></div>';
			}

			html += '<p><a href="' + imageScraperAdmin.media_library_url + '" class="button button-primary">View Media Library</a></p>';

			$content.html(html);
			$results.show();

			// Scroll to results.
			$('html, body').animate({
				scrollTop: $results.offset().top - 50
			}, 500);
		}

		/**
		 * Display import error.
		 *
		 * @param {string} message The error message.
		 */
		function displayImportError(message) {
			var $results = $('#import-results');
			var $content = $results.find('.results-content');

			var html = '<div class="notice notice-error"><p>';
			html += escapeHtml(message);
			html += '</p></div>';

			$content.html(html);
			$results.show();
		}

		/**
		 * Handle "Start Over" button.
		 */
		$('#start-over').on('click', function() {
			// Reset form and hide results.
			$('#image-scraper-form')[0].reset();
			$('#target_class_wrapper').hide();
			$('#scraping-results').hide();
			$('#import-results').hide();
			scrapedImages = [];

			// Scroll to top.
			$('html, body').animate({
				scrollTop: $('.image-scraper-form-wrapper').offset().top - 50
			}, 500);
		});

		/**
		 * Escape HTML to prevent XSS.
		 *
		 * @param {string} text The text to escape.
		 * @return {string} Escaped text.
		 */
		function escapeHtml(text) {
			if (!text) return '';
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		}

	});

})( jQuery );
