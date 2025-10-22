# AI Virtual Try-On - Copilot Instructions

## Project Overview

WordPress plugin providing AI-powered virtual clothing try-on using Google's Gemini 2.5 Flash Image API. Dual-mode architecture: **WooCommerce product page modals** (primary, v2.0+) and **shortcode-based pages** (legacy compatibility).

**Key Technical Decisions:**
- **Three-tier proxy model**: Browser → WordPress backend → Gemini API (API keys stay server-side only in `wp-config.php`)
- **Direct filesystem access** for WooCommerce mode (not `wp_get_attachment_url()`) - avoids HTTP requests, saves 1-2s per generation
- **Dual-mode JavaScript**: Single `avto-frontend.js` detects modal vs shortcode context and adapts behavior
- **Conditional asset loading**: CSS/JS only loads on product pages with WooCommerce integration enabled OR pages with `[ai_virtual_tryon]` shortcode

## Architecture & Data Flow

### Core Components

```
ai-virtual-try-on.php           # Plugin bootstrap, hooks, constants, activation/deactivation
includes/avto-ajax-handler.php  # AJAX endpoint, API proxy, image validation, rate limiting
includes/avto-woocommerce.php   # Product page button injection, modal HTML, category filtering
includes/avto-admin.php         # Settings page (tabs: AI, WooCommerce, Advanced)
includes/avto-shortcode.php     # Legacy shortcode rendering [ai_virtual_tryon]
assets/js/avto-frontend.js      # Dual-mode logic: AVTOModal (WooCommerce) + AVTOCore (shortcode)
```

### Critical Flow: Image Generation

1. **Frontend** → User uploads photo + selects clothing image → AJAX to `avto_handle_generate_image_request`
2. **Backend validates**:
   - Nonce verification (`check_ajax_referer`)
   - Rate limiting (two-tier: global site-wide check, then per-user/IP check)
   - MIME type check (client-provided AND actual file content via `finfo_file`)
   - File size limit (configurable, default 5MB)
3. **Gemini API call**:
   - Base64-encode user photo + clothing image
   - POST to `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp-image:generateContent`
   - Custom prompt from `avto_ai_prompt` option (filterable via `avto_gemini_prompt`)
4. **Result processing**:
   - Base64 response → saved to WordPress Media Library
   - Attachment ID returned to frontend
   - Hooks: `avto_before_api_call`, `avto_after_generation_success`

### WooCommerce vs Shortcode Mode Detection

**WooCommerce mode** (check `includes/avto-woocommerce.php:50-85`):
- Hook: `add_action( $hook_location, 'avto_wc_add_tryon_button', $hook_priority )`
- Uses `get_attached_file( $clothing_image_id )` for direct filesystem paths
- Modal HTML injected via `wp_footer`
- Product images fetched via AJAX endpoint `avto_get_product_images`

**Shortcode mode** (check `includes/avto-shortcode.php`):
- Uses `$clothing_items` array from `avto_clothing_items` option
- Images from `assets/images/` directory
- Inline rendering, no modal

## WordPress Conventions

### Security Patterns (REQUIRED)

```php
// ALWAYS verify nonce before processing AJAX
check_ajax_referer( 'avto-generate-image-nonce', 'nonce' );

// ALWAYS sanitize inputs
$product_id = absint( $_POST['product_id'] );
$text = sanitize_text_field( $_POST['text'] );

// ALWAYS escape outputs
echo esc_html( $text );
echo esc_attr( $attribute );
echo esc_url( $url );
```

### Settings API (follow `includes/avto-admin.php:28-120`)

```php
// Register settings with sanitize callbacks
register_setting( 'avto_settings_group', 'avto_option_name', array(
	'type'              => 'string',
	'sanitize_callback' => 'sanitize_text_field',
	'default'           => 'default_value',
) );

// Retrieve with fallback
$value = get_option( 'avto_option_name', 'default_value' );
```

### Internationalization (i18n)

```php
// Strings wrapped in translation functions
__( 'Text', 'avto' );           // Returns translated string
esc_html__( 'Text', 'avto' );   // Returns escaped translated string
esc_html_e( 'Text', 'avto' );   // Echoes escaped translated string
```

## Development Workflows

### Local Development Setup

```bash
# Using wp-env (Docker-based, config in .wp-env.json)
npm install -g @wordpress/env
wp-env start

# Access: http://localhost:8888 (admin: admin/password)
# Stop: wp-env stop
```

### Testing WooCommerce Integration

1. **Enable integration**: Settings → Virtual Try-On → WooCommerce tab → Toggle ON
2. **Target categories**: Select "Clothing" or create test category
3. **Verify button**: Visit product page in targeted category → Check for "Virtual Try-On" button
4. **Debug**: Check `WP_DEBUG` logs for asset enqueue messages (see `ai-virtual-try-on.php:275`)

### Common Debugging Commands

```bash
# Check if API key is configured
wp-env run cli wp eval 'echo defined("AVTO_GEMINI_API_KEY") ? "YES" : "NO";'

# Clear transients (rate limiting, caching)
wp-env run cli wp transient delete --all

# Verify WooCommerce active
wp-env run cli wp plugin list --status=active | grep woocommerce
```

## Critical Patterns & Gotchas

### 1. MIME Type Validation is Double-Checked

See `includes/avto-ajax-handler.php:67-90`. Browsers may upload AVIF (unsupported by Gemini), so we check BOTH `$_FILES['user_image']['type']` AND `finfo_file()` on server. **Never skip the `finfo_file()` check.**

### 2. WooCommerce Hook Positioning is Dynamic

Settings allow users to choose hook location (`avto_wc_display_hook`) and priority (`avto_wc_hook_priority`). Don't hardcode `woocommerce_single_product_summary` - always read from options (see `includes/avto-woocommerce.php:35-38`).

### 3. Asset Loading is Conditional

CSS/JS only enqueues when:
- Shortcode `[ai_virtual_tryon]` present on page, OR
- Product page + WooCommerce integration enabled + product in target category

Check `ai-virtual-try-on.php:241-282` for full logic.

### 4. Localization Timing Matters

Product data (`avtoProductData`) is localized in `wp_footer` at priority 5 (see `ai-virtual-try-on.php:338-379`) because the `$product` object isn't available earlier on product pages.

### 5. Rate Limiting Uses Two Tiers

See `includes/avto-ajax-handler.php:596-700`. **Two-tier system** (as of v2.4.0):

**Per-User/IP Limits** (always active):
- Key format: `avto_rate_limit_user_{$user_id}` or `avto_rate_limit_ip_{$md5_hash}`
- Default: 10 requests per 60 seconds per user/IP
- Each user/IP tracked independently

**Global Site-Wide Limits** (optional):
- Key: `avto_global_rate_limit`
- Default: 100 requests per 3600 seconds (disabled by default)
- Applies to ALL users combined
- Checked first before per-user limits

Both use WordPress transients. To clear: `wp transient delete avto_rate_limit_user_123` or `wp transient delete avto_global_rate_limit`.

## Extension Points

### Filters (see HOOKS-REFERENCE.md for details)

```php
// Customize AI prompt per product
add_filter( 'avto_gemini_prompt', function( $prompt, $user_img, $clothing_img, $product_id ) {
	if ( has_term( 'formal-wear', 'product_cat', $product_id ) ) {
		return $prompt . ' Emphasize elegant draping and professional fit.';
	}
	return $prompt;
}, 10, 4 );

// Modify API generation config (aspect ratio, etc)
add_filter( 'avto_gemini_generation_config', function( $config, $product_id ) {
	$config['aspectRatio'] = '9:16'; // Portrait mode
	return $config;
}, 10, 2 );

// Restrict feature to specific user roles
add_action( 'avto_before_api_call', function() {
	if ( ! current_user_can( 'subscriber' ) ) {
		wp_send_json_error( array( 'message' => 'Login required' ) );
	}
}, 10, 4 );
```

### Actions

```php
// Track usage analytics
add_action( 'avto_after_generation_success', function( $attachment_id, $image_url, $product_id ) {
	update_post_meta( $attachment_id, '_generated_for_product', $product_id );
	update_post_meta( $attachment_id, '_generation_timestamp', time() );
}, 10, 3 );
```

## Key Files to Reference

- **API integration**: `includes/avto-ajax-handler.php` (lines 200-450 for Gemini API call)
- **WooCommerce logic**: `includes/avto-woocommerce.php` (button injection, category filtering)
- **Settings structure**: `includes/avto-admin.php` (tabs at line 150+)
- **Frontend dual-mode**: `assets/js/avto-frontend.js` (AVTOModal vs AVTOCore)
- **Constants**: `ai-virtual-try-on.php` (lines 26-48)

## Version Management

### Current Version
- **Plugin version**: (see `ai-virtual-try-on.php:6` and line 28)
- **WordPress**: 6.0+
- **PHP**: 7.4+ (8.0+ recommended)
- **WooCommerce**: 5.0+ (optional dependency)
- **Gemini API**: `gemini-2.0-flash-exp-image` model

### Updating Plugin Version (Required After Completed Features)

**CRITICAL:** When a feature/fix task is 100% complete, fully tested, and ready for release, you MUST update the plugin version number. Follow this checklist:

#### 1. Determine Version Number (Semantic Versioning)
- **MAJOR (X.0.0)**: Breaking changes, incompatible API changes
- **MINOR (x.Y.0)**: New features, backward-compatible additions
- **PATCH (x.y.Z)**: Bug fixes, small improvements, layout/UX tweaks

**Examples:**
- Layout improvements → PATCH (2.6.0 → 2.6.1)
- New lightbox feature → MINOR (2.5.2 → 2.6.0)
- Database schema change → MAJOR (2.6.1 → 3.0.0)

#### 2. Update Plugin Header (Line ~6)
```php
/**
 * Version:           2.6.1
 */
```

#### 3. Update Version Constant (Line ~28)
```php
define( 'AVTO_VERSION', '2.6.1' );
```

#### 4. Add Upgrade Routine Entry (After line ~170)
```php
// Upgrade to 2.6.1 - Brief Description
// Key changes:
// - Bullet point 1
// - Bullet point 2
// - Database migrations needed? (yes/no)
if ( version_compare( $from_version, '2.6.1', '<' ) ) {
    // Add any upgrade logic here
    // Or leave empty if no migration needed
}
```

#### 5. Update CHANGELOG.md (Top of file)
Add new version section with complete feature list:
```markdown
## [2.6.1] - YYYY-MM-DD

### Added/Fixed/Changed
- Feature 1 description
- Feature 2 description

### Technical
- Technical details
- Database changes (if any)
```

#### 6. Verification Checklist
- [ ] Plugin header version matches constant version
- [ ] Upgrade routine includes new version entry
- [ ] CHANGELOG.md has detailed release notes
- [ ] Date format is YYYY-MM-DD
- [ ] All three files updated: `ai-virtual-try-on.php` (2 places) + `CHANGELOG.md`

#### When NOT to Update Version
- Work in progress (not fully complete)
- Experimental changes
- Documentation-only updates
- Internal refactoring without user-facing changes

**Remember:** Version updates signal production-ready releases. Only increment when feature is 100% complete, tested, and documented.

## Quick Reference

| Task | Command/Location |
|------|------------------|
| Add new setting | `includes/avto-admin.php` → `avto_register_settings()` |
| Modify API prompt | Settings → AI tab OR filter `avto_gemini_prompt` |
| Change button position | Settings → WooCommerce tab → Display Hook |
| Debug AJAX errors | Enable `avto_debug_mode` option OR check `WP_DEBUG` logs |
| Clear per-user rate limit | Delete transient `avto_rate_limit_user_{$id}` or `avto_rate_limit_ip_{$hash}` |
| Clear global rate limit | Delete transient `avto_global_rate_limit` |
| View rate limit status | Dashboard widget (when global limiting enabled) |
| Test without WC | Use shortcode `[ai_virtual_tryon]` on any page |
