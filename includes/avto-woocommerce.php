<?php
/**
 * WooCommerce Integration
 *
 * Handles all WooCommerce-specific functionality including:
 * - Button injection on product pages
 * - Product data localization for frontend
 * - Modal HTML structure
 * 
 * @package AI_Virtual_Try_On
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize WooCommerce integration hooks
 */
function avto_init_wc_integration() {
	// Check if WooCommerce is active
	if ( ! function_exists( 'WC' ) ) {
		return;
	}
	
	// Get settings
	$is_enabled = get_option( 'avto_wc_integration_enabled', false );
	
	if ( ! $is_enabled ) {
		return;
	}
	
	// Get the hook location and priority from settings
	$hook_location = get_option( 'avto_wc_display_hook', 'woocommerce_single_product_summary' );
	$hook_priority = get_option( 'avto_wc_hook_priority', 35 );
	
	// Dynamically attach button function to selected hook
	add_action( $hook_location, 'avto_wc_add_tryon_button', $hook_priority );
	
	// Add modal HTML to footer
	add_action( 'wp_footer', 'avto_wc_add_modal_html' );
}
add_action( 'init', 'avto_init_wc_integration' );

/**
 * Add Virtual Try-On button to WooCommerce product page
 * 
 * Button displays on product pages based on:
 * - WooCommerce integration enabled
 * - Target category matching (or no categories specified)
 * - Product has images
 */
function avto_wc_add_tryon_button() {
	// Verify we're on a single product page
	if ( ! is_product() ) {
		return;
	}
	
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	
	// CRITICAL: Check if WooCommerce integration is enabled
	$integration_enabled = get_option( 'avto_wc_integration_enabled', false );
	if ( ! $integration_enabled ) {
		return;
	}
	
	// Get settings
	$target_categories = get_option( 'avto_wc_target_categories', array() );
	$button_text       = get_option( 'avto_wc_button_text', __( 'Virtual Try-On', 'avto' ) );
	
	// Check if product is in target categories
	$product_categories = $product->get_category_ids();
	$is_targeted        = ! empty( array_intersect( $product_categories, $target_categories ) );
	
	// If no categories targeted, show on all products
	// If categories specified, only show on matching products
	if ( ! empty( $target_categories ) && ! $is_targeted ) {
		return;
	}
	
	// Check if product has images
	$main_image_id = $product->get_image_id();
	if ( ! $main_image_id ) {
		// No images, don't show button
		return;
	}
	
	// Output button with product ID in data attribute
	printf(
		'<button type="button" class="avto-wc-tryon-trigger button" data-product-id="%d">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
				<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
				<circle cx="8.5" cy="8.5" r="1.5"></circle>
				<polyline points="21 15 16 10 5 21"></polyline>
			</svg>
			%s
		</button>',
		esc_attr( $product->get_id() ),
		esc_html( $button_text )
	);
}

/**
 * AJAX handler to get product image data
 * 
 * Optimized with caching and efficient data retrieval
 */
function avto_get_product_images_ajax() {
	// Verify nonce
	check_ajax_referer( 'avto-product-images-nonce', 'nonce' );
	
	$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
	
	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid product ID', 'avto' ) ) );
	}
	
	// Check cache first (5-minute transient for product images)
	$cache_key = 'avto_product_images_' . $product_id;
	$cached_images = get_transient( $cache_key );
	
	if ( false !== $cached_images ) {
		wp_send_json_success( array(
			'images'     => $cached_images,
			'productId' => $product_id,
			'cached'    => true,
		) );
	}
	
	$product = wc_get_product( $product_id );
	
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'Product not found', 'avto' ) ) );
	}
	
	$images = array();
	$all_image_ids = array();
	
	// Get featured image
	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$all_image_ids[] = $image_id;
	}
	
	// Get gallery images
	$gallery_ids = $product->get_gallery_image_ids();
	if ( ! empty( $gallery_ids ) ) {
		$all_image_ids = array_merge( $all_image_ids, $gallery_ids );
	}
	
	if ( empty( $all_image_ids ) ) {
		wp_send_json_error( array( 'message' => __( 'No images found for this product', 'avto' ) ) );
	}
	
	// Batch fetch all alt text with a single query (performance optimization)
	$alt_texts = array();
	if ( ! empty( $all_image_ids ) ) {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $all_image_ids ), '%d' ) );
		$query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
				  WHERE post_id IN ($placeholders) AND meta_key = '_wp_attachment_image_alt'";
		$results = $wpdb->get_results( $wpdb->prepare( $query, $all_image_ids ) );
		
		foreach ( $results as $row ) {
			$alt_texts[ $row->post_id ] = $row->meta_value;
		}
	}
	
	// Build image array
	$product_name = $product->get_name();
	foreach ( $all_image_ids as $index => $attachment_id ) {
		$images[] = array(
			'id'  => (int) $attachment_id,
			'url' => wp_get_attachment_image_url( $attachment_id, 'full' ),
			'alt' => isset( $alt_texts[ $attachment_id ] ) ? $alt_texts[ $attachment_id ] : $product_name,
			'name' => isset( $alt_texts[ $attachment_id ] ) && ! empty( $alt_texts[ $attachment_id ] ) 
					  ? $alt_texts[ $attachment_id ] 
					  : sprintf( __( 'Product Image %d', 'avto' ), $index + 1 ),
		);
	}
	
	// Cache for 5 minutes (product images don't change frequently)
	set_transient( $cache_key, $images, 300 );
	
	// Allow developers to filter images
	$images = apply_filters( 'avto_product_images', $images, $product_id, $product );
	
	wp_send_json_success( array(
		'images'     => $images,
		'productId' => $product_id,
		'cached'    => false,
	) );
}
add_action( 'wp_ajax_avto_get_product_images', 'avto_get_product_images_ajax' );
add_action( 'wp_ajax_nopriv_avto_get_product_images', 'avto_get_product_images_ajax' );

/**
 * Clear product image cache when product is updated
 * 
 * Ensures cached data stays fresh
 */
function avto_clear_product_image_cache( $product_id ) {
	$cache_key = 'avto_product_images_' . $product_id;
	delete_transient( $cache_key );
}
add_action( 'woocommerce_update_product', 'avto_clear_product_image_cache' );
add_action( 'woocommerce_new_product', 'avto_clear_product_image_cache' );

/**
 * NOTE: Product data localization moved to main plugin file (ai-virtual-try-on.php)
 * in avto_enqueue_frontend_assets() to ensure script is enqueued before localizing.
 * The old avto_wc_enqueue_product_data() function has been removed.
 */

/**
 * Add hidden modal HTML to footer
 */
function avto_wc_add_modal_html() {
	// Only on single product pages with feature enabled
	if ( ! is_product() || ! get_option( 'avto_wc_integration_enabled', false ) ) {
		return;
	}
	
	// Check if we should show on this product (category check)
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	
	$target_categories = get_option( 'avto_wc_target_categories', array() );
	if ( ! empty( $target_categories ) ) {
		$product_categories = $product->get_category_ids();
		$is_targeted        = ! empty( array_intersect( $product_categories, $target_categories ) );
		
		if ( ! $is_targeted ) {
			return;
		}
	}
	
	?>
	<div id="avto-modal" class="avto-modal" style="display: none;" aria-hidden="true" role="dialog" aria-labelledby="avto-modal-title">
		<div class="avto-modal-overlay" aria-label="<?php esc_attr_e( 'Close modal', 'avto' ); ?>"></div>
		<div class="avto-modal-container">
			<button type="button" class="avto-modal-close" aria-label="<?php esc_attr_e( 'Close', 'avto' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="18" y1="6" x2="6" y2="18"></line>
					<line x1="6" y1="6" x2="18" y2="18"></line>
				</svg>
			</button>
			<h2 id="avto-modal-title" class="avto-modal-title"><?php esc_html_e( 'Virtual Try-On', 'avto' ); ?></h2>
			
			<!-- Main try-on UI container -->
			<div id="avto-container" class="avto-container">
				<!-- Content will be dynamically populated by JavaScript -->
			</div>
		</div>
	</div>
	<?php
}

/**
 * Get WooCommerce product categories for admin settings
 */
function avto_get_wc_product_categories() {
	if ( ! function_exists( 'WC' ) ) {
		return array();
	}
	
	$categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	) );
	
	if ( is_wp_error( $categories ) ) {
		return array();
	}
	
	$category_options = array();
	foreach ( $categories as $category ) {
		$category_options[] = array(
			'id'   => $category->term_id,
			'name' => $category->name,
			'slug' => $category->slug,
		);
	}
	
	return $category_options;
}
