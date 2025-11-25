<?php
/**
 * Provide a admin area view for the settings page.
 *
 * @package Image_Scraper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'image_scraper_settings_group' );
		do_settings_sections( 'image-scraper-settings' );
		submit_button();
		?>
	</form>

	<div class="image-scraper-info">
		<h2><?php esc_html_e( 'About Firecrawl API', 'image-scraper' ); ?></h2>
		<p><?php esc_html_e( 'Firecrawl is a web scraping API that allows you to extract content from websites. To use this plugin, you need a Firecrawl API key.', 'image-scraper' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Sign up:', 'image-scraper' ); ?></strong> <a href="https://firecrawl.dev" target="_blank">https://firecrawl.dev</a></li>
			<li><strong><?php esc_html_e( 'Documentation:', 'image-scraper' ); ?></strong> <a href="https://docs.firecrawl.dev" target="_blank">https://docs.firecrawl.dev</a></li>
		</ul>
	</div>
</div>
