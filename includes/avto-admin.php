<?php
/**
 * Admin Settings Page (WordPress Best Practices Compliant)
 * 
 * @package AI_Virtual_Try_On
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin menu
 */
function avto_register_admin_menu() {
	add_options_page(
		__( 'AI Virtual Try-On Settings', 'avto' ),
		__( 'AI Virtual Try-On', 'avto' ),
		'manage_options',
		'avto-settings',
		'avto_render_settings_page'
	);
}
add_action( 'admin_menu', 'avto_register_admin_menu' );

/**
 * Register settings - CORRECT WordPress way
 */
function avto_register_settings() {
	// Register all settings with proper sanitization callbacks
	register_setting( 'avto_settings_group', 'avto_ai_prompt', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_textarea_field',
		'default'           => avto_get_default_prompt(),
	) );
	
	register_setting( 'avto_settings_group', 'avto_aspect_ratio', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '1:1',
	) );
	
	register_setting( 'avto_settings_group', 'avto_max_file_size', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 5,
	) );
	
	register_setting( 'avto_settings_group', 'avto_clothing_items', array(
		'type'              => 'array',
		'sanitize_callback' => 'avto_sanitize_clothing_items',
		'default'           => avto_get_default_clothing_items(),
	) );
	
	register_setting( 'avto_settings_group', 'avto_upload_button_text', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => __( 'Click to upload or drag and drop', 'avto' ),
	) );
	
	register_setting( 'avto_settings_group', 'avto_generate_button_text', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => __( 'Generate Virtual Try-On', 'avto' ),
	) );
	
	register_setting( 'avto_settings_group', 'avto_show_download_button', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => true,
	) );
	
	register_setting( 'avto_settings_group', 'avto_enable_caching', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	) );
	
	register_setting( 'avto_settings_group', 'avto_cache_duration', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 86400,
	) );
	
	// Rate limiting settings (per-user)
	register_setting( 'avto_settings_group', 'avto_rate_limit_requests', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 10,
	) );
	
	register_setting( 'avto_settings_group', 'avto_rate_limit_window', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 60,
	) );
	
	// Global rate limiting settings (site-wide)
	register_setting( 'avto_settings_group', 'avto_enable_global_rate_limit', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	) );
	
	register_setting( 'avto_settings_group', 'avto_global_rate_limit_requests', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 100,
	) );
	
	register_setting( 'avto_settings_group', 'avto_global_rate_limit_window', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 3600,
	) );
	
	register_setting( 'avto_settings_group', 'avto_debug_mode', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	) );
}
add_action( 'admin_init', 'avto_register_settings' );

/**
 * Register WooCommerce integration settings
 */
function avto_register_wc_settings() {
	// Master switch for WooCommerce integration
	register_setting( 'avto_settings_group', 'avto_wc_integration_enabled', array(
		'type'              => 'boolean',
		'default'           => false,
		'show_in_rest'      => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	
	// Display hook location
	register_setting( 'avto_settings_group', 'avto_wc_display_hook', array(
		'type'              => 'string',
		'default'           => 'woocommerce_single_product_summary',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
	) );
	
	// Hook priority
	register_setting( 'avto_settings_group', 'avto_wc_hook_priority', array(
		'type'              => 'integer',
		'default'           => 35,
		'show_in_rest'      => true,
		'sanitize_callback' => 'absint',
	) );
	
	// Target categories (array of integers)
	register_setting( 'avto_settings_group', 'avto_wc_target_categories', array(
		'type'              => 'array',
		'default'           => array(),
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		),
		'sanitize_callback' => 'avto_sanitize_wc_categories',
	) );
	
	// Button text
	register_setting( 'avto_settings_group', 'avto_wc_button_text', array(
		'type'              => 'string',
		'default'           => __( 'Virtual Try-On', 'avto' ),
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
	) );
}
add_action( 'admin_init', 'avto_register_wc_settings' );

/**
 * Sanitize WooCommerce category IDs
 */
function avto_sanitize_wc_categories( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	return array_map( 'absint', $value );
}

/**
 * Get available WooCommerce hook options
 */
function avto_get_wc_hook_options() {
	return array(
		'woocommerce_single_product_summary'     => __( 'Product Summary (After Short Description)', 'avto' ),
		'woocommerce_before_add_to_cart_form'    => __( 'Before Add to Cart Form', 'avto' ),
		'woocommerce_after_add_to_cart_form'     => __( 'After Add to Cart Form', 'avto' ),
		'woocommerce_product_meta_start'         => __( 'Product Meta Start', 'avto' ),
		'woocommerce_product_meta_end'           => __( 'Product Meta End', 'avto' ),
		'woocommerce_after_single_product_summary' => __( 'After Product Summary', 'avto' ),
	);
}

/**
 * Get default AI prompt
 */
function avto_get_default_prompt() {
	return 'Create a virtual try-on using the person from image 1 wearing the clothing from image 2, replacing the current clothing item. Remove any existing clothing that conflicts with the new item. Ensure realistic fit, proper lighting and shadows, natural fabric draping, and show the complete garment uncropped. The clothing must fit perfectly and be the visual focus of the image.';
}

/**
 * Get default clothing items
 */
function avto_get_default_clothing_items() {
	$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
	return array(
		array(
			'id'    => 'shirt-1',
			'name'  => 'Classic White Shirt',
			'image' => $plugin_url . 'assets/images/shirt-1.jpg',
		),
		array(
			'id'    => 'dress-1',
			'name'  => 'Summer Dress',
			'image' => $plugin_url . 'assets/images/dress-1.jpg',
		),
	);
}

/**
 * Sanitize clothing items array
 */
function avto_sanitize_clothing_items( $items ) {
	if ( ! is_array( $items ) ) {
		return avto_get_default_clothing_items();
	}
	
	$sanitized = array();
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		
		$sanitized[] = array(
			'id'    => isset( $item['id'] ) ? sanitize_key( $item['id'] ) : '',
			'name'  => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
			'image' => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
		);
	}
	
	return $sanitized;
}

/**
 * Render settings page - CORRECT WordPress way
 * Form posts to options.php, WordPress handles everything
 */
function avto_render_settings_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'avto' ) );
	}
	
	// Get current settings
	$ai_prompt             = get_option( 'avto_ai_prompt', avto_get_default_prompt() );
	$aspect_ratio          = get_option( 'avto_aspect_ratio', '1:1' );
	$max_file_size         = get_option( 'avto_max_file_size', 5 );
	$clothing_items        = get_option( 'avto_clothing_items', avto_get_default_clothing_items() );
	$upload_button_text    = get_option( 'avto_upload_button_text', __( 'Click to upload or drag and drop', 'avto' ) );
	$generate_button_text  = get_option( 'avto_generate_button_text', __( 'Generate Virtual Try-On', 'avto' ) );
	$show_download_button  = get_option( 'avto_show_download_button', true );
	$enable_caching        = get_option( 'avto_enable_caching', false );
	$cache_duration        = get_option( 'avto_cache_duration', 86400 );
	$debug_mode            = get_option( 'avto_debug_mode', false );
	
	// WooCommerce settings
	$wc_integration_enabled = get_option( 'avto_wc_integration_enabled', false );
	$wc_display_hook        = get_option( 'avto_wc_display_hook', 'woocommerce_single_product_summary' );
	$wc_hook_priority       = get_option( 'avto_wc_hook_priority', 35 );
	$wc_target_categories   = get_option( 'avto_wc_target_categories', array() );
	$wc_button_text         = get_option( 'avto_wc_button_text', __( 'Virtual Try-On', 'avto' ) );
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<?php settings_errors(); ?>
		
		<!-- CORRECT: Form posts to options.php, not to itself -->
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting "avto_settings_group"
			settings_fields( 'avto_settings_group' );
			?>
			
			<!-- Tabbed Navigation -->
			<h2 class="nav-tab-wrapper">
				<a href="#ai-config" class="nav-tab nav-tab-active"><?php esc_html_e( 'AI Configuration', 'avto' ); ?></a>
				<a href="#clothing-items" class="nav-tab"><?php esc_html_e( 'Clothing Items', 'avto' ); ?></a>
				<a href="#ui-settings" class="nav-tab"><?php esc_html_e( 'UI Settings', 'avto' ); ?></a>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="#woocommerce" class="nav-tab"><?php esc_html_e( 'WooCommerce', 'avto' ); ?></a>
				<?php endif; ?>
				<a href="#advanced" class="nav-tab"><?php esc_html_e( 'Advanced', 'avto' ); ?></a>
			</h2>
			
			<!-- Tab 1: AI Configuration -->
			<div id="ai-config" class="avto-tab-content">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="avto_ai_prompt"><?php esc_html_e( 'AI Prompt', 'avto' ); ?></label>
						</th>
						<td>
							<textarea 
								name="avto_ai_prompt" 
								id="avto_ai_prompt" 
								rows="5" 
								cols="50" 
								class="large-text"><?php echo esc_textarea( $ai_prompt ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'The prompt sent to Gemini API for generating virtual try-on images.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_aspect_ratio"><?php esc_html_e( 'Aspect Ratio', 'avto' ); ?></label>
						</th>
						<td>
							<select name="avto_aspect_ratio" id="avto_aspect_ratio">
								<option value="1:1" <?php selected( $aspect_ratio, '1:1' ); ?>>1:1</option>
								<option value="2:3" <?php selected( $aspect_ratio, '2:3' ); ?>>2:3</option>
								<option value="3:2" <?php selected( $aspect_ratio, '3:2' ); ?>>3:2</option>
								<option value="3:4" <?php selected( $aspect_ratio, '3:4' ); ?>>3:4</option>
								<option value="4:3" <?php selected( $aspect_ratio, '4:3' ); ?>>4:3</option>
								<option value="4:5" <?php selected( $aspect_ratio, '4:5' ); ?>>4:5</option>
								<option value="5:4" <?php selected( $aspect_ratio, '5:4' ); ?>>5:4</option>
								<option value="9:16" <?php selected( $aspect_ratio, '9:16' ); ?>>9:16</option>
								<option value="16:9" <?php selected( $aspect_ratio, '16:9' ); ?>>16:9</option>
								<option value="21:9" <?php selected( $aspect_ratio, '21:9' ); ?>>21:9</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Output image aspect ratio (default: 1:1).', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_max_file_size"><?php esc_html_e( 'Max File Size (MB)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_max_file_size" 
								id="avto_max_file_size" 
								value="<?php echo esc_attr( $max_file_size ); ?>" 
								min="1" 
								max="20" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Maximum file size for user uploads (1-20 MB).', 'avto' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- Tab 2: Clothing Items -->
			<div id="clothing-items" class="avto-tab-content" style="display:none;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Clothing Items', 'avto' ); ?>
						</th>
						<td>
							<div id="avto-clothing-items-list">
								<?php foreach ( $clothing_items as $index => $item ) : ?>
									<div class="avto-clothing-item" data-index="<?php echo esc_attr( $index ); ?>">
										<input 
											type="text" 
											name="avto_clothing_items[<?php echo esc_attr( $index ); ?>][id]" 
											placeholder="<?php esc_attr_e( 'ID (e.g., shirt-1)', 'avto' ); ?>" 
											value="<?php echo esc_attr( $item['id'] ); ?>" 
											class="regular-text">
										<input 
											type="text" 
											name="avto_clothing_items[<?php echo esc_attr( $index ); ?>][name]" 
											placeholder="<?php esc_attr_e( 'Name (e.g., Classic White Shirt)', 'avto' ); ?>" 
											value="<?php echo esc_attr( $item['name'] ); ?>" 
											class="regular-text">
										<input 
											type="hidden" 
											name="avto_clothing_items[<?php echo esc_attr( $index ); ?>][image]" 
											value="<?php echo esc_url( $item['image'] ); ?>" 
											class="avto-image-url">
										<button type="button" class="button avto-upload-image">
											<?php esc_html_e( 'Upload Image', 'avto' ); ?>
										</button>
										<button type="button" class="button avto-remove-item">
											<?php esc_html_e( 'Remove', 'avto' ); ?>
										</button>
										<?php if ( ! empty( $item['image'] ) ) : ?>
											<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" style="max-width:100px;display:block;margin-top:5px;">
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" id="avto-add-item" class="button">
								<?php esc_html_e( 'Add New Item', 'avto' ); ?>
							</button>
							<p class="description">
								<?php esc_html_e( 'Manage clothing items available for virtual try-on.', 'avto' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- Tab 3: UI Settings -->
			<div id="ui-settings" class="avto-tab-content" style="display:none;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="avto_upload_button_text"><?php esc_html_e( 'Upload Button Text', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="text" 
								name="avto_upload_button_text" 
								id="avto_upload_button_text" 
								value="<?php echo esc_attr( $upload_button_text ); ?>" 
								class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Text displayed on the photo upload area.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_generate_button_text"><?php esc_html_e( 'Generate Button Text', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="text" 
								name="avto_generate_button_text" 
								id="avto_generate_button_text" 
								value="<?php echo esc_attr( $generate_button_text ); ?>" 
								class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Text displayed on the generate button.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Show Download Button', 'avto' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									name="avto_show_download_button" 
									id="avto_show_download_button" 
									value="1" 
									<?php checked( $show_download_button, true ); ?>>
								<?php esc_html_e( 'Display a download button after generating the image', 'avto' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- Tab 4: WooCommerce Integration -->
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<div id="woocommerce" class="avto-tab-content" style="display:none;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable WooCommerce Integration', 'avto' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									name="avto_wc_integration_enabled" 
									id="avto_wc_integration_enabled" 
									value="1" 
									<?php checked( $wc_integration_enabled, true ); ?>>
								<?php esc_html_e( 'Enable virtual try-on on WooCommerce product pages', 'avto' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, a "Virtual Try-On" button will appear on product pages in selected categories.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_wc_display_hook"><?php esc_html_e( 'Button Display Location', 'avto' ); ?></label>
						</th>
						<td>
							<select name="avto_wc_display_hook" id="avto_wc_display_hook">
								<?php
								$hook_options = avto_get_wc_hook_options();
								foreach ( $hook_options as $hook_value => $hook_label ) :
									?>
									<option value="<?php echo esc_attr( $hook_value ); ?>" <?php selected( $wc_display_hook, $hook_value ); ?>>
										<?php echo esc_html( $hook_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Choose where the "Virtual Try-On" button appears on product pages.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_wc_hook_priority"><?php esc_html_e( 'Display Priority', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_wc_hook_priority" 
								id="avto_wc_hook_priority" 
								value="<?php echo esc_attr( $wc_hook_priority ); ?>" 
								min="1" 
								max="99" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Lower numbers display earlier. Default: 35', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_wc_target_categories"><?php esc_html_e( 'Target Product Categories', 'avto' ); ?></label>
						</th>
						<td>
							<?php
							// Get all WooCommerce product categories
							$product_categories = get_terms( array(
								'taxonomy'   => 'product_cat',
								'hide_empty' => false,
							) );
							
							if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) :
								?>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Select Categories', 'avto' ); ?></legend>
									<?php foreach ( $product_categories as $category ) : ?>
										<label style="display: block; margin-bottom: 5px;">
											<input 
												type="checkbox" 
												name="avto_wc_target_categories[]" 
												value="<?php echo esc_attr( $category->term_id ); ?>"
												<?php checked( in_array( $category->term_id, $wc_target_categories, true ) ); ?>>
											<?php echo esc_html( $category->name ); ?>
											<span style="color: #666;">(<?php echo absint( $category->count ); ?> products)</span>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description">
									<?php esc_html_e( 'Select which product categories will show the Virtual Try-On button. Leave empty to show on all products.', 'avto' ); ?>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'No product categories found. Create some categories in WooCommerce first.', 'avto' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_wc_button_text"><?php esc_html_e( 'Button Text', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="text" 
								name="avto_wc_button_text" 
								id="avto_wc_button_text" 
								value="<?php echo esc_attr( $wc_button_text ); ?>" 
								class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Customize the text displayed on the Virtual Try-On button.', 'avto' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			<?php endif; ?>
			
			<!-- Tab 5: Advanced -->
			<div id="advanced" class="avto-tab-content" style="display:none;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Debug Mode', 'avto' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									name="avto_debug_mode" 
									id="avto_debug_mode" 
									value="1" 
									<?php checked( $debug_mode, true ); ?>>
								<?php esc_html_e( 'Enable detailed error messages from Gemini API', 'avto' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, shows specific API error messages to help troubleshoot issues. Disable in production for security.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Caching', 'avto' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									name="avto_enable_caching" 
									id="avto_enable_caching" 
									value="1" 
									<?php checked( $enable_caching, true ); ?>>
								<?php esc_html_e( 'Cache API responses to reduce costs', 'avto' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, identical requests will use cached results.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_cache_duration"><?php esc_html_e( 'Cache Duration (seconds)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_cache_duration" 
								id="avto_cache_duration" 
								value="<?php echo esc_attr( $cache_duration ); ?>" 
								min="3600" 
								max="604800" 
								class="regular-text">
							<p class="description">
								<?php esc_html_e( 'How long to cache results (3600 = 1 hour, 86400 = 1 day, 604800 = 1 week).', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" colspan="2">
							<h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
								<?php esc_html_e( 'Per-User Rate Limiting', 'avto' ); ?>
							</h3>
							<p class="description">
								<?php esc_html_e( 'Limit how many requests each individual user or IP address can make.', 'avto' ); ?>
							</p>
						</th>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_rate_limit_requests"><?php esc_html_e( 'Per-User Limit (requests)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_rate_limit_requests" 
								id="avto_rate_limit_requests" 
								value="<?php echo esc_attr( get_option( 'avto_rate_limit_requests', 10 ) ); ?>" 
								min="1" 
								max="100" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Maximum requests per user/IP in the time window below.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_rate_limit_window"><?php esc_html_e( 'Per-User Time Window (seconds)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_rate_limit_window" 
								id="avto_rate_limit_window" 
								value="<?php echo esc_attr( get_option( 'avto_rate_limit_window', 60 ) ); ?>" 
								min="10" 
								max="3600" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Time window for per-user rate limiting (60 = 1 minute, 3600 = 1 hour).', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" colspan="2">
							<h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
								<?php esc_html_e( 'Global (Site-Wide) Rate Limiting', 'avto' ); ?>
							</h3>
							<p class="description">
								<?php esc_html_e( 'Limit total requests across ALL users on your entire site. Useful for managing API costs.', 'avto' ); ?>
							</p>
						</th>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Global Limit', 'avto' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									name="avto_enable_global_rate_limit" 
									id="avto_enable_global_rate_limit" 
									value="1" 
									<?php checked( get_option( 'avto_enable_global_rate_limit', false ), true ); ?>>
								<?php esc_html_e( 'Enable site-wide rate limiting (applies to all users combined)', 'avto' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, limits total requests from all users. Works in addition to per-user limits.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_global_rate_limit_requests"><?php esc_html_e( 'Global Limit (requests)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_global_rate_limit_requests" 
								id="avto_global_rate_limit_requests" 
								value="<?php echo esc_attr( get_option( 'avto_global_rate_limit_requests', 100 ) ); ?>" 
								min="10" 
								max="10000" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Maximum total requests from all users combined in the time window below.', 'avto' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avto_global_rate_limit_window"><?php esc_html_e( 'Global Time Window (seconds)', 'avto' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								name="avto_global_rate_limit_window" 
								id="avto_global_rate_limit_window" 
								value="<?php echo esc_attr( get_option( 'avto_global_rate_limit_window', 3600 ) ); ?>" 
								min="60" 
								max="86400" 
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Time window for global rate limiting (3600 = 1 hour, 86400 = 1 day).', 'avto' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
			<?php submit_button( __( 'Save Settings', 'avto' ) ); ?>
		</form>
	</div>
	
	<!-- JavaScript for tabs and media uploader -->
	<script>
	jQuery(document).ready(function($) {
		// Tab switching
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.avto-tab-content').hide();
			$($(this).attr('href')).show();
		});
		
		// Media uploader
		var mediaUploader;
		$(document).on('click', '.avto-upload-image', function(e) {
			e.preventDefault();
			var button = $(this);
			var inputField = button.siblings('.avto-image-url');
			
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			
			mediaUploader = wp.media({
				title: '<?php esc_html_e( 'Select Clothing Image', 'avto' ); ?>',
				button: {
					text: '<?php esc_html_e( 'Use this image', 'avto' ); ?>'
				},
				multiple: false
			});
			
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				inputField.val(attachment.url);
				
				// Remove existing image preview
				button.parent().find('img').remove();
				
				// Add new image preview
				button.after('<img src="' + attachment.url + '" style="max-width:100px;display:block;margin-top:5px;">');
			});
			
			mediaUploader.open();
		});
		
		// Add new clothing item
		$('#avto-add-item').on('click', function() {
			var index = $('.avto-clothing-item').length;
			var newItem = '<div class="avto-clothing-item" data-index="' + index + '">' +
				'<input type="text" name="avto_clothing_items[' + index + '][id]" placeholder="<?php esc_attr_e( 'ID (e.g., shirt-1)', 'avto' ); ?>" class="regular-text">' +
				'<input type="text" name="avto_clothing_items[' + index + '][name]" placeholder="<?php esc_attr_e( 'Name (e.g., Classic White Shirt)', 'avto' ); ?>" class="regular-text">' +
				'<input type="hidden" name="avto_clothing_items[' + index + '][image]" class="avto-image-url">' +
				'<button type="button" class="button avto-upload-image"><?php esc_html_e( 'Upload Image', 'avto' ); ?></button>' +
				'<button type="button" class="button avto-remove-item"><?php esc_html_e( 'Remove', 'avto' ); ?></button>' +
				'</div>';
			$('#avto-clothing-items-list').append(newItem);
		});
		
		// Remove clothing item
		$(document).on('click', '.avto-remove-item', function() {
			$(this).closest('.avto-clothing-item').remove();
		});
	});
	</script>
	
	<style>
	.avto-clothing-item {
		padding: 10px;
		border: 1px solid #ddd;
		margin-bottom: 10px;
		background: #f9f9f9;
	}
	.avto-clothing-item input[type="text"],
	.avto-clothing-item input[type="hidden"] {
		margin-bottom: 5px;
	}
	</style>
	<?php
}

/**
 * Enqueue admin scripts
 */
function avto_enqueue_admin_scripts( $hook ) {
	// Only load on our settings page
	if ( 'settings_page_avto-settings' !== $hook ) {
		return;
	}
	
	// Enqueue WordPress media library
	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
}
add_action( 'admin_enqueue_scripts', 'avto_enqueue_admin_scripts' );

/**
 * Add Rate Limit Status Dashboard Widget
 * 
 * Shows current rate limit usage for administrators.
 * 
 * @since 2.4.0
 */
function avto_add_dashboard_widget() {
	// Only show to administrators
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Only show if global rate limiting is enabled
	if ( ! get_option( 'avto_enable_global_rate_limit', false ) ) {
		return;
	}
	
	wp_add_dashboard_widget(
		'avto_rate_limit_status',
		__( 'AI Virtual Try-On - Rate Limit Status', 'avto' ),
		'avto_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'avto_add_dashboard_widget' );

/**
 * Render the dashboard widget content
 */
function avto_render_dashboard_widget() {
	$global_enabled = get_option( 'avto_enable_global_rate_limit', false );
	
	if ( ! $global_enabled ) {
		echo '<p>' . esc_html__( 'Global rate limiting is disabled.', 'avto' ) . '</p>';
		return;
	}
	
	// Get settings
	$global_max = (int) get_option( 'avto_global_rate_limit_requests', 100 );
	$global_window = (int) get_option( 'avto_global_rate_limit_window', 3600 );
	$per_user_max = (int) get_option( 'avto_rate_limit_requests', 10 );
	$per_user_window = (int) get_option( 'avto_rate_limit_window', 60 );
	
	// Get current usage
	$global_count = (int) get_transient( 'avto_global_rate_limit' );
	$global_count = ( false === $global_count ) ? 0 : $global_count;
	
	// Calculate percentage
	$percentage = ( $global_max > 0 ) ? ( $global_count / $global_max ) * 100 : 0;
	
	// Determine status color
	if ( $percentage >= 90 ) {
		$status_color = '#dc3232'; // Red
		$status_text = __( 'Critical', 'avto' );
	} elseif ( $percentage >= 70 ) {
		$status_color = '#f56e28'; // Orange
		$status_text = __( 'Warning', 'avto' );
	} elseif ( $percentage >= 50 ) {
		$status_color = '#ffb900'; // Yellow
		$status_text = __( 'Moderate', 'avto' );
	} else {
		$status_color = '#46b450'; // Green
		$status_text = __( 'Healthy', 'avto' );
	}
	
	?>
	<div style="margin: 10px 0;">
		<h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'Global Usage', 'avto' ); ?></h4>
		<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
			<span><strong><?php echo absint( $global_count ); ?></strong> / <?php echo absint( $global_max ); ?> requests</span>
			<span style="color: <?php echo esc_attr( $status_color ); ?>; font-weight: bold;">
				<?php echo esc_html( $status_text ); ?>
			</span>
		</div>
		<div style="background: #f0f0f1; height: 20px; border-radius: 3px; overflow: hidden;">
			<div style="background: <?php echo esc_attr( $status_color ); ?>; width: <?php echo esc_attr( $percentage ); ?>%; height: 100%; transition: width 0.3s;"></div>
		</div>
		<p style="margin: 10px 0 0 0; font-size: 12px; color: #646970;">
			<?php
			printf(
				/* translators: %s: time window in human readable format */
				esc_html__( 'Time window: %s', 'avto' ),
				esc_html( avto_format_seconds( $global_window ) )
			);
			?>
		</p>
	</div>
	
	<div style="margin: 20px 0 10px 0; padding-top: 15px; border-top: 1px solid #f0f0f1;">
		<h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'Per-User Limits', 'avto' ); ?></h4>
		<p style="margin: 5px 0; font-size: 13px;">
			<strong><?php echo absint( $per_user_max ); ?></strong> requests per 
			<strong><?php echo esc_html( avto_format_seconds( $per_user_window ) ); ?></strong>
		</p>
	</div>
	
	<p style="margin: 15px 0 0 0;">
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=avto-settings#advanced' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Adjust Settings', 'avto' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Format seconds into human-readable time
 * 
 * @param int $seconds Number of seconds
 * @return string Human-readable time
 */
function avto_format_seconds( $seconds ) {
	if ( $seconds >= 86400 ) {
		$days = floor( $seconds / 86400 );
		return sprintf( _n( '%d day', '%d days', $days, 'avto' ), $days );
	} elseif ( $seconds >= 3600 ) {
		$hours = floor( $seconds / 3600 );
		return sprintf( _n( '%d hour', '%d hours', $hours, 'avto' ), $hours );
	} elseif ( $seconds >= 60 ) {
		$minutes = floor( $seconds / 60 );
		return sprintf( _n( '%d minute', '%d minutes', $minutes, 'avto' ), $minutes );
	} else {
		return sprintf( _n( '%d second', '%d seconds', $seconds, 'avto' ), $seconds );
	}
}
