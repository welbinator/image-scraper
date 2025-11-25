<?php
/**
 * Provide an admin area view for scraping images.
 *
 * @package Image_Scraper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if API key is set.
$options = get_option( 'image_scraper_settings' );
$api_key = isset( $options['firecrawl_api_key'] ) ? $options['firecrawl_api_key'] : '';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( empty( $api_key ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: link to settings page */
					esc_html__( 'Please configure your Firecrawl API key in %s before scraping images.', 'image-scraper' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=image-scraper-settings' ) ) . '">' . esc_html__( 'Settings', 'image-scraper' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="image-scraper-container">
		<!-- Step 1: Initial Scrape Form -->
		<div class="image-scraper-form-wrapper">
			<h2><?php esc_html_e( 'Step 1: Scrape Images from URL', 'image-scraper' ); ?></h2>
			
			<form id="image-scraper-form" method="post">
				<?php wp_nonce_field( 'image_scraper_scrape_action', 'image_scraper_scrape_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="target_url"><?php esc_html_e( 'Target URL', 'image-scraper' ); ?></label>
						</th>
						<td>
							<input 
								type="url" 
								id="target_url" 
								name="target_url" 
								class="regular-text" 
								placeholder="https://example.com"
								required
								<?php echo empty( $api_key ) ? 'disabled' : ''; ?>
							/>
							<p class="description">
								<?php esc_html_e( 'Enter the URL of the webpage you want to scrape images from.', 'image-scraper' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="target_class_toggle"><?php esc_html_e( 'Target Specific Class', 'image-scraper' ); ?></label>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="target_class_toggle" 
									name="target_class_toggle"
									<?php echo empty( $api_key ) ? 'disabled' : ''; ?>
								/>
								<?php esc_html_e( 'Only scrape images with a specific CSS class', 'image-scraper' ); ?>
							</label>
							<div id="target_class_wrapper" style="display: none; margin-top: 10px;">
								<input 
									type="text" 
									id="target_class" 
									name="target_class" 
									class="regular-text" 
									placeholder="my-image-class or .my-image-class"
									<?php echo empty( $api_key ) ? 'disabled' : ''; ?>
								/>
								<p class="description">
									<?php esc_html_e( 'Enter the CSS class name (with or without the dot).', 'image-scraper' ); ?>
								</p>
							</div>
						</td>
					</tr>
				</table>

				<?php 
				$button_disabled = empty( $api_key );
				submit_button( 
					__( 'Start Scraping', 'image-scraper' ), 
					'primary', 
					'submit', 
					true, 
					$button_disabled ? array( 'disabled' => 'disabled' ) : array()
				); 
				?>
			</form>
		</div>

		<!-- Progress Indicator -->
		<div id="scraping-progress" class="image-scraper-progress" style="display: none;">
			<h3><?php esc_html_e( 'Scraping in Progress...', 'image-scraper' ); ?></h3>
			<div class="progress-bar">
				<div class="progress-bar-fill"></div>
			</div>
			<p class="progress-message"></p>
		</div>

		<!-- Step 2: Preview and Options -->
		<div id="scraping-results" class="image-scraper-results" style="display: none;">
			<h3><?php esc_html_e( 'Step 2: Preview & Configure Import Options', 'image-scraper' ); ?></h3>
			
			<div class="results-summary">
				<p class="images-found-message"></p>
			</div>

			<!-- Image Preview Grid -->
			<div id="scraped-images-preview" class="scraped-images-grid"></div>

			<!-- Import Options Form -->
			<div class="import-options-wrapper">
				<h3><?php esc_html_e( 'Import Options', 'image-scraper' ); ?></h3>
				
				<form id="import-options-form">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="convert_format_toggle"><?php esc_html_e( 'Convert Image Format', 'image-scraper' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="convert_format_toggle" name="convert_format_toggle" />
									<?php esc_html_e( 'Convert all images to a specific format', 'image-scraper' ); ?>
								</label>
								<div id="convert_format_wrapper" style="display: none; margin-top: 10px;">
									<select id="convert_format" name="convert_format" class="regular-text">
										<option value="webp"><?php esc_html_e( 'WebP', 'image-scraper' ); ?></option>
										<option value="jpeg"><?php esc_html_e( 'JPEG', 'image-scraper' ); ?></option>
										<option value="png"><?php esc_html_e( 'PNG', 'image-scraper' ); ?></option>
									</select>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="max_width"><?php esc_html_e( 'Maximum Width', 'image-scraper' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="max_width" 
									name="max_width" 
									class="small-text" 
									placeholder="e.g., 1920"
									min="1"
								/>
								<span class="description"><?php esc_html_e( 'pixels (leave empty for no limit)', 'image-scraper' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="max_filesize"><?php esc_html_e( 'Maximum File Size', 'image-scraper' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="max_filesize" 
									name="max_filesize" 
									class="small-text" 
									placeholder="e.g., 500"
									min="1"
								/>
								<span class="description"><?php esc_html_e( 'KB (leave empty for no limit)', 'image-scraper' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="filename_prefix"><?php esc_html_e( 'Filename Prefix', 'image-scraper' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="filename_prefix" 
									name="filename_prefix" 
									class="regular-text" 
									placeholder="e.g., my-site-"
								/>
								<p class="description">
									<?php esc_html_e( 'Add a prefix to all imported image filenames (optional).', 'image-scraper' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="image_alt"><?php esc_html_e( 'Alt Text', 'image-scraper' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="image_alt" 
									name="image_alt" 
									class="regular-text" 
									placeholder="e.g., Product image"
								/>
								<p class="description">
									<?php esc_html_e( 'This alt text will be applied to all imported images (optional).', 'image-scraper' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="image_title"><?php esc_html_e( 'Image Title', 'image-scraper' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="image_title" 
									name="image_title" 
									class="regular-text" 
									placeholder="e.g., Product photo"
								/>
								<p class="description">
									<?php esc_html_e( 'This title will be applied to all imported images (optional).', 'image-scraper' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="add-to-media-library">
							<?php esc_html_e( 'Add to Media Library', 'image-scraper' ); ?>
						</button>
						<button type="button" class="button button-secondary" id="start-over">
							<?php esc_html_e( 'Start Over', 'image-scraper' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

		<!-- Import Progress -->
		<div id="import-progress" class="image-scraper-progress" style="display: none;">
			<h3><?php esc_html_e( 'Importing Images...', 'image-scraper' ); ?></h3>
			<div class="progress-bar">
				<div class="progress-bar-fill"></div>
			</div>
			<p class="progress-message"></p>
		</div>

		<!-- Final Results -->
		<div id="import-results" class="image-scraper-results" style="display: none;">
			<h3><?php esc_html_e( 'Import Complete!', 'image-scraper' ); ?></h3>
			<div class="results-content"></div>
		</div>
	</div>
</div>
