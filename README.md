# Image Scraper WordPress Plugin

A WordPress plugin that scrapes images from websites using the Firecrawl API and adds them to your WordPress media library.

## Features

- 🔥 **Dual Scraping Methods**: Choose between Simple Mode (free, direct HTML) or Firecrawl API (advanced, JavaScript-heavy sites)
- 🖼️ **Automatic Image Import**: Import scraped images directly to WordPress media library
- ✏️ **Per-Image Customization**: Edit individual settings for each image (filename, alt text, title, format, dimensions)
- 🎛️ **Bulk Options**: Apply global settings to all images or customize each one individually
- 📐 **Image Processing**: Convert formats (WebP, JPEG, PNG), resize to max width, compress to max file size
- ☑️ **Selective Import**: Choose which images to import with checkboxes
- 🎯 **CSS Class Targeting**: Optionally scrape only images with specific CSS classes
- 🔒 **Security First**: Follows WordPress security best practices (nonces, sanitization, escaping)
- 🏗️ **Clean Architecture**: Object-oriented design with proper namespacing
- 📱 **Responsive UI**: Works on desktop and mobile devices

## Installation

1. Clone or download this plugin to `wp-content/plugins/image-scraper/`
2. Activate the plugin through the WordPress admin panel
3. Navigate to "Image Scraper" → "Settings" in the admin menu
4. **Choose your scraping method**:
   - **Simple Mode** (default): Free, works for most websites, no API key needed
   - **Firecrawl API**: For JavaScript-heavy sites, requires API key from [firecrawl.dev](https://firecrawl.dev)

## Usage

### Basic Workflow

1. **Navigate** to "Image Scraper" in the WordPress admin menu
2. **Enter URL** of the webpage containing images
3. **Optional**: Target specific CSS class to scrape only certain images
4. **Click "Start Scraping"** to fetch images
5. **Review Preview**: See all found images in a grid
6. **Customize Images** (optional):
   - Click "Edit Settings" on any image to customize individually
   - Or use global options at the bottom to apply settings to all images
7. **Select Images**: Use checkboxes to choose which images to import
8. **Configure Options**:
   - Convert format (WebP, JPEG, PNG)
   - Set maximum width (images larger will be resized)
   - Set maximum file size (compress if needed)
   - Add filename prefix
   - Set alt text and title
9. **Click "Add to Media Library"** to import selected images

### Per-Image Settings

Each image can have individual settings that override global defaults:

- **Filename**: Custom filename for this specific image
- **Alt Text**: SEO-friendly alt text
- **Title**: Image title in media library
- **Format**: Convert to WebP, JPEG, or PNG
- **Max Width**: Resize if wider than specified (maintains aspect ratio)
- **Max Size**: Compress to stay under file size limit (in KB)

### Scraping Methods

#### Simple Mode (Recommended)
- ✅ Free - no API costs
- ✅ Fast - direct HTTP requests
- ✅ No API key required
- ✅ Works for most standard websites
- ❌ Cannot handle JavaScript-rendered content
- ❌ May be blocked by anti-bot protections

#### Firecrawl API Mode
- ✅ Handles JavaScript-heavy sites (React, Vue, Angular)
- ✅ Bypasses anti-bot protections
- ✅ More reliable for protected content

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
│   ├── class-i18n.php            # Internationalization
│   ├── class-firecrawl-api.php   # Firecrawl API integration
│   ├── class-html-scraper.php    # Simple Mode HTML scraper
│   └── class-media-importer.php  # Image processing & import
├── admin/                         # Admin-specific functionality
│   ├── class-admin.php           # Admin menu and pages
│   ├── class-settings.php        # Settings API integration
│   ├── class-ajax-handler.php    # AJAX request handlers
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
- **`Image_Scraper\Firecrawl_Api`** - Firecrawl API integration for advanced scraping
- **`Image_Scraper\Html_Scraper`** - Simple Mode HTML scraper (no API needed)
- **`Image_Scraper\Media_Importer`** - Image processing, format conversion, and media library import
- **`Image_Scraper\Admin\Admin`** - Handles admin menu, pages, and asset loading
- **`Image_Scraper\Admin\Settings`** - Settings API registration and sanitization
- **`Image_Scraper\Admin\Ajax_Handler`** - AJAX request handlers for scraping and importing
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
- `scraping_method` (string) - Scraping method: 'simple' (default) or 'firecrawl'
- `firecrawl_api_key` (string) - Your Firecrawl API key (only needed for Firecrawl mode)
- `max_images` (int) - Maximum images per scrape (1-500, default: 50)
- `timeout` (int) - API request timeout in seconds (5-300, default: 30)
## Next Steps for Development

### Potential Future Enhancements

- Batch processing for multiple URLs
- Background processing with WordPress cron for large scrapes
- Schedule recurring scrapes
- Custom taxonomy for scraped images
- Import/export settings
- WP-CLI commands for automation
- Srcset support for responsive images
- Image gallery creation from scraped images
- Auto-detection of lazy-loaded images (already partially supported)
- API for third-party integrationsrsion, resizing, compression
   - Returns success/error count
   
3. **`image_scraper_validate_api`** - Test Firecrawl API key
   - Only available in Firecrawl mode
   - Validates API connectivity

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

## Firecrawl API Integration (Optional)

The plugin can optionally use the Firecrawl API for advanced web scraping:

- **When to use**: JavaScript-heavy sites, SPAs, protected content
- **Authentication**: API key in request headers
- **Documentation**: https://docs.firecrawl.dev
- **Getting started**: Sign up at https://firecrawl.dev

The plugin works perfectly fine without Firecrawl using Simple Mode for standard HTML websites.

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

James Welbes - https://jameswelbes.com
Your Name (customize in `image-scraper.php`)
