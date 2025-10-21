# Placeholder Images

This directory should contain the clothing item images for the virtual try-on gallery.

## Required Files

- `clothing-1.jpg` - Blue Denim Jacket
- `clothing-2.jpg` - Red Evening Dress  
- `clothing-3.jpg` - Black Leather Jacket

## Image Specifications

### Format
- **Accepted**: JPG or PNG
- **Recommended**: JPG for photographs, PNG for items with transparency

### Dimensions
- **Minimum**: 300 x 400 pixels
- **Recommended**: 600 x 800 pixels
- **Aspect Ratio**: 3:4 (portrait orientation works best)

### Quality Guidelines

1. **Clear Product Shots**: Use high-quality, well-lit images
2. **Plain Background**: White or transparent backgrounds work best
3. **Centered Subject**: Clothing item should be centered in frame
4. **No Text**: Avoid images with text overlays or watermarks
5. **File Size**: Keep under 500KB for optimal loading

### Example Sources

You can obtain clothing images from:
- Stock photo sites (Unsplash, Pexels, Pixabay)
- Fashion e-commerce sites (with proper licensing)
- Your own product photography
- AI-generated clothing mockups

## Creating Placeholder SVGs

If you don't have images yet, the plugin will automatically generate SVG placeholders with the clothing item titles.

## Adding More Items

To add more clothing items:

1. Add image files to this directory (e.g., `clothing-4.jpg`)
2. Edit `includes/avto-shortcode.php`
3. Add a new entry to the `$clothing_items` array:

```php
array(
    'id'    => 'clothing-4',
    'file'  => 'clothing-4.jpg',
    'title' => __( 'Your Item Name', 'avto' ),
),
```

## Best Practices for Virtual Try-On

### Optimal Image Characteristics

1. **Full-body shots** of clothing work better than close-ups
2. **Flat lay** or mannequin photos provide clearest clothing details
3. **Consistent lighting** across all items creates better results
4. **Similar perspectives** help maintain visual consistency

### What Works Best

✅ Clean product photography  
✅ Solid color backgrounds  
✅ Well-defined clothing edges  
✅ Proper exposure and lighting  
✅ Minimal shadows  

### What to Avoid

❌ Busy or patterned backgrounds  
❌ Multiple items in one image  
❌ Extreme angles or perspectives  
❌ Low resolution or blurry images  
❌ Heavy text or graphics  

## Copyright and Licensing

⚠️ **Important**: Ensure you have the rights to use any images you add to this directory.

- Use royalty-free stock images
- Use your own photography
- Respect image licenses and attributions
- Don't use copyrighted material without permission

## Testing Your Images

After adding images:

1. Activate the plugin
2. Add the `[ai_virtual_tryon]` shortcode to a page
3. View the page to ensure images load correctly
4. Test the virtual try-on functionality
5. Check that generated results look realistic

## Technical Notes

- Images are base64-encoded before sending to Gemini API
- The API supports images up to 5MB
- JPEG and PNG are the only supported formats
- Images are cached in browser after first load
- Generated results are saved to WordPress Media Library
