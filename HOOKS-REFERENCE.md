# Quick Reference - Extensibility Hooks (v2.2.0+)

## Action Hooks

### `avto_before_api_call`
**Fires:** Before calling Gemini API  
**Parameters:** `($user_image_path, $clothing_image_path, $product_id, $clothing_image_id)`  
**Use For:** Analytics, logging, usage restrictions

```php
add_action('avto_before_api_call', 'my_function', 10, 4);
```

### `avto_after_generation_success`
**Fires:** After successful image generation  
**Parameters:** `($attachment_id, $image_url, $product_id)`  
**Use For:** Notifications, metadata, conversion tracking

```php
add_action('avto_after_generation_success', 'my_function', 10, 3);
```

---

## Filter Hooks

### `avto_gemini_prompt`
**Returns:** `string` - AI prompt  
**Parameters:** `($prompt, $user_image_path, $clothing_image_path, $product_id)`  
**Use For:** Dynamic prompts, localization, A/B testing

```php
add_filter('avto_gemini_prompt', 'my_function', 10, 4);
```

### `avto_gemini_generation_config`
**Returns:** `array` - Generation configuration  
**Parameters:** `($config, $product_id)`  
**Use For:** Aspect ratios, custom API parameters

```php
add_filter('avto_gemini_generation_config', 'my_function', 10, 2);
```

### `avto_gemini_request_body`
**Returns:** `array` - Complete API request  
**Parameters:** `($request_body, $user_image_path, $clothing_image_path, $product_id)`  
**Use For:** Advanced API customizations

```php
add_filter('avto_gemini_request_body', 'my_function', 10, 4);
```

### `avto_generation_result`
**Returns:** `array` - Final result (`image_url`, `attachment_id`)  
**Parameters:** `($result, $product_id)`  
**Use For:** Watermarks, social sharing, custom URLs

```php
add_filter('avto_generation_result', 'my_function', 10, 2);
```

---

## Quick Examples

### Restrict to Premium Users
```php
add_action('avto_before_api_call', function() {
    if (!current_user_can('premium_member')) {
        wp_send_json_error(['message' => 'Premium only']);
    }
}, 10, 4);
```

### Product-Specific Prompts
```php
add_filter('avto_gemini_prompt', function($prompt, $u, $c, $pid) {
    if (has_term('dresses', 'product_cat', $pid)) {
        $prompt .= ' Show elegant fabric flow.';
    }
    return $prompt;
}, 10, 4);
```

### Track Usage
```php
add_action('avto_after_generation_success', function($aid, $url, $pid) {
    update_post_meta($aid, '_generated_for_product', $pid);
    update_post_meta($aid, '_generated_by_user', get_current_user_id());
}, 10, 3);
```

---

**Full Documentation:** See `DEVELOPER-GUIDE.md`
