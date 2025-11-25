# Copilot Instructions: Image Scraper WordPress Plugin

## Project Overview
This is a WordPress plugin being developed within a Lando-based local development environment. The plugin directory (`wp-content/plugins/image-scraper/`) is currently empty and ready for development.

## Development Environment

### Stack
- **WordPress**: v6.8.3
- **PHP**: 7.4 (via Apache)
- **MySQL**: 5.7
- **Development Tool**: Lando (containerized WordPress environment)

### Lando Configuration
The project uses Lando with WordPress recipe (`.lando.yml` at project root):
- **Project name**: `image-scraper`
- **Webroot**: Project root (not typical `web/` or `public/`)
- **Local URL**: https://image-scraper.lndo.site/
- **Database service**: `database` (accessible internally at `database:3306`)
- **Database credentials**: All set to `wordpress` (user, pass, db name)

### Essential Lando Commands
```bash
# Start the environment
lando start

# Stop the environment
lando stop

# Access WP-CLI
lando wp [command]

# SSH into app container
lando ssh

# View service info
lando info

# Rebuild containers (after config changes)
lando rebuild
```

## WordPress Configuration

### Database Settings (wp-config.php)
- Host: `database` (Lando service name, not localhost)
- Database: `wordpress`
- User: `wordpress`
- Password: `wordpress`
- Prefix: `wp_`

### Debug Mode
Currently set to `false`. Enable for development:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

## Plugin Development Conventions

### File Structure
WordPress plugins should follow this structure:
```
image-scraper/
├── image-scraper.php          # Main plugin file (required)
├── readme.txt                 # WordPress.org readme
├── includes/                  # Core plugin classes
├── admin/                     # Admin-specific functionality
├── public/                    # Public-facing functionality
├── assets/                    # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
└── languages/                 # Translation files
```

### Main Plugin File Header
The primary PHP file must include a standard WordPress plugin header:
```php
<?php
/**
 * Plugin Name:       Image Scraper
 * Plugin URI:        https://example.com/image-scraper
 * Description:       Description of what the plugin does
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       image-scraper
 * Domain Path:       /languages
 */
```

### WordPress Coding Standards (CRITICAL - ALWAYS FOLLOW)
- **Namespacing**: Use `Image_Scraper` namespace for all classes
- **Class naming**: PascalCase with `Image_Scraper_` prefix (e.g., `Image_Scraper_Admin`)
- **Function naming**: snake_case with `image_scraper_` prefix (e.g., `image_scraper_init()`)
- **Security - ALWAYS**:
  - Use `check_admin_referer()` with nonces for all form submissions
  - Escape output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
  - Sanitize input: `sanitize_text_field()`, `sanitize_email()`, `sanitize_url()`
  - Validate capabilities: `current_user_can()` before any admin action
- Use WordPress functions over PHP (e.g., `wp_remote_get()` not `file_get_contents()`)
- Never trust user input - always sanitize and validate
- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)

### Common WordPress Functions
- `add_action()` / `add_filter()` - Hook into WordPress lifecycle
- `register_activation_hook()` / `register_deactivation_hook()` - Plugin lifecycle
- `wp_enqueue_script()` / `wp_enqueue_style()` - Load assets properly
- `wp_remote_get()` / `wp_remote_post()` - HTTP requests
- `esc_html()`, `esc_attr()`, `esc_url()` - Output escaping
- `sanitize_text_field()`, `sanitize_email()` - Input sanitization

## Testing & Debugging

### Accessing Logs
Debug logs are written to `wp-content/debug.log` when `WP_DEBUG_LOG` is enabled.

### Using WP-CLI
```bash
# Activate/deactivate plugin
lando wp plugin activate image-scraper
lando wp plugin deactivate image-scraper

# Check plugin status
lando wp plugin list

# Database operations
lando wp db query "SELECT * FROM wp_options LIMIT 5"

# Export/import database
lando wp db export backup.sql
lando wp db import backup.sql
```

### Direct Database Access
```bash
# Connect to MySQL
lando mysql -u wordpress -pwordpress wordpress
```

## WordPress-Specific Patterns

### Plugin Activation/Deactivation
Always use hooks for setup/teardown:
```php
register_activation_hook(__FILE__, 'image_scraper_activate');
register_deactivation_hook(__FILE__, 'image_scraper_deactivate');
```

### AJAX Handlers
WordPress AJAX must be registered for both logged-in and logged-out users:
```php
add_action('wp_ajax_my_action', 'my_action_callback');
add_action('wp_ajax_nopriv_my_action', 'my_action_callback');
```

### Enqueuing Assets
Always enqueue (never hardcode script/style tags):
```php
function image_scraper_enqueue_assets() {
    wp_enqueue_style('image-scraper-css', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script('image-scraper-js', plugins_url('assets/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'image_scraper_enqueue_assets');
```

## Project Context
- **Existing Plugins**: Akismet (bundled), Hello Dolly (default)
- **Active Themes**: Twenty Twenty-Three, Twenty Twenty-Four, Twenty Twenty-Five
- **Plugin Location**: `wp-content/plugins/image-scraper/` (workspace root)
- **WordPress Root**: Two directories up from plugin directory

## Key Paths
- Plugin directory: `/home/highprrrr/lando/image-scraper/wp-content/plugins/image-scraper/`
- WordPress root: `/home/highprrrr/lando/image-scraper/`
- Debug log: `wp-content/debug.log`
- PHP config: `/home/highprrrr/.lando/config/wordpress/php.ini`
