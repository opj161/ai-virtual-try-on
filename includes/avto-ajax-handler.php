<?php
/**
 * AJAX Handler: Gemini API Integration
 *
 * @package AI_Virtual_Try_On
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the AJAX request to generate virtual try-on image
 */
function avto_handle_generate_image_request() {
	
	// Security headers - WordPress AJAX already handles most, but add extras
	avto_set_ajax_security_headers();
	
	// 1. VERIFY NONCE - Security First!
	check_ajax_referer( 'avto-generate-image-nonce', 'nonce' );

	// 2. RATE LIMITING - Prevent API abuse
	if ( ! avto_check_rate_limit() ) {
		wp_send_json_error( array(
			'message' => __( 'Too many requests. Please wait a moment before trying again.', 'avto' ),
		) );
	}

	// 3. SANITIZE INPUTS - Support both WooCommerce and shortcode modes
	// WooCommerce mode: product_id + clothing_image_id (integers)
	// Shortcode mode: clothing_id + clothing_file (strings) - backward compatibility
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$clothing_image_id = isset( $_POST['clothing_image_id'] ) ? absint( $_POST['clothing_image_id'] ) : 0;
	
	// Legacy shortcode mode inputs
	$clothing_id = isset( $_POST['clothing_id'] ) ? sanitize_text_field( $_POST['clothing_id'] ) : '';
	$clothing_file = isset( $_POST['clothing_file'] ) ? sanitize_file_name( $_POST['clothing_file'] ) : '';

	// Determine which mode we're in
	$is_wc_mode = ( $product_id > 0 && $clothing_image_id > 0 );
	$is_shortcode_mode = ( ! empty( $clothing_id ) && ! empty( $clothing_file ) );

	if ( ! $is_wc_mode && ! $is_shortcode_mode ) {
		wp_send_json_error( array(
			'message' => __( 'Please select a clothing item.', 'avto' ),
		) );
	}

	// 4. HANDLE USER IMAGE - Either new upload OR existing attachment ID
	$attachment_id = 0;
	$user_image_path = '';
	
	// Check if user is using existing default image
	if ( isset( $_POST['user_image_id'] ) && ! empty( $_POST['user_image_id'] ) ) {
		// Using existing image from Media Library
		$attachment_id = absint( $_POST['user_image_id'] );
		
		// Security: Verify attachment exists and belongs to current user
		$attachment = get_post( $attachment_id );
		
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			wp_send_json_error( array(
				'message' => __( 'Default user image not found. Please upload a new photo.', 'avto' ),
			) );
		}
		
		// Verify attachment ownership - must be uploaded by current user
		if ( absint( $attachment->post_author ) !== get_current_user_id() ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to use this image.', 'avto' ),
			) );
		}
		
		// Get file path
		$user_image_path = get_attached_file( $attachment_id );
		
		if ( ! $user_image_path || ! file_exists( $user_image_path ) ) {
			wp_send_json_error( array(
				'message' => __( 'Default user image not found. Please upload a new photo.', 'avto' ),
			) );
		}
		
		// Validate MIME type of existing file
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$actual_mime = finfo_file( $finfo, $user_image_path );
		finfo_close( $finfo );
		
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
		if ( ! in_array( $actual_mime, $allowed_types, true ) ) {
			wp_send_json_error( array(
				'message' => __( 'Default image has invalid format. Please upload a new photo.', 'avto' ),
			) );
		}
		
	} else {
		// New file upload - original validation logic
		if ( ! isset( $_FILES['user_image'] ) || $_FILES['user_image']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array(
				'message' => __( 'File upload error. Please try again.', 'avto' ),
			) );
		}

		$file = $_FILES['user_image'];

		// Validate MIME type - check both client-provided and actual file content
		// Gemini API supports: PNG, JPEG, WEBP, HEIC, HEIF (but NOT AVIF)
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
		$file_type = wp_check_filetype( $file['name'] );
		
		// First check: client-provided MIME type (quick, but unreliable)
		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid file type. Please upload a JPG, PNG, WebP, HEIC, or HEIF image.', 'avto' ),
			) );
		}
		
		// Second check: actual file content MIME type (reliable, prevents unsupported format issues)
		// This is critical because browsers may convert images to formats not supported by Gemini API
		$temp_path = $file['tmp_name'];
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$actual_mime = finfo_file( $finfo, $temp_path );
		finfo_close( $finfo );
		
		if ( ! in_array( $actual_mime, $allowed_types, true ) ) {
			wp_send_json_error( array(
				'message' => sprintf( 
					/* translators: %s: detected MIME type */
					__( 'Unsupported image format detected (%s). Supported formats: JPG, PNG, WebP, HEIC, and HEIF. AVIF format is not supported by the AI service.', 'avto' ),
					$actual_mime 
				),
			) );
		}

		// Validate file size - get max size from settings
		$max_size_mb = get_option( 'avto_max_file_size', 5 );
		$max_size = $max_size_mb * 1024 * 1024; // Convert MB to bytes
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( array(
				'message' => sprintf( __( 'File size must be less than %dMB.', 'avto' ), $max_size_mb ),
			) );
		}

		// USE WORDPRESS MEDIA LIBRARY
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'user_image', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array(
				'message' => $attachment_id->get_error_message(),
			) );
		}

		// Get the user image file path
		$user_image_path = get_attached_file( $attachment_id );
		
		if ( ! $user_image_path || ! file_exists( $user_image_path ) ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to process uploaded image. Please try again.', 'avto' ),
			) );
		}
	}

	// 5. GET CLOTHING IMAGE PATH - Different logic for WooCommerce vs Shortcode modes
	$clothing_image_path = '';
	
	if ( $is_wc_mode ) {
		// WooCommerce mode: Direct file path retrieval (FAST - no HTTP request!)
		$clothing_image_path = get_attached_file( $clothing_image_id );
		
		if ( ! $clothing_image_path || ! file_exists( $clothing_image_path ) ) {
			wp_send_json_error( array(
				'message' => __( 'Selected product image not found. Please refresh the page and try again.', 'avto' ),
			) );
		}
	} else {
		// Shortcode mode: Legacy logic (backward compatibility)
		$clothing_items = get_option( 'avto_clothing_items', array() );
		$selected_item = null;

		// Find the selected clothing item - support both 'id' and array index matching
		foreach ( $clothing_items as $index => $item ) {
			// Check if item has 'id' property that matches
			if ( isset( $item['id'] ) && $item['id'] === $clothing_id ) {
				$selected_item = $item;
				break;
			}
			// Fallback: check if the index matches (for legacy data)
			if ( (string) $index === $clothing_id ) {
				$selected_item = $item;
				break;
			}
		}

		if ( ! $selected_item ) {
			wp_send_json_error( array(
				'message' => __( 'Selected clothing item not found. Please refresh the page and try again.', 'avto' ),
				'debug' => array(
					'requested_id' => $clothing_id,
					'available_items' => array_map( function( $item ) {
						return isset( $item['id'] ) ? $item['id'] : 'no-id';
					}, $clothing_items ),
				),
			) );
		}

		// Get image URL - support both 'image' and 'file_url' properties
		$clothing_image_url = '';
		if ( isset( $selected_item['image'] ) && ! empty( $selected_item['image'] ) ) {
			$clothing_image_url = $selected_item['image'];
		} elseif ( isset( $selected_item['file_url'] ) && ! empty( $selected_item['file_url'] ) ) {
			$clothing_image_url = $selected_item['file_url'];
		}

		if ( empty( $clothing_image_url ) ) {
			wp_send_json_error( array(
				'message' => __( 'Clothing item image URL is missing. Please check the settings.', 'avto' ),
			) );
		}

		// Download clothing image to temporary file for processing
		$clothing_response = wp_remote_get( 
			$clothing_image_url,
			array(
				'timeout' => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $clothing_response ) ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to retrieve clothing image. Please try again.', 'avto' ),
			) );
		}

		$clothing_image_data = wp_remote_retrieve_body( $clothing_response );
		
		if ( empty( $clothing_image_data ) ) {
			wp_send_json_error( array(
				'message' => __( 'Clothing image data is empty. Please try again.', 'avto' ),
			) );
		}

		// Save to temporary file
		$temp_file = wp_tempnam( 'avto-clothing-' );
		$saved = file_put_contents( $temp_file, $clothing_image_data );
		
		if ( false === $saved ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to save clothing image temporarily.', 'avto' ),
			) );
		}

		$clothing_image_path = $temp_file;
	}

	// 6. CHECK CACHE (if enabled)
	$cache_enabled = get_option( 'avto_enable_caching', false );
	$cached_result = false;
	$cache_key = '';
	
	if ( $cache_enabled ) {
		// Generate cache key based on user image, clothing image, and AI prompt
		$ai_prompt = get_option( 'avto_ai_prompt', avto_get_default_prompt() );
		$cache_key = 'avto_result_' . md5( 
			$user_image_path . 
			$clothing_image_path . 
			$ai_prompt . 
			AVTO_VERSION 
		);
		
		// Try to get cached result
		$cached_result = get_transient( $cache_key );
		
		if ( false !== $cached_result && ! empty( $cached_result['image_url'] ) ) {
			// Verify cached image still exists
			$cached_attachment_id = attachment_url_to_postid( $cached_result['image_url'] );
			if ( $cached_attachment_id && get_post_status( $cached_attachment_id ) === 'inherit' ) {
				// Clean up temporary file if in shortcode mode
				if ( ! $is_wc_mode && file_exists( $clothing_image_path ) ) {
					@unlink( $clothing_image_path );
				}
				
				// Return cached result
				wp_send_json_success( array(
					'image_url' => $cached_result['image_url'],
					'message'   => __( 'Virtual try-on generated successfully! (from cache)', 'avto' ),
					'cached'    => true,
				) );
			} else {
				// Cached image no longer exists, delete transient
				delete_transient( $cache_key );
			}
		}
	}

	// 7. CALL GEMINI API
	// Allow developers to hook before API call
	do_action( 'avto_before_api_call', $user_image_path, $clothing_image_path, $product_id, $clothing_image_id );
	
	$result = avto_call_gemini_api( $user_image_path, $clothing_image_path, $product_id, $attachment_id, $clothing_image_id );

	// Clean up temporary file (only needed in shortcode mode)
	if ( ! $is_wc_mode && file_exists( $clothing_image_path ) ) {
		@unlink( $clothing_image_path );
	}

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array(
			'message' => $result->get_error_message(),
		) );
	}

	// 8. CACHE THE RESULT (if enabled)
	if ( $cache_enabled && ! empty( $cache_key ) ) {
		$cache_duration = get_option( 'avto_cache_duration', 86400 );
		set_transient( $cache_key, $result, absint( $cache_duration ) );
	}

	// 9. RETURN SUCCESS RESPONSE
	wp_send_json_success( array(
		'image_url' => $result['image_url'],
		'message'   => __( 'Virtual try-on generated successfully!', 'avto' ),
	) );
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action( 'wp_ajax_avto_generate_image', 'avto_handle_generate_image_request' );
add_action( 'wp_ajax_nopriv_avto_generate_image', 'avto_handle_generate_image_request' );

/**
 * Call Gemini API to generate virtual try-on image
 *
 * @param string $user_image_path Path to user's uploaded image
 * @param string $clothing_image_path Path to clothing item image
 * @param int $product_id Optional. WooCommerce product ID (0 for shortcode mode)
 * @param int $user_photo_attach_id Optional. Attachment ID of user's uploaded photo (for history tracking)
 * @param int $clothing_image_id Optional. Attachment ID of clothing image (for history tracking)
 * @return array|WP_Error Array with image_url on success, WP_Error on failure
 */
function avto_call_gemini_api( $user_image_path, $clothing_image_path, $product_id = 0, $user_photo_attach_id = 0, $clothing_image_id = 0 ) {
	
	// Check if API key is configured
	if ( ! defined( 'AVTO_GEMINI_API_KEY' ) || empty( AVTO_GEMINI_API_KEY ) ) {
		return new WP_Error(
			'no_api_key',
			__( 'Gemini API key not configured. Please add it to wp-config.php.', 'avto' )
		);
	}

	// Encode images to base64
	$user_image_data = base64_encode( file_get_contents( $user_image_path ) );
	$clothing_image_data = base64_encode( file_get_contents( $clothing_image_path ) );

	if ( ! $user_image_data || ! $clothing_image_data ) {
		return new WP_Error(
			'encoding_error',
			__( 'Failed to encode images. Please try again.', 'avto' )
		);
	}

	// Determine MIME types using finfo_file() (consistent with upload validation)
	$finfo = finfo_open( FILEINFO_MIME_TYPE );
	$user_mime = finfo_file( $finfo, $user_image_path );
	$clothing_mime = finfo_file( $finfo, $clothing_image_path );
	finfo_close( $finfo );

	// Validate MIME types match allowed formats
	$allowed_mime_types = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
	
	if ( ! in_array( $user_mime, $allowed_mime_types, true ) ) {
		return new WP_Error(
			'invalid_user_mime',
			sprintf(
				/* translators: %s: detected MIME type */
				__( 'Invalid user image MIME type detected: %s. This should not happen after validation.', 'avto' ),
				$user_mime
			)
		);
	}
	
	if ( ! in_array( $clothing_mime, $allowed_mime_types, true ) ) {
		return new WP_Error(
			'invalid_clothing_mime',
			sprintf(
				/* translators: %s: detected MIME type */
				__( 'Invalid clothing image MIME type detected: %s. Please check the clothing item configuration.', 'avto' ),
				$clothing_mime
			)
		);
	}

	// Prepare the API request body
	$request_body = array(
		'contents' => array(
			array(
				'parts' => array(
					// Image 1: User's photo (FIRST)
					array(
						'inline_data' => array(
							'mime_type' => $user_mime,
							'data'      => $user_image_data,
						),
					),
					// Image 2: Clothing item (SECOND)
					array(
						'inline_data' => array(
							'mime_type' => $clothing_mime,
							'data'      => $clothing_image_data,
						),
					),
					// Text prompt (LAST) - Get from settings with default fallback
					array(
						'text' => apply_filters( 
							'avto_gemini_prompt', 
							get_option( 'avto_ai_prompt', avto_get_default_prompt() ),
							$user_image_path,
							$clothing_image_path,
							$product_id
						),
					),
				),
			),
		),
		'generationConfig' => apply_filters(
			'avto_gemini_generation_config',
			array(
				'responseModalities' => array( 'Image' ),
				'imageConfig' => array(
					'aspectRatio' => get_option( 'avto_aspect_ratio', '1:1' ), // Get aspect ratio from settings
				),
			),
			$product_id
		),
	);

	// Allow modification of entire request body
	$request_body = apply_filters( 'avto_gemini_request_body', $request_body, $user_image_path, $clothing_image_path, $product_id );

	// API endpoint
	$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent';

	// Make the API request
	$response = wp_remote_post(
		$api_url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'x-goog-api-key' => AVTO_GEMINI_API_KEY,
			),
			'body'    => wp_json_encode( $request_body ),
			'timeout' => 60, // 60 seconds for image generation
		)
	);

	// Check for errors
	if ( is_wp_error( $response ) ) {
		$error_msg = sprintf(
			/* translators: %s: error message */
			__( 'API request failed: %s', 'avto' ),
			$response->get_error_message()
		);
		
		// Add debug info if debug mode is enabled
		if ( get_option( 'avto_debug_mode', false ) ) {
			$error_msg .= ' | Debug: wp_remote_post() error - ' . $response->get_error_message();
		}
		
		return new WP_Error( 'api_request_failed', $error_msg );
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	if ( $response_code !== 200 ) {
		$error_data = json_decode( $response_body, true );
		$error_message = isset( $error_data['error']['message'] ) 
			? $error_data['error']['message'] 
			: __( 'API returned an error. Please try again.', 'avto' );

		// Add debug info if debug mode is enabled
		$debug_info = '';
		if ( get_option( 'avto_debug_mode', false ) ) {
			$debug_info = ' | Debug: Response Code ' . $response_code . ' | Full Response: ' . $response_body;
		}

		return new WP_Error(
			'api_error',
			sprintf(
				/* translators: %s: API error message */
				__( 'Gemini API Error: %s', 'avto' ),
				$error_message
			) . $debug_info
		);
	}

	// Parse the response
	$data = json_decode( $response_body, true );

	if ( ! isset( $data['candidates'][0]['content']['parts'][0]['inlineData']['data'] ) ) {
		$error_msg = __( 'Invalid API response format. Please try again.', 'avto' );
		
		// Add debug info if debug mode is enabled
		if ( get_option( 'avto_debug_mode', false ) ) {
			$error_msg .= ' | Debug: Missing inlineData in response. Full Response: ' . $response_body;
		}
		
		return new WP_Error( 'invalid_response', $error_msg );
	}

	// Extract the base64 image data
	$base64_image = $data['candidates'][0]['content']['parts'][0]['inlineData']['data'];

	// Decode and save to Media Library
	$image_data = base64_decode( $base64_image );

	if ( ! $image_data ) {
		return new WP_Error(
			'decoding_error',
			__( 'Failed to decode generated image. Please try again.', 'avto' )
		);
	}

	// Save to WordPress uploads directory
	$upload_dir = wp_upload_dir();
	$filename = 'virtual-tryon-' . uniqid() . '.png';
	$filepath = $upload_dir['path'] . '/' . $filename;

	// Write file
	$file_saved = file_put_contents( $filepath, $image_data );

	if ( ! $file_saved ) {
		return new WP_Error(
			'save_error',
			__( 'Failed to save generated image. Please check file permissions.', 'avto' )
		);
	}

	// Create attachment post
	$attachment = array(
		'post_mime_type' => 'image/png',
		'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $filepath );

	if ( is_wp_error( $attach_id ) ) {
		return new WP_Error(
			'attachment_error',
			__( 'Failed to create media library entry. Please try again.', 'avto' )
		);
	}

	// Generate attachment metadata
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	// Get image URL
	$image_url = wp_get_attachment_url( $attach_id );

	// Allow developers to hook after successful generation
	// Pass all necessary IDs for history tracking and extensibility
	do_action( 'avto_after_generation_success', $attach_id, $image_url, $product_id, $user_photo_attach_id, $clothing_image_id );

	// Return the image URL
	return apply_filters(
		'avto_generation_result',
		array(
			'image_url'     => $image_url,
			'attachment_id' => $attach_id,
		),
		$product_id
	);
}

/**
 * Rate Limiting Function
 * 
 * Prevents API abuse by limiting requests per user/session AND globally.
 * Uses WordPress transients with user ID or session token as key.
 * 
 * Implements two-tier rate limiting:
 * 1. Per-user/IP limits (always active)
 * 2. Global site-wide limits (optional)
 * 
 * @return bool True if request is allowed, false if rate limit exceeded.
 */
function avto_check_rate_limit() {
	// Get rate limit settings (default: 10 requests per 60 seconds per user)
	$max_requests = (int) get_option( 'avto_rate_limit_requests', 10 );
	$time_window = (int) get_option( 'avto_rate_limit_window', 60 );

	// Allow developers to bypass rate limiting
	if ( apply_filters( 'avto_bypass_rate_limit', false ) ) {
		return true;
	}

	// ===== CHECK 1: GLOBAL (SITE-WIDE) RATE LIMIT =====
	$enable_global_limit = get_option( 'avto_enable_global_rate_limit', false );
	
	if ( $enable_global_limit ) {
		$global_max_requests = (int) get_option( 'avto_global_rate_limit_requests', 100 );
		$global_time_window = (int) get_option( 'avto_global_rate_limit_window', 3600 );
		
		$global_transient_key = 'avto_global_rate_limit';
		$global_count = get_transient( $global_transient_key );
		
		if ( false === $global_count ) {
			// First global request - start tracking
			set_transient( $global_transient_key, 1, $global_time_window );
		} else {
			// Check if global limit exceeded
			if ( $global_count >= $global_max_requests ) {
				// Log global violation
				do_action( 'avto_global_rate_limit_exceeded', $global_count, $global_max_requests );
				
				// Set admin notice
				set_transient( 'avto_global_rate_limit_warning', true, 3600 );
				
				wp_send_json_error( array(
					'message' => __( 'Site-wide generation limit reached. Please try again later.', 'avto' ),
					'code'    => 'global_rate_limit_exceeded',
				) );
				return false;
			}
			
			// Increment global counter
			set_transient( $global_transient_key, $global_count + 1, $global_time_window );
		}
	}

	// ===== CHECK 2: PER-USER/IP RATE LIMIT =====
	// Identify user - prefer user ID, fall back to IP address for guests
	$user_id = get_current_user_id();
	if ( $user_id > 0 ) {
		$identifier = 'user_' . $user_id;
	} else {
		// For guests, use IP address (sanitized)
		$ip_address = avto_get_client_ip();
		$identifier = 'ip_' . md5( $ip_address );
	}

	// Create transient key
	$transient_key = 'avto_rate_limit_' . $identifier;

	// Get current request count
	$request_count = get_transient( $transient_key );

	if ( false === $request_count ) {
		// First request - start tracking
		set_transient( $transient_key, 1, $time_window );
		return true;
	}

	// Check if limit exceeded
	if ( $request_count >= $max_requests ) {
		// Track violations for admin notice
		$violation_count = (int) get_transient( 'avto_rate_limit_violations' );
		$violation_count++;
		set_transient( 'avto_rate_limit_violations', $violation_count, 3600 );
		
		// Trigger admin warning if violations exceed threshold (e.g., 5 in an hour)
		if ( $violation_count >= 5 ) {
			set_transient( 'avto_rate_limit_warning', true, 3600 );
		}
		
		// Allow developers to log rate limit violations
		do_action( 'avto_rate_limit_exceeded', $identifier, $request_count );
		return false;
	}

	// Increment counter
	set_transient( $transient_key, $request_count + 1, $time_window );
	return true;
}

/**
 * Get Client IP Address
 * 
 * Safely retrieves the client's IP address, checking common proxy headers.
 * 
 * @return string Client IP address.
 */
function avto_get_client_ip() {
	$ip_keys = array(
		'HTTP_CF_CONNECTING_IP', // Cloudflare
		'HTTP_X_FORWARDED_FOR',  // Standard proxy header
		'HTTP_X_REAL_IP',        // Nginx
		'REMOTE_ADDR',           // Direct connection
	);

	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			// For X-Forwarded-For, take first IP in comma-separated list
			if ( strpos( $ip, ',' ) !== false ) {
				$ip_list = explode( ',', $ip );
				$ip = trim( $ip_list[0] );
			}
			// Validate IP address format
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}

	return '0.0.0.0'; // Fallback if no valid IP found
}

/**
 * Set Security Headers for AJAX Requests
 * 
 * Adds additional security headers to AJAX responses.
 * WordPress already handles most security, but we add extras for defense-in-depth.
 */
function avto_set_ajax_security_headers() {
	// Prevent response from being cached
	if ( ! headers_sent() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		
		// Content Security Policy - only allow same-origin resources
		header( "Content-Security-Policy: default-src 'self'" );
		
		// Prevent MIME type sniffing
		header( 'X-Content-Type-Options: nosniff' );
		
		// XSS Protection (legacy browsers)
		header( 'X-XSS-Protection: 1; mode=block' );
		
		// Prevent framing (clickjacking protection)
		header( 'X-Frame-Options: SAMEORIGIN' );
		
		// Referrer policy - only send origin for cross-origin requests
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}
