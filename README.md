# Image Scraper WordPress Plugin

A WordPress plugin that scrapes images from websites using the Firecrawl API and adds them to your WordPress media library.

## Features

- 🔥 Integration with Firecrawl API for robust web scraping
- 🖼️ Automatic import of scraped images to WordPress media library
- ⚙️ Configurable settings (API key, max images, timeout)
- 🎨 Clean admin interface with real-time progress indicators
- 🔒 Follows WordPress security best practices (nonces, sanitization, escaping)
- 🏗️ Object-oriented architecture with proper namespacing

## Installation

1. Clone or download this plugin to `wp-content/plugins/image-scraper/`
2. Activate the plugin through the WordPress admin panel
3. Navigate to "Image Scraper" → "Settings" in the admin menu
4. Enter your Firecrawl API key

## Plugin Architecture

### Directory Structure

```
image-scraper/
├── image-scraper.php              # Main plugin file (bootstrap)
├── includes/                      # Core plugin classes
│   ├── class-core.php            # Main orchestrator
│   ├── class-loader.php          # Hooks/filters manager
│   ├── class-activator.php       # Activation hooks
│   ├── class-deactivator.php     # Deactivation hooks
│   └── class-i18n.php            # Internationalization
├── admin/                         # Admin-specific functionality
│   ├── class-admin.php           # Admin menu and pages
│   ├── class-settings.php        # Settings API integration
│   ├── css/
│   │   └── admin.css             # Admin styles
│   ├── js/
│   │   └── admin.js              # Admin JavaScript (AJAX)
│   └── partials/                 # View templates
│       ├── settings-display.php   # Settings page UI
│       └── scraper-display.php    # Main scraper page UI
└── .github/
    └── copilot-instructions.md    # AI coding assistant guide
```

### Class Structure & Namespacing

All classes use the `Image_Scraper` namespace to avoid conflicts:

- **`Image_Scraper\Core`** - Main plugin orchestrator, coordinates all components
- **`Image_Scraper\Loader`** - Manages WordPress hooks/filters registration
- **`Image_Scraper\Admin\Admin`** - Handles admin menu, pages, and asset loading
- **`Image_Scraper\Admin\Settings`** - Settings API registration and sanitization
- **`Image_Scraper\Activator`** - Plugin activation logic
- **`Image_Scraper\Deactivator`** - Plugin deactivation logic
- **`Image_Scraper\I18n`** - Translation/localization support

### Key Design Patterns

1. **Autoloading**: PSR-4-style autoloader converts namespaced class names to file paths
2. **Separation of Concerns**: Admin, public, and core logic in separate directories
3. **Hook Abstraction**: `Loader` class centralizes all WordPress hooks
4. **Settings API**: Full WordPress Settings API integration with validation
5. **Security First**: All inputs sanitized, all outputs escaped, nonces everywhere

## Current Settings

The plugin stores settings in a single option: `image_scraper_settings`

Available settings:
- `firecrawl_api_key` (string) - Your Firecrawl API key
- `max_images` (int) - Maximum images per scrape (1-500, default: 50)
- `timeout` (int) - API request timeout in seconds (5-300, default: 30)

## Next Steps for Development

### Immediate Tasks

1. **Create Firecrawl API Service Class**
   - Location: `includes/class-firecrawl-api.php`
   - Methods: `scrape_url()`, `validate_api_key()`, `get_images()`
   - Handle API authentication and error responses

2. **Create AJAX Handler**
   - Add AJAX action: `wp_ajax_image_scraper_scrape`
   - Validate nonce and capabilities
   - Call Firecrawl API service
   - Return JSON response

3. **Create Media Library Importer**
   - Location: `includes/class-media-importer.php`
   - Use `media_sideload_image()` or custom implementation
   - Handle duplicate detection
   - Set proper image metadata (alt text, title, caption)

4. **Add Error Handling & Logging**
   - Create error/success message system
   - Log API failures for debugging
   - User-friendly error messages

5. **Add Image Filtering Options**
   - Minimum image dimensions
   - Exclude certain file types
   - Duplicate detection before import

### Future Enhancements

- Batch processing for multiple URLs
- Background processing with WordPress cron
- Image optimization before import
- Custom taxonomy for scraped images
- Export/import settings
- WP-CLI commands
- Unit tests with PHPUnit

## Firecrawl API Integration

The plugin uses the Firecrawl API for web scraping. Key endpoints:

- **Authentication**: API key in `Authorization` header
- **Scrape endpoint**: POST to scrape URLs
- **Rate limits**: Varies by plan (handle gracefully)

Documentation: https://docs.firecrawl.dev

## Security & Coding Standards

This plugin follows WordPress coding standards:

✅ **Security**:
- Nonce verification on all form submissions
- Capability checks (`manage_options`)
- Input sanitization (`sanitize_text_field()`, `absint()`)
- Output escaping (`esc_html()`, `esc_attr()`, `esc_url()`)

✅ **Naming Conventions**:
- Classes: `Image_Scraper_Class_Name`
- Functions: `image_scraper_function_name()`
- Hooks: `image_scraper_hook_name`

✅ **Best Practices**:
- WordPress functions over PHP alternatives
- Proper enqueueing of scripts/styles
- Translation-ready strings
- Direct file access prevention

## Development Commands

```bash
# Activate plugin via WP-CLI
lando wp plugin activate image-scraper

# Deactivate plugin
lando wp plugin deactivate image-scraper

# Check plugin status
lando wp plugin list

# View plugin options
lando wp option get image_scraper_settings

# Update API key via CLI
lando wp option patch update image_scraper_settings firecrawl_api_key "your-api-key"
```

## License

GPL v2 or later

## Author

Your Name (customize in `image-scraper.php`)
