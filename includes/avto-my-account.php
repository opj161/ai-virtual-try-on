<?php
/**
 * WooCommerce My Account Integration
 *
 * Handles user-facing features in WooCommerce My Account:
 * - Try-On History tab with gallery
 * - Default user image upload/management
 * 
 * @package AI_Virtual_Try_On
 * @since 2.3.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register WooCommerce custom endpoint for Try-On History
 * 
 * @since 2.3.0
 */
function avto_register_tryon_history_endpoint() {
	add_rewrite_endpoint( 'try-on-history', EP_PAGES );
}
add_action( 'init', 'avto_register_tryon_history_endpoint' );

/**
 * Allow paged query var on WooCommerce My Account endpoints
 * 
 * WordPress doesn't automatically expose 'paged' on custom WooCommerce endpoints,
 * so we need to explicitly add it via the query_vars filter.
 * 
 * @since 2.3.0
 * 
 * @param array $query_vars Existing query vars
 * @return array Modified query vars with 'paged' added
 */
function avto_add_paged_query_var( $query_vars ) {
	$query_vars[] = 'paged';
	return $query_vars;
}
add_filter( 'query_vars', 'avto_add_paged_query_var' );

/**
 * Add Try-On History to WooCommerce My Account menu
 * 
 * @since 2.3.0
 * 
 * @param array $items Existing menu items
 * @return array Modified menu items
 */
function avto_add_my_account_menu_item( $items ) {
	// Insert before 'customer-logout' if it exists
	$logout = isset( $items['customer-logout'] ) ? $items['customer-logout'] : null;
	unset( $items['customer-logout'] );
	
	// Add our item
	$items['try-on-history'] = __( 'Virtual Try-On', 'avto' );
	
	// Re-add logout at the end
	if ( $logout ) {
		$items['customer-logout'] = $logout;
	}
	
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'avto_add_my_account_menu_item' );

/**
 * Render Try-On History content
 * 
 * Displays combined Virtual Try-On page with:
 * - Default image upload/management
 * - Gallery of user's past try-on sessions
 * - Product links, timestamps, delete functionality
 * 
 * @since 2.3.0
 */
function avto_render_tryon_history_content() {
	$user_id = get_current_user_id();
	
	// Get default image data
	$default_image_id  = get_user_meta( $user_id, '_avto_default_user_image_id', true );
	$default_image_url = $default_image_id ? wp_get_attachment_image_url( $default_image_id, 'thumbnail' ) : '';
	
	?>
	<div class="avto-tryon-wrapper">
		
		<!-- Default Image Settings Section -->
		<div class="avto-default-image-section" style="margin-bottom: 3rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px;">
			<h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.2rem;">
				<?php esc_html_e( 'Default Try-On Photo', 'avto' ); ?>
			</h3>
			
			<p style="margin-bottom: 1.5rem; color: #666;">
				<?php esc_html_e( 'Set a default photo to use for virtual try-ons. This will be automatically loaded when you use the try-on feature on product pages.', 'avto' ); ?>
			</p>
			
			<?php if ( $default_image_url ) : ?>
				<div id="avto-current-default-image" style="margin-bottom: 1.5rem;">
					<p style="margin-bottom: 0.5rem; font-weight: 600;">
						<?php esc_html_e( 'Current Default Image:', 'avto' ); ?>
					</p>
					<img src="<?php echo esc_url( $default_image_url ); ?>" 
						 alt="<?php esc_attr_e( 'Default try-on photo', 'avto' ); ?>" 
						 style="max-width: 150px; border-radius: 8px; border: 2px solid #ddd;">
					<br>
					<button type="button" 
							id="avto-remove-default-image-btn" 
							class="button" 
							style="margin-top: 0.75rem;">
						<?php esc_html_e( 'Remove Default Image', 'avto' ); ?>
					</button>
				</div>
			<?php endif; ?>
			
			<div class="avto-upload-section">
				<label for="avto_default_user_image_upload" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">
					<?php esc_html_e( $default_image_url ? 'Upload New Default Image' : 'Upload Default Image', 'avto' ); ?>
				</label>
				<input type="file" 
					   name="avto_default_user_image_upload" 
					   id="avto_default_user_image_upload" 
					   accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
					   style="width: 100%; max-width: 400px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
				<small style="display: block; margin-top: 0.5rem; color: #666;">
					<?php esc_html_e( 'Accepted formats: JPG, PNG, WebP, HEIC, HEIF (Max 5MB)', 'avto' ); ?>
				</small>
				<button type="button" 
						id="avto-upload-default-image-btn" 
						class="button button-primary" 
						style="margin-top: 1rem;" 
						disabled>
					<?php esc_html_e( 'Upload Image', 'avto' ); ?>
				</button>
				<span id="avto-upload-status" style="margin-left: 1rem; display: none;"></span>
			</div>
		</div>
		
		<!-- Try-On History Section -->
		<div class="avto-history-section">
			<h3 style="margin-bottom: 1rem; font-size: 1.2rem;">
				<?php esc_html_e( 'Your Try-On History', 'avto' ); ?>
			</h3>
			
			<?php
			// Get current page for pagination
			$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
			
			// Query user's try-on history
			$args = array(
				'post_type'      => 'avto_tryon_session',
				'author'         => $user_id,
				'posts_per_page' => 12,
				'paged'          => $paged,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			);
			
			$history_query = new WP_Query( $args );
			
			if ( $history_query->have_posts() ) : ?>
			
			<div class="avto-history-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
				
				<?php while ( $history_query->have_posts() ) : $history_query->the_post(); ?>
					<?php
					$session_id        = get_the_ID();
					$generated_img_id  = get_post_meta( $session_id, '_generated_image_id', true );
					$product_id        = get_post_meta( $session_id, '_product_id', true );
					$timestamp         = get_the_date( 'M j, Y' );
					
					$image_url = wp_get_attachment_image_url( $generated_img_id, 'medium' );
					$product   = $product_id ? wc_get_product( $product_id ) : null;
					?>
					
					<div class="avto-history-item" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #fff;">
						<?php if ( $image_url ) : ?>
							<div style="position: relative; padding-top: 100%; background: #f5f5f5;">
								<img src="<?php echo esc_url( $image_url ); ?>" 
									 alt="<?php echo esc_attr( get_the_title() ); ?>" 
									 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
							</div>
						<?php endif; ?>
						
						<div style="padding: 1rem;">
							<?php if ( $product && $product_id ) : ?>
								<h3 style="margin: 0 0 0.5rem; font-size: 0.95rem;">
									<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" style="color: #333; text-decoration: none;">
										<?php echo esc_html( $product->get_name() ); ?>
									</a>
								</h3>
							<?php else : ?>
								<h3 style="margin: 0 0 0.5rem; font-size: 0.95rem; color: #666;">
									<?php echo esc_html( get_the_title() ); ?>
								</h3>
							<?php endif; ?>
							
							<p style="margin: 0 0 0.75rem; font-size: 0.85rem; color: #666;">
								<?php echo esc_html( $timestamp ); ?>
							</p>
							
							<div style="display: flex; gap: 0.5rem;">
								<?php if ( $image_url ) : ?>
									<a href="<?php echo esc_url( $image_url ); ?>" 
									   target="_blank" 
									   class="button" 
									   style="flex: 1; text-align: center; font-size: 0.85rem; padding: 0.5rem;">
										<?php esc_html_e( 'View', 'avto' ); ?>
									</a>
								<?php endif; ?>
								
								<button type="button" 
										class="avto-delete-history-item button" 
										data-session-id="<?php echo esc_attr( $session_id ); ?>"
										style="flex: 1; font-size: 0.85rem; padding: 0.5rem; background: #dc3232; color: #fff; border-color: #dc3232;">
									<?php esc_html_e( 'Delete', 'avto' ); ?>
								</button>
							</div>
						</div>
					</div>
					
				<?php endwhile; ?>
				
			</div>
			
			<?php
			// Pagination
			if ( $history_query->max_num_pages > 1 ) :
				$current_page = max( 1, $paged );
				?>
				<nav class="woocommerce-pagination" style="margin-top: 2rem;">
					<?php
					echo paginate_links( array(
						'base'      => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $current_page,
						'total'     => $history_query->max_num_pages,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					) );
					?>
				</nav>
			<?php endif; ?>
			
		<?php else : ?>
			
			<p style="margin-top: 1.5rem; padding: 2rem; background: #f9f9f9; border-radius: 8px; text-align: center;">
				<?php esc_html_e( 'You haven\'t created any virtual try-ons yet. Visit a product page to get started!', 'avto' ); ?>
			</p>
			
		<?php endif; ?>
		
		<?php wp_reset_postdata(); ?>
		
		</div><!-- .avto-history-section -->
		
	</div><!-- .avto-tryon-wrapper -->
	
	<style>
		.avto-history-item:hover {
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
			transition: box-shadow 0.3s ease;
		}
		
		.avto-delete-history-item:hover {
			background: #a00 !important;
			border-color: #a00 !important;
		}
	</style>
	
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		
		// Enable upload button when file is selected
		$('#avto_default_user_image_upload').on('change', function() {
			const hasFile = this.files && this.files.length > 0;
			$('#avto-upload-default-image-btn').prop('disabled', !hasFile);
		});
		
		// Handle default image upload
		$('#avto-upload-default-image-btn').on('click', function() {
			const $button = $(this);
			const $status = $('#avto-upload-status');
			const $fileInput = $('#avto_default_user_image_upload');
			const file = $fileInput[0].files[0];
			
			if (!file) {
				alert('<?php echo esc_js( __( 'Please select an image file.', 'avto' ) ); ?>');
				return;
			}
			
			// Validate file size (5MB)
			if (file.size > 5 * 1024 * 1024) {
				alert('<?php echo esc_js( __( 'File size must be less than 5MB.', 'avto' ) ); ?>');
				return;
			}
			
			// Create FormData
			const formData = new FormData();
			formData.append('action', 'avto_save_default_image');
			formData.append('nonce', '<?php echo wp_create_nonce( 'avto-save-default-image-nonce' ); ?>');
			formData.append('default_image', file);
			
			// Update UI
			$button.prop('disabled', true).text('<?php echo esc_js( __( 'Uploading...', 'avto' ) ); ?>');
			$status.html('<span style="color: #666;"><?php echo esc_js( __( 'Uploading...', 'avto' ) ); ?></span>').show();
			
			// AJAX upload
			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						$status.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
						// Reload page after short delay
						setTimeout(function() {
							window.location.reload();
						}, 1000);
					} else {
						$status.html('<span style="color: #dc3232;">✗ ' + (response.data.message || '<?php echo esc_js( __( 'Upload failed.', 'avto' ) ); ?>') + '</span>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Upload Image', 'avto' ) ); ?>');
					}
				},
				error: function() {
					$status.html('<span style="color: #dc3232;">✗ <?php echo esc_js( __( 'An error occurred. Please try again.', 'avto' ) ); ?></span>');
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Upload Image', 'avto' ) ); ?>');
				}
			});
		});
		
		// Handle default image removal
		$('#avto-remove-default-image-btn').on('click', function() {
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to remove your default try-on photo?', 'avto' ) ); ?>')) {
				return;
			}
			
			const $button = $(this);
			$button.prop('disabled', true).text('<?php echo esc_js( __( 'Removing...', 'avto' ) ); ?>');
			
			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data: {
					action: 'avto_save_default_image',
					nonce: '<?php echo wp_create_nonce( 'avto-save-default-image-nonce' ); ?>',
					remove_image: '1'
				},
				success: function(response) {
					if (response.success) {
						// Reload page to show updated state
						window.location.reload();
					} else {
						alert(response.data.message || '<?php echo esc_js( __( 'Failed to remove image. Please try again.', 'avto' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Remove Default Image', 'avto' ) ); ?>');
					}
				},
				error: function() {
					alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'avto' ) ); ?>');
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Remove Default Image', 'avto' ) ); ?>');
				}
			});
		});
		
		// Existing delete history item handler
		$('.avto-delete-history-item').on('click', function(e) {
			e.preventDefault();
			
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this try-on from your history?', 'avto' ) ); ?>')) {
				return;
			}
			
			var $button = $(this);
			var sessionId = $button.data('session-id');
			
			$button.prop('disabled', true).text('<?php echo esc_js( __( 'Deleting...', 'avto' ) ); ?>');
			
			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data: {
					action: 'avto_delete_history_item',
					nonce: '<?php echo esc_js( wp_create_nonce( 'avto-delete-history-nonce' ) ); ?>',
					session_id: sessionId
				},
				success: function(response) {
					if (response.success) {
						// Remove the item from DOM with animation
						$button.closest('.avto-history-item').fadeOut(300, function() {
							$(this).remove();
							
							// Check if there are no more items
							if ($('.avto-history-item').length === 0) {
								location.reload();
							}
						});
					} else {
						alert(response.data.message || '<?php echo esc_js( __( 'Failed to delete. Please try again.', 'avto' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Delete', 'avto' ) ); ?>');
					}
				},
				error: function() {
					alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'avto' ) ); ?>');
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Delete', 'avto' ) ); ?>');
				}
			});
		});
		
	});
	</script>
	
	<?php
}
add_action( 'woocommerce_account_try-on-history_endpoint', 'avto_render_tryon_history_content' );

/**
 * Handle AJAX request to delete history item
 * 
 * @since 2.3.0
 */
function avto_handle_delete_history_item() {
	// Verify nonce
	check_ajax_referer( 'avto-delete-history-nonce', 'nonce' );
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array(
			'message' => __( 'You must be logged in to perform this action.', 'avto' ),
		) );
	}
	
	$user_id    = get_current_user_id();
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	
	if ( ! $session_id ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid session ID.', 'avto' ),
		) );
	}
	
	// Verify the post exists and belongs to the current user
	$post = get_post( $session_id );
	
	if ( ! $post || $post->post_type !== 'avto_tryon_session' ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid try-on session.', 'avto' ),
		) );
	}
	
	if ( absint( $post->post_author ) !== $user_id ) {
		wp_send_json_error( array(
			'message' => __( 'You do not have permission to delete this item.', 'avto' ),
		) );
	}
	
	// Delete the post
	$deleted = wp_delete_post( $session_id, true );
	
	if ( ! $deleted ) {
		wp_send_json_error( array(
			'message' => __( 'Failed to delete try-on session. Please try again.', 'avto' ),
		) );
	}
	
	wp_send_json_success( array(
		'message' => __( 'Try-on deleted successfully.', 'avto' ),
	) );
}
add_action( 'wp_ajax_avto_delete_history_item', 'avto_handle_delete_history_item' );


/**
 * Handle AJAX request to save/remove default user image
 * 
 * @since 2.3.0
 */
function avto_handle_save_default_image() {
	// Verify nonce
	check_ajax_referer( 'avto-save-default-image-nonce', 'nonce' );
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array(
			'message' => __( 'You must be logged in to perform this action.', 'avto' ),
		) );
	}
	
	$user_id = get_current_user_id();
	
	// Handle image removal
	if ( isset( $_POST['remove_image'] ) && $_POST['remove_image'] == '1' ) {
		delete_user_meta( $user_id, '_avto_default_user_image_id' );
		wp_send_json_success( array(
			'message' => __( 'Default image removed successfully.', 'avto' ),
		) );
	}
	
	// Handle image upload
	if ( ! isset( $_FILES['default_image'] ) || $_FILES['default_image']['error'] !== UPLOAD_ERR_OK ) {
		wp_send_json_error( array(
			'message' => __( 'File upload error. Please try again.', 'avto' ),
		) );
	}
	
	// Validate file type and size
	$allowed_types = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
	$file_type     = $_FILES['default_image']['type'];
	$file_size     = $_FILES['default_image']['size'];
	$max_size      = 5 * 1024 * 1024; // 5MB
	
	if ( ! in_array( $file_type, $allowed_types, true ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid file type. Please upload a JPG, PNG, WebP, HEIC, or HEIF image.', 'avto' ),
		) );
	}
	
	if ( $file_size > $max_size ) {
		wp_send_json_error( array(
			'message' => __( 'File size must be less than 5MB.', 'avto' ),
		) );
	}
	
	// Use WordPress media handling
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	
	$attachment_id = media_handle_upload( 'default_image', 0, array(
		'post_title'  => 'Default Try-On Photo - User ' . $user_id,
		'post_author' => $user_id,
	) );
	
	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error( array(
			'message' => sprintf( 
				/* translators: %s: error message */
				__( 'Failed to upload image: %s', 'avto' ), 
				$attachment_id->get_error_message() 
			),
		) );
	}
	
	// Save the attachment ID to user meta
	update_user_meta( $user_id, '_avto_default_user_image_id', $attachment_id );
	
	wp_send_json_success( array(
		'message'       => __( 'Default image uploaded successfully!', 'avto' ),
		'attachment_id' => $attachment_id,
	) );
}
add_action( 'wp_ajax_avto_save_default_image', 'avto_handle_save_default_image' );

