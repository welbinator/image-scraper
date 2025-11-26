<?php
/**
 * The settings-specific functionality of the plugin.
 *
 * @package Image_Scraper
 */

namespace Image_Scraper\Admin;

/**
 * The settings-specific functionality of the plugin.
 *
 * Handles all settings registration and sanitization.
 */
class Settings {

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
	 * Register all settings.
	 */
	public function register_settings() {
		// Register setting.
		register_setting(
			'image_scraper_settings_group',
			'image_scraper_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Add scraping method section.
		add_settings_section(
			'image_scraper_method_section',
			__( 'Scraping Method', 'image-scraper' ),
			array( $this, 'method_section_callback' ),
			'image-scraper-settings'
		);

		// Add scraping method field.
		add_settings_field(
			'scraping_method',
			__( 'Scraping Method', 'image-scraper' ),
			array( $this, 'scraping_method_field_callback' ),
			'image-scraper-settings',
			'image_scraper_method_section'
		);

		// Add settings section for Firecrawl.
		add_settings_section(
			'image_scraper_api_section',
			__( 'Firecrawl API Configuration', 'image-scraper' ),
			array( $this, 'api_section_callback' ),
			'image-scraper-settings'
		);

		// Add API key field.
		add_settings_field(
			'firecrawl_api_key',
			__( 'Firecrawl API Key', 'image-scraper' ),
			array( $this, 'api_key_field_callback' ),
			'image-scraper-settings',
			'image_scraper_api_section'
		);

		// Add settings section for scraping options.
		add_settings_section(
			'image_scraper_options_section',
			__( 'Scraping Options', 'image-scraper' ),
			array( $this, 'options_section_callback' ),
			'image-scraper-settings'
		);

		// Add max images field.
		add_settings_field(
			'max_images',
			__( 'Maximum Images Per Scrape', 'image-scraper' ),
			array( $this, 'max_images_field_callback' ),
			'image-scraper-settings',
			'image_scraper_options_section'
		);

		// Add timeout field.
		add_settings_field(
			'timeout',
			__( 'Request Timeout (seconds)', 'image-scraper' ),
			array( $this, 'timeout_field_callback' ),
			'image-scraper-settings',
			'image_scraper_options_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input The input array to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize scraping method.
		if ( isset( $input['scraping_method'] ) ) {
			$method = sanitize_text_field( $input['scraping_method'] );
			$sanitized['scraping_method'] = in_array( $method, array( 'simple', 'firecrawl' ), true ) ? $method : 'simple';
		}

		// Sanitize API key.
		if ( isset( $input['firecrawl_api_key'] ) ) {
			$sanitized['firecrawl_api_key'] = sanitize_text_field( $input['firecrawl_api_key'] );
		}

		// Sanitize max images.
		if ( isset( $input['max_images'] ) ) {
			$max_images = absint( $input['max_images'] );
			// Enforce reasonable limits.
			$sanitized['max_images'] = max( 1, min( 500, $max_images ) );
		}

		// Sanitize timeout.
		if ( isset( $input['timeout'] ) ) {
			$timeout = absint( $input['timeout'] );
			// Enforce reasonable limits.
			$sanitized['timeout'] = max( 5, min( 300, $timeout ) );
		}

		return $sanitized;
	}

	/**
	 * Method section callback.
	 */
	public function method_section_callback() {
		echo '<p>' . esc_html__( 'Choose how to scrape images from websites.', 'image-scraper' ) . '</p>';
	}

	/**
	 * Scraping method field callback.
	 */
	public function scraping_method_field_callback() {
		$options = get_option( 'image_scraper_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';
		?>
		<fieldset>
			<label>
				<input 
					type="radio" 
					name="image_scraper_settings[scraping_method]" 
					value="simple"
					<?php checked( $method, 'simple' ); ?>
				/>
				<strong><?php esc_html_e( 'Simple Mode (Recommended)', 'image-scraper' ); ?></strong>
				<p class="description" style="margin-left: 25px;">
					<?php esc_html_e( 'Direct HTML scraping - fast, free, and works for most websites. No API key required.', 'image-scraper' ); ?>
				</p>
			</label>
			<br><br>
			<label>
				<input 
					type="radio" 
					name="image_scraper_settings[scraping_method]" 
					value="firecrawl"
					<?php checked( $method, 'firecrawl' ); ?>
				/>
				<strong><?php esc_html_e( 'Firecrawl API', 'image-scraper' ); ?></strong>
				<p class="description" style="margin-left: 25px;">
					<?php esc_html_e( 'Advanced scraping for JavaScript-heavy sites, SPAs, and protected content. Requires API key.', 'image-scraper' ); ?>
				</p>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * API section callback.
	 */
	public function api_section_callback() {
		$options = get_option( 'image_scraper_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';
		
		if ( $method === 'simple' ) {
			echo '<p class="description">' . esc_html__( 'Firecrawl API is not required when using Simple Mode.', 'image-scraper' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Configure your Firecrawl API credentials. Get your API key from', 'image-scraper' ) . ' <a href="https://firecrawl.dev" target="_blank">firecrawl.dev</a>.</p>';
		}
	}

	/**
	 * Options section callback.
	 */
	public function options_section_callback() {
		echo '<p>' . esc_html__( 'Configure scraping behavior and limits.', 'image-scraper' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function api_key_field_callback() {
		$options = get_option( 'image_scraper_settings' );
		$api_key = isset( $options['firecrawl_api_key'] ) ? $options['firecrawl_api_key'] : '';
		?>
		<input 
			type="password" 
			id="firecrawl_api_key" 
			name="image_scraper_settings[firecrawl_api_key]" 
			value="<?php echo esc_attr( $api_key ); ?>" 
			class="regular-text"
			autocomplete="off"
		/>
				<p class="description">
					<?php esc_html_e( 'Your Firecrawl API key. This will be stored securely.', 'image-scraper' ); ?>
				</p>
				<p>
					<button type="button" class="button button-secondary" id="test-api-key" <?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'Test API Connection', 'image-scraper' ); ?>
					</button>
					<span id="api-test-result"></span>
				</p>
		<?php
	}	/**
	 * Render max images field.
	 */
	public function max_images_field_callback() {
		$options    = get_option( 'image_scraper_settings' );
		$max_images = isset( $options['max_images'] ) ? $options['max_images'] : 50;
		?>
		<input 
			type="number" 
			id="max_images" 
			name="image_scraper_settings[max_images]" 
			value="<?php echo esc_attr( $max_images ); ?>" 
			min="1" 
			max="500"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Maximum number of images to scrape per request (1-500).', 'image-scraper' ); ?>
		</p>
		<?php
	}

	/**
	 * Render timeout field.
	 */
	public function timeout_field_callback() {
		$options = get_option( 'image_scraper_settings' );
		$timeout = isset( $options['timeout'] ) ? $options['timeout'] : 30;
		?>
		<input 
			type="number" 
			id="timeout" 
			name="image_scraper_settings[timeout]" 
			value="<?php echo esc_attr( $timeout ); ?>" 
			min="5" 
			max="300"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Timeout for API requests in seconds (5-300).', 'image-scraper' ); ?>
		</p>
		<?php
	}
}
