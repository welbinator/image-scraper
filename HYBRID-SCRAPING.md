# Hybrid Scraping Implementation

## Overview
The Image Scraper plugin now supports two scraping methods that can be switched via settings:

1. **Simple Mode (Default)** - Direct HTML scraping without external API
2. **Firecrawl API Mode** - Advanced scraping using Firecrawl API

## Scraping Methods

### Simple Mode (Recommended)
- **How it works**: Uses WordPress's `wp_remote_get()` to fetch HTML directly, then parses it with DOMDocument
- **Advantages**:
  - ✅ Free - No API costs
  - ✅ Fast - Direct HTTP requests
  - ✅ No API key required
  - ✅ Works for most standard websites
- **Limitations**:
  - ❌ Cannot handle JavaScript-rendered content
  - ❌ May be blocked by anti-bot protections
  - ❌ Won't work with Single Page Applications (SPAs)
- **Best for**: Static HTML sites, blogs, basic e-commerce sites

### Firecrawl API Mode
- **How it works**: Uses Firecrawl's cloud service which renders JavaScript and bypasses anti-bot measures
- **Advantages**:
  - ✅ Handles JavaScript-heavy sites
  - ✅ Bypasses anti-bot protections
  - ✅ Works with SPAs (React, Vue, Angular)
  - ✅ More reliable for protected content
- **Limitations**:
  - ❌ Requires API key from firecrawl.dev
  - ❌ Costs money (API credits)
  - ❌ Slightly slower (cloud processing)
- **Best for**: Modern web apps, JavaScript-heavy sites, protected content

## Implementation Details

### Files Modified/Created

1. **`includes/class-html-scraper.php`** (NEW)
   - Direct HTML scraping implementation
   - Same interface as Firecrawl_Api for easy swapping
   - Supports lazy-loaded images (data-src, srcset)
   - Filters placeholder images
   - Parses srcset to get largest image

2. **`includes/class-activator.php`**
   - Added `scraping_method` default option ('simple')

3. **`admin/class-settings.php`**
   - Added "Scraping Method" section with radio buttons
   - Added sanitization for scraping_method (validates 'simple' or 'firecrawl')
   - Dynamic API section description based on method

4. **`admin/class-ajax-handler.php`**
   - Modified `handle_scrape()` to check method and instantiate correct scraper
   - Modified `handle_validate_api()` to only work with Firecrawl method

5. **`includes/class-core.php`**
   - Added Html_Scraper class loading

6. **`admin/partials/scraper-display.php`**
   - Added method badge showing current scraping mode
   - Dynamic warnings based on method
   - Conditional field disabling (only for Firecrawl without API key)

### Scraper Interface
Both scrapers implement the same method signature:
```php
public function scrape_url( $url, $target_class = '' );
```

This allows seamless switching without changing AJAX handler logic.

## Usage

### For Users

1. Navigate to **Image Scraper > Settings**
2. Choose your preferred scraping method:
   - Select "Simple Mode" for free, fast scraping (default)
   - Select "Firecrawl API" for advanced features
3. If using Firecrawl:
   - Enter your API key from firecrawl.dev
   - Click "Test API Key" to verify
4. Save changes

### For Developers

**Adding a new scraping method:**

1. Create new scraper class in `includes/` directory
2. Implement `scrape_url($url, $target_class)` method
3. Return array of images in same format:
   ```php
   [
       ['src' => 'https://...', 'alt' => '...'],
       // ...
   ]
   ```
4. Add option to settings radio buttons
5. Update AJAX handler routing logic

## Testing

After implementation, test both methods:

```bash
# Simple Mode Test
1. Set method to "Simple Mode" in settings
2. Try scraping a basic HTML website
3. Verify images are found and imported correctly

# Firecrawl Mode Test  
1. Set method to "Firecrawl API" in settings
2. Add API key and test it
3. Try scraping the same website
4. Compare results with Simple Mode
```

## Migration from Previous Version

Existing installations will automatically default to Simple Mode. To use Firecrawl:
1. Go to Settings
2. Switch method to "Firecrawl API"
3. Your existing API key will still be saved

## Cost Comparison

### Simple Mode
- **Cost**: $0 (free)
- **Usage limits**: Only server resources (unlimited requests)

### Firecrawl API
- **Cost**: Based on Firecrawl's pricing (pay per request)
- **Usage limits**: Based on subscription tier
- **Estimate**: Check firecrawl.dev/pricing for current rates

## When to Use Each Method

**Use Simple Mode when:**
- Scraping standard HTML websites
- Budget is a concern
- Website doesn't heavily rely on JavaScript
- You don't need to bypass anti-bot measures

**Use Firecrawl when:**
- Website uses heavy JavaScript (React, Vue, Angular)
- Content loads dynamically after page render
- Site has anti-bot protections
- Willing to pay for more reliable scraping

## Future Enhancements

Possible additions to the hybrid system:
- Auto-fallback: Try Simple Mode first, fallback to Firecrawl if it fails
- Method recommendations: Analyze URL and suggest best method
- Performance metrics: Track success rate of each method
- Additional scrapers: Puppeteer, Playwright, Selenium
