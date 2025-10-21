# AI Virtual Try-On WordPress Plugin

A modern, AI-powered virtual try-on experience for WordPress using Google's Gemini 2.5 Flash Image API. Upload your photo, select clothing, and see yourself wearing it—all without leaving your WordPress site!

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-6.0%2B-brightgreen.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2-red.svg)

## ✨ Features

- **🎨 AI-Powered Image Generation**: Uses Google Gemini 2.5 Flash Image for photorealistic virtual try-ons
- **�️ WooCommerce Integration**: Modal-based try-on experience directly on product pages (NEW in v2.0!)
- **�🔒 Secure Architecture**: Three-tier proxy model keeps API keys safe on the server
- **⚙️ Admin Settings Page**: Full control over AI prompts, clothing items, UI text, WooCommerce settings, and more
- **📸 Wide Format Support**: Accepts JPEG, PNG, WebP, HEIC, and HEIF images (iPhone photos work directly!)
- **👔 Dynamic Clothing Management**: Add/remove/edit clothing items via WordPress Media Library or WooCommerce products
- **⚡ Modern UI/UX**: Responsive, accessible interface with real-time feedback
- **📱 Mobile-Friendly**: Works seamlessly on all devices
- **🚀 Performance Optimized**: Direct filesystem access, conditional asset loading, optional caching
- **♿ Accessible**: WCAG AA compliant with proper ARIA labels and keyboard navigation
- **🔄 AJAX-Powered**: No page reloads, smooth asynchronous operations
- **🖼️ Media Library Integration**: All images managed through WordPress Media Library

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **Gemini API Key**: Free tier available at [Google AI Studio](https://aistudio.google.com/apikey)
- **WooCommerce** (Optional): 5.0 or higher for product page integration

## 🚀 Installation

### Method 1: Upload via WordPress Admin

1. Download the plugin ZIP file
2. Go to **WordPress Admin** → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `ai-virtual-try-on` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

## ⚙️ Configuration

### Step 1: Get Your Gemini API Key

1. Visit [Google AI Studio](https://aistudio.google.com/apikey)
2. Sign in with your Google account
3. Click **Get API Key** or **Create API Key**
4. Copy your API key

### Step 2: Add API Key to WordPress

Open your `wp-config.php` file (located in your WordPress root directory) and add this line **before** the `/* That's all, stop editing! */` comment:

```php
define( 'AVTO_GEMINI_API_KEY', 'YOUR_API_KEY_HERE' );
```

**Important Security Notes:**
- ⚠️ NEVER commit `wp-config.php` to version control
- ⚠️ Keep your API key secret and secure
- ⚠️ The API key should ONLY be in `wp-config.php`, never in the database or plugin files

### Step 3: Add Clothing Images (Optional)

The plugin includes placeholder images. To add your own:

1. Navigate to `/wp-content/plugins/ai-virtual-try-on/assets/images/`
2. Replace the placeholder files:
   - `clothing-1.jpg` - Blue Denim Jacket
   - `clothing-2.jpg` - Red Evening Dress
   - `clothing-3.jpg` - Black Leather Jacket
3. Images should be:
   - Format: JPG, PNG, WebP, HEIC, or HEIF
   - Recommended size: 300x400px minimum
   - Clear product shots with transparent or plain backgrounds work best

## 📖 Usage

### Option 1: WooCommerce Product Pages (Recommended)

**Automatic Integration** - The virtual try-on modal appears on eligible WooCommerce product pages:

1. **Enable Integration**: Go to **WordPress Admin** → **Settings** → **Virtual Try-On** → **WooCommerce** tab
2. **Configure Settings**:
   - **Enable WooCommerce Integration**: Toggle ON
   - **Display Hook**: Choose where the button appears (default: After product summary)
   - **Hook Priority**: Set display order (default: 35)
   - **Target Categories**: Select which product categories show the try-on button
   - **Button Text**: Customize the call-to-action (default: "Virtual Try-On")
3. **How It Works**:
   - A "Virtual Try-On" button appears on product pages in selected categories
   - Clicking opens a modal with the product's images as clothing options
   - Users upload their photo, select a product image, and generate the try-on
   - Modal keeps users in the product context (no navigation required)
   - Uses actual product gallery images (no manual image management!)

**User Experience Flow**:
1. Browse to any WooCommerce product page in a targeted category
2. Click the "Virtual Try-On" button
3. Upload photo in modal (JPG, PNG, WebP, HEIC, HEIF - max 5MB)
4. Select product image from gallery (uses featured + gallery images)
5. Click "Generate Virtual Try-On"
6. Wait 30-60 seconds for AI processing
7. View result and download, or create another try-on

### Option 2: Shortcode (Legacy/Custom Pages)

Add the shortcode to any post, page, or widget where you want the virtual try-on interface to appear:

```
[ai_virtual_tryon]
```

### User Workflow (Shortcode Mode)

1. **Upload Photo**: Users click to upload or drag-and-drop their photo
   - Supported formats: JPG, PNG, WebP, HEIC, HEIF
   - Max file size: Configurable (default 5MB)
2. **Select Clothing**: Click on a clothing item from the gallery
3. **Generate**: Click "Generate Virtual Try-On" button
4. **View Result**: Wait 30-60 seconds for AI processing
5. **Download**: Download the result or create a new try-on

## ⚙️ Admin Settings

Access plugin settings via **WordPress Admin** → **Settings** → **Virtual Try-On**

### WooCommerce Integration (NEW in v2.0!)
- **Enable Integration**: Toggle WooCommerce product page integration ON/OFF
- **Display Hook**: Choose button placement:
  - After product summary (default, priority 35)
  - After product title
  - After product price
  - Before add to cart button
  - After add to cart button
  - After product meta
- **Hook Priority**: Fine-tune display order relative to other elements (1-99)
- **Target Categories**: Select which product categories display the try-on button
- **Button Text**: Customize the call-to-action text (default: "Virtual Try-On")

### AI Configuration
- **Custom AI Prompt**: Modify the text prompt sent to Gemini API for different results
- **Aspect Ratio**: Choose output image ratio (1:1, 16:9, 9:16, etc.)
- **Max File Size**: Set upload limit (1-50MB)

### Clothing Items Management (Shortcode Mode)
- **Add/Remove Items**: Manage your clothing catalog for shortcode pages
- **Upload Images**: Use WordPress Media Library to add clothing images
- **Reorder Items**: Change display order in the gallery

**Note**: WooCommerce mode uses product images automatically - no manual clothing management required!

### UI Customization
- **Button Text**: Customize upload and generate button labels
- **Download Button**: Show/hide the download button on results

### Advanced Settings
- **Debug Mode**: Enable to show detailed API error messages for troubleshooting (disable in production)
- **Caching**: Enable to cache API responses and reduce duplicate requests
- **Cache Duration**: Set how long to cache results (in seconds)
- **Reset Settings**: Restore all settings to defaults

## 🏗️ Architecture

### Three-Tier Proxy Model

```
[User Browser] → [WordPress Backend] → [Gemini API]
     ↓                    ↓                   ↓
  AJAX Request    Validates & Proxies    Generates Image
     ↓                    ↓                   ↓
  Displays Result   Saves to Media      Returns Base64
```

**Security Principle**: The frontend NEVER communicates directly with Gemini API. All API calls go through the WordPress backend proxy.

### File Structure

```
/ai-virtual-try-on/
├── ai-virtual-try-on.php          # Main plugin file
├── /assets/
│   ├── /css/
│   │   └── avto-frontend.css      # UI styling (modal + shortcode)
│   ├── /js/
│   │   └── avto-frontend.js       # Dual-mode logic (modal + shortcode)
│   └── /images/
│       ├── clothing-1.jpg         # Default sample items (shortcode mode)
│       ├── clothing-2.jpg
│       └── clothing-3.jpg
└── /includes/
    ├── avto-shortcode.php         # Shortcode UI rendering
    ├── avto-ajax-handler.php      # Dual-mode API integration
    ├── avto-admin.php             # Admin settings page
    └── avto-woocommerce.php       # WooCommerce integration (NEW in v2.0)
```

## 🔧 Customization

### Adding More Clothing Items

Edit `includes/avto-shortcode.php` and modify the `$clothing_items` array:

```php
$clothing_items = array(
    array(
        'id'    => 'clothing-4',
        'file'  => 'clothing-4.jpg',
        'title' => __( 'White T-Shirt', 'avto' ),
    ),
    // Add more items...
);
```

### Customizing the Prompt

Edit the prompt in `includes/avto-ajax-handler.php` in the `avto_call_gemini_api()` function:

```php
'text' => 'Your custom prompt here...',
```

**Prompt Tips:**
- Be descriptive, not keyword-based
- Mention lighting, shadows, and fit
- Specify to maintain original pose and background
- Request photorealistic results

### Styling Customization

All styles are in `assets/css/avto-frontend.css`. You can:
- Override styles in your theme's CSS
- Modify the plugin CSS directly (not recommended for updates)
- Use WordPress customizer for simple color changes

## 🛡️ Security Features

- ✅ CSRF protection via WordPress nonces
- ✅ File upload validation (type, size, errors)
- ✅ Input sanitization and escaping
- ✅ API key stored securely in `wp-config.php`
- ✅ Server-side only API communication
- ✅ WordPress Media Library integration

## ⚡ Performance

- **Direct Filesystem Access**: WooCommerce mode eliminates HTTP requests (1-2 second improvement per generation)
- **Conditional Loading**: Assets only load on WooCommerce product pages or pages with shortcode
- **Optimized Images**: Recommended compression and sizing
- **Efficient AJAX**: Single request per generation
- **Future-Ready**: Architecture supports caching implementation

## 🐛 Troubleshooting

### "API key not configured" Error

- Check that you've added `define( 'AVTO_GEMINI_API_KEY', 'your-key' );` to `wp-config.php`
- Ensure the constant is defined **before** `/* That's all, stop editing! */`
- Verify the API key is valid

### WooCommerce Button Not Appearing

- Check that WooCommerce is active and version 5.0+
- Go to **Settings** → **Virtual Try-On** → **WooCommerce** and verify integration is enabled
- Ensure you've selected at least one target category
- Visit a product in one of your selected categories
- Clear all caches (WordPress, WooCommerce, browser)

### Modal Not Opening

- Check browser console for JavaScript errors
- Verify jQuery is loaded (WooCommerce typically includes it)
- Clear browser cache
- Try a different browser to rule out extension conflicts

### "File upload error"

- Check PHP upload limits in `php.ini`: `upload_max_filesize` and `post_max_size`
- Verify file permissions on `wp-content/uploads/`
- Ensure file is JPG, PNG, WebP, HEIC, or HEIF and under 5MB

### Request Timeout

- Image generation can take 30-60 seconds
- Check your hosting allows 60+ second PHP execution time
- Verify firewall isn't blocking outbound requests to Google APIs

### Images Not Displaying (Shortcode Mode)

- Check that clothing image files exist in `/assets/images/`
- Verify file names match the array in `avto-shortcode.php`
- Clear browser cache and WordPress cache

## 🔮 Roadmap

Completed in v2.0:
- [x] **WooCommerce Integration**: Product page modal interface
- [x] **Category Targeting**: Control which products show try-on
- [x] **Performance Optimization**: Direct filesystem access
- [x] **REST API**: Settings exposed via WordPress REST API

Future enhancements planned:

- [ ] **React Admin Component**: Modern UI for WooCommerce settings tab
- [ ] **Custom Post Type**: Manage clothing items from WordPress admin (shortcode mode)
- [ ] **API Response Caching**: Use WordPress Transients API
- [ ] **User Galleries**: Save history for logged-in users
- [ ] **Multi-language Support**: Complete internationalization
- [ ] **Aspect Ratio Options**: Let users choose output dimensions
- [ ] **Batch Processing**: Generate multiple try-ons at once
- [ ] **AI Model Selection**: Choose between different Gemini models

## 📝 API Costs

Gemini 2.5 Flash Image pricing:
- **Free Tier**: 15 requests per minute
- **Cost**: $30 per 1 million tokens
- **Per Image**: 1290 tokens (flat rate, up to 1024x1024px)
- **Estimate**: ~$0.04 per generated image

[Check current pricing](https://ai.google.dev/gemini-api/docs/pricing)

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This plugin is licensed under the GNU General Public License v2 or later.

```
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

## 🙏 Credits

- **Gemini API**: Google's Generative AI
- **Icons**: Custom SVG icons
- **Inspiration**: AI-powered fashion tech

## 📧 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/ai-virtual-try-on/issues)
- **Documentation**: [GitHub Wiki](https://github.com/yourusername/ai-virtual-try-on/wiki)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/ai-virtual-try-on/discussions)

## ⚖️ Disclaimer

This plugin uses Google's Gemini API. By using this plugin, you agree to:
- Google's [Terms of Service](https://policies.google.com/terms)
- Gemini API [Prohibited Use Policy](https://policies.google.com/terms/generative-ai/use-policy)

**Important**: 
- Don't upload images that infringe on others' rights
- Don't generate content that is harmful, hateful, or deceptive
- Uploading images of children is not supported in EEA, CH, and UK
- All generated images include SynthID watermark

---

Made with ❤️ and AI | [Report Issues](https://github.com/yourusername/ai-virtual-try-on/issues) | [Request Features](https://github.com/yourusername/ai-virtual-try-on/discussions)
