# 🚀 Quick Start Guide - AI Virtual Try-On Plugin

**For developers who want to get up and running in 5 minutes**

## Prerequisites Checklist

- [ ] WordPress 6.0+ installed
- [ ] PHP 7.4+ running
- [ ] Gemini API key from [Google AI Studio](https://aistudio.google.com/apikey)
- [ ] HTTPS recommended (production)

## Installation (2 minutes)

### Option A: Direct Upload

```bash
# 1. Copy plugin folder to WordPress
cp -r ai-virtual-try-on /path/to/wordpress/wp-content/plugins/

# 2. Set correct permissions
chmod -R 755 /path/to/wordpress/wp-content/plugins/ai-virtual-try-on
```

### Option B: ZIP Upload

```bash
# 1. Create ZIP file
cd /path/to/ai-virtual-try-on
zip -r ai-virtual-try-on.zip . -x "*.git*" "*.DS_Store"

# 2. Upload via WordPress Admin → Plugins → Add New → Upload
```

## Configuration (1 minute)

### 1. Add API Key

Edit `wp-config.php` and add **before** `/* That's all, stop editing! */`:

```php
define( 'AVTO_GEMINI_API_KEY', 'YOUR_ACTUAL_API_KEY_HERE' );
```

### 2. Activate Plugin

```
WordPress Admin → Plugins → AI Virtual Try-On → Activate
```

## Usage (30 seconds)

### Add Shortcode to Any Page

```
[ai_virtual_tryon]
```

That's it! The interface will appear on that page.

## Verify Installation

### Quick Test

1. Visit page with shortcode
2. Open browser console (F12)
3. Check for errors
4. Upload a test image
5. Select clothing item
6. Click "Generate"
7. Wait 30-60 seconds
8. View result

### Expected Behavior

✅ UI loads with no console errors  
✅ File upload works (drag-drop or click)  
✅ Clothing items are selectable  
✅ Generate button enables when both selected  
✅ Loading spinner appears  
✅ Generated image displays  
✅ Download button works  

## Troubleshooting (Common Issues)

### ❌ "API key not configured"

**Solution**: Check wp-config.php has the define statement

```bash
grep "AVTO_GEMINI_API_KEY" wp-config.php
```

### ❌ Assets not loading

**Solution**: Clear WordPress cache and browser cache

```bash
# If using W3 Total Cache or similar
wp cache flush
```

### ❌ File upload fails

**Solution**: Check PHP upload limits

```bash
# Check current limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Should be at least 6M
```

### ❌ Request timeout

**Solution**: Increase PHP max_execution_time

```php
// In php.ini or .htaccess
max_execution_time = 90
```

### ❌ Images not displaying

**Solution**: Add sample images to `/assets/images/`

```bash
# Download sample images (free stock photos)
cd wp-content/plugins/ai-virtual-try-on/assets/images/
wget https://via.placeholder.com/300x400.jpg?text=Clothing+1 -O clothing-1.jpg
wget https://via.placeholder.com/300x400.jpg?text=Clothing+2 -O clothing-2.jpg
wget https://via.placeholder.com/300x400.jpg?text=Clothing+3 -O clothing-3.jpg
```

## File Structure Reference

```
ai-virtual-try-on/
├── ai-virtual-try-on.php          # Main plugin (modify API settings here)
├── /includes/
│   ├── avto-shortcode.php         # Edit UI & clothing items
│   └── avto-ajax-handler.php      # Edit API logic & prompts
├── /assets/
│   ├── /css/avto-frontend.css     # Customize styles
│   ├── /js/avto-frontend.js       # Customize behavior
│   └── /images/                    # Add clothing images here
└── README.md                       # Full documentation
```

## Customization Quick Reference

### Add More Clothing Items

**File**: `includes/avto-shortcode.php` (around line 70)

```php
$clothing_items = array(
    // ... existing items ...
    array(
        'id'    => 'clothing-4',           // Unique ID
        'file'  => 'clothing-4.jpg',       // Filename in /assets/images/
        'title' => __( 'New Item', 'avto' ), // Display name
    ),
);
```

### Customize the AI Prompt

**File**: `includes/avto-ajax-handler.php` (around line 161)

```php
'text' => 'YOUR CUSTOM PROMPT HERE. Describe what you want the AI to do.'
```

**Prompt Tips**:
- Use narrative descriptions, not keywords
- Mention lighting, shadows, fit
- Specify to maintain pose and background
- Be specific about desired result

### Change Colors/Styles

**File**: `assets/css/avto-frontend.css`

```css
/* Primary color (buttons, highlights) */
.avto-generate-btn {
    background: linear-gradient(135deg, #YOUR_COLOR 0%, #YOUR_COLOR_DARK 100%);
}

/* Clothing selection highlight */
.avto-clothing-item.selected {
    border-color: #YOUR_COLOR;
}
```

## API Cost Calculator

```
Gemini 2.5 Flash Image Pricing:
- Per 1M tokens: $30
- Per image: 1290 tokens (~$0.04)

Monthly Estimates:
- 100 generations/month:  ~$4
- 500 generations/month:  ~$20
- 1000 generations/month: ~$40

Free Tier: 15 requests/minute
```

## Development Mode

Enable WordPress debugging:

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check logs:

```bash
tail -f wp-content/debug.log
```

## Testing Commands

```bash
# Test file permissions
ls -la wp-content/plugins/ai-virtual-try-on/

# Test uploads directory writable
touch wp-content/uploads/test.txt && rm wp-content/uploads/test.txt

# Check PHP errors
tail -f /var/log/php-errors.log

# Check WordPress errors
tail -f wp-content/debug.log
```

## Performance Tips

### Production Optimization

1. **Enable Caching**
   ```php
   // Install W3 Total Cache or WP Super Cache
   ```

2. **Optimize Images**
   ```bash
   # Compress clothing images
   jpegoptim --max=80 assets/images/*.jpg
   ```

3. **Enable GZIP**
   ```apache
   # In .htaccess
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/css application/javascript
   </IfModule>
   ```

4. **CDN (Optional)**
   - Serve static assets from CDN
   - Faster global delivery

## Security Checklist

- ✅ API key in wp-config.php (not in code)
- ✅ HTTPS enabled in production
- ✅ WordPress and PHP updated
- ✅ File permissions correct (644/755)
- ✅ Debug mode disabled in production
- ✅ Regular backups configured

## Support Resources

- **Full Documentation**: `README.md`
- **Testing Guide**: `TESTING.md`
- **Development Summary**: `DEVELOPMENT_SUMMARY.md`
- **Changelog**: `CHANGELOG.md`
- **Gemini API Docs**: https://ai.google.dev/gemini-api/docs

## One-Command Test

```bash
# Quick health check
wp plugin is-active ai-virtual-try-on && \
wp eval "echo defined('AVTO_GEMINI_API_KEY') ? 'API Key: OK' : 'API Key: MISSING';" && \
ls wp-content/plugins/ai-virtual-try-on/assets/images/ | grep -E 'clothing-[0-9]+\.jpg' && \
echo "All checks passed!"
```

## Next Steps

1. ✅ Plugin installed and activated
2. ✅ API key configured
3. ✅ Shortcode added to page
4. ✅ Basic test completed
5. → Add custom clothing images
6. → Customize colors/branding
7. → Test with real users
8. → Monitor API usage
9. → Collect feedback
10. → Plan enhancements

## Questions?

- Read `README.md` for comprehensive guide
- Check `TESTING.md` for troubleshooting
- Review code comments for technical details
- Consult Gemini API docs for API questions

---

**⏱️ Total Setup Time: ~5 minutes**  
**🎯 Difficulty Level: Beginner-Friendly**  
**✅ Production Ready: Yes**  

*Happy coding! 🎨*
