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
 * Displays notification badge if user has new results.
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
	
	// Get notification count for current user
	$new_count = 0;
	if ( is_user_logged_in() ) {
		$new_count = (int) get_user_meta( get_current_user_id(), '_avto_new_results_count', true );
	}
	
	// Build menu item with notification badge if needed
	$menu_label = __( 'Virtual Try-On', 'avto' );
	if ( $new_count > 0 ) {
		$menu_label .= ' <span class="avto-notification-dot">' . esc_html( $new_count ) . '</span>';
	}
	
	// Add our item
	$items['try-on-history'] = $menu_label;
	
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
	
	// Clear notification flag when user visits this page
	delete_user_meta( $user_id, '_avto_new_results_count' );
	
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
			
			// Query user's try-on history - include all statuses
			$args = array(
				'post_type'      => 'avto_tryon_session',
				'author'         => $user_id,
				'posts_per_page' => 12,
				'paged'          => $paged,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => array( 'publish', 'avto-pending', 'avto-processing', 'avto-failed' ),
			);
			
			$history_query = new WP_Query( $args );
			
			if ( $history_query->have_posts() ) : ?>
			
			<div class="avto-history-grid">
				
				<?php while ( $history_query->have_posts() ) : $history_query->the_post(); ?>
					<?php
					$session_id        = get_the_ID();
					$session_status    = get_post_status( $session_id );
					$generated_img_id  = get_post_meta( $session_id, '_generated_image_id', true );
					$product_id        = get_post_meta( $session_id, '_product_id', true );
					$timestamp         = get_the_date( 'M j, Y' );
					$timestamp_full    = get_the_date( 'Y-m-d' );
					$error_message     = get_post_meta( $session_id, '_avto_error_message', true );
					
					// Get both full-size and thumbnail URLs
					$image_url_full = wp_get_attachment_url( $generated_img_id );
					$image_url_thumb = wp_get_attachment_image_url( $generated_img_id, 'medium' );
					$product   = $product_id ? wc_get_product( $product_id ) : null;
					
					// Create safe filename for download
					$download_filename = 'tryon-';
					if ( $product ) {
						$download_filename .= sanitize_title( $product->get_name() ) . '-';
					}
					$download_filename .= $timestamp_full . '.jpg';
					
					// Determine status display
					$status_class = '';
					$status_label = '';
					$is_pending_or_processing = false;
					
					switch ( $session_status ) {
						case 'avto-pending':
							$status_class = 'avto-status-pending';
							$status_label = __( 'Queued', 'avto' );
							$is_pending_or_processing = true;
							break;
						case 'avto-processing':
							$status_class = 'avto-status-processing';
							$status_label = __( 'Processing...', 'avto' );
							$is_pending_or_processing = true;
							break;
						case 'avto-failed':
							$status_class = 'avto-status-failed';
							$status_label = __( 'Failed', 'avto' );
							break;
					}
					?>
					
					<div class="avto-history-item <?php echo esc_attr( $status_class ); ?>">
						
						<?php if ( $is_pending_or_processing ) : ?>
							<!-- Processing/Pending Overlay -->
							<div style="position: relative; padding-top: 100%; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
								<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
									<div class="avto-spinner" style="width: 48px; height: 48px; border: 4px solid #e1cccb; border-top-color: #7d5a68; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
									<p style="margin: 0; color: #7d5a68; font-weight: 600; font-size: 0.9rem;">
										<?php echo esc_html( $status_label ); ?>
									</p>
								</div>
							</div>
						<?php elseif ( $session_status === 'avto-failed' ) : ?>
							<!-- Failed State -->
							<div style="position: relative; padding-top: 100%; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
								<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; padding: 1rem;">
									<svg style="color: #dc3232; margin-bottom: 1rem;" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<circle cx="12" cy="12" r="10"></circle>
										<line x1="12" y1="8" x2="12" y2="12"></line>
										<line x1="12" y1="16" x2="12.01" y2="16"></line>
									</svg>
									<p style="margin: 0; color: #dc3232; font-weight: 600; font-size: 0.9rem;">
										<?php echo esc_html( $status_label ); ?>
									</p>
								</div>
							</div>
						<?php elseif ( $image_url_thumb ) : ?>
							<!-- Success State - Show Image -->
							<div class="avto-history-image-wrapper" 
								 style="position: relative; padding-top: 100%; background: #f5f5f5; cursor: pointer;"
								 data-lightbox-trigger
								 data-image-full="<?php echo esc_url( $image_url_full ); ?>"
								 data-image-thumb="<?php echo esc_url( $image_url_thumb ); ?>"
								 data-session-id="<?php echo esc_attr( $session_id ); ?>"
								 data-product-name="<?php echo esc_attr( $product ? $product->get_name() : get_the_title() ); ?>"
								 data-timestamp="<?php echo esc_attr( $timestamp ); ?>">
								<img src="<?php echo esc_url( $image_url_thumb ); ?>" 
									 alt="<?php echo esc_attr( get_the_title() ); ?>" 
									 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
								<div class="avto-image-hover-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.3s ease; display: flex; align-items: center; justify-content: center;">
									<svg style="opacity: 0; transition: opacity 0.3s ease; color: white; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<circle cx="11" cy="11" r="8"></circle>
										<path d="m21 21-4.35-4.35"></path>
										<line x1="11" y1="8" x2="11" y2="14"></line>
										<line x1="8" y1="11" x2="14" y2="11"></line>
									</svg>
								</div>
							</div>
						<?php endif; ?>
						
						<div class="avto-history-card-content">
							<div class="avto-history-card-info">
								<?php if ( $product && $product_id ) : ?>
									<h3 class="avto-history-card-title">
										<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
											<?php echo esc_html( $product->get_name() ); ?>
										</a>
									</h3>
								<?php else : ?>
									<h3 class="avto-history-card-title avto-no-product">
										<?php echo esc_html( get_the_title() ); ?>
									</h3>
								<?php endif; ?>
								
								<p class="avto-history-card-date">
									<?php echo esc_html( $timestamp ); ?>
								</p>
								
								<?php if ( $session_status === 'avto-failed' && $error_message ) : ?>
									<p class="avto-history-error-message">
										<?php echo esc_html( $error_message ); ?>
									</p>
								<?php endif; ?>
							</div>
							
							<div class="avto-history-actions">
								<?php if ( $image_url_full && $session_status === 'publish' ) : ?>
									<div class="avto-history-btn-row">
										<button type="button" 
												class="button avto-view-btn" 
												data-lightbox-open
												data-session-id="<?php echo esc_attr( $session_id ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
												<circle cx="12" cy="12" r="3"></circle>
											</svg>
											<span><?php esc_html_e( 'View', 'avto' ); ?></span>
										</button>
										<a href="<?php echo esc_url( $image_url_full ); ?>" 
										   download="<?php echo esc_attr( $download_filename ); ?>"
										   class="button avto-download-btn">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
												<polyline points="7 10 12 15 17 10"></polyline>
												<line x1="12" y1="15" x2="12" y2="3"></line>
											</svg>
											<span><?php esc_html_e( 'Download', 'avto' ); ?></span>
										</a>
									</div>
								<?php endif; ?>
								
								<?php if ( ! $is_pending_or_processing ) : ?>
									<button type="button" 
											class="avto-delete-history-item button" 
											data-session-id="<?php echo esc_attr( $session_id ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<polyline points="3 6 5 6 21 6"></polyline>
											<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
										</svg>
										<span><?php esc_html_e( 'Delete', 'avto' ); ?></span>
									</button>
								<?php else : ?>
									<button type="button" 
											class="button avto-processing-btn" 
											disabled>
										<?php echo esc_html( $status_label ); ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
					</div><!-- .avto-history-item -->
					
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
	
	<!-- Lightbox Modal -->
	<div id="avto-history-lightbox" class="avto-lightbox" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 999999; background: rgba(0,0,0,0.95);">
		<div class="avto-lightbox-container" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: relative;">
			
			<!-- Close Button -->
			<button type="button" class="avto-lightbox-close" aria-label="<?php esc_attr_e( 'Close', 'avto' ); ?>" style="position: absolute; top: 20px; right: 20px; z-index: 10; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="18" y1="6" x2="6" y2="18"></line>
					<line x1="6" y1="6" x2="18" y2="18"></line>
				</svg>
			</button>
			
			<!-- Navigation Arrows -->
			<button type="button" class="avto-lightbox-prev" aria-label="<?php esc_attr_e( 'Previous', 'avto' ); ?>" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="15 18 9 12 15 6"></polyline>
				</svg>
			</button>
			
			<button type="button" class="avto-lightbox-next" aria-label="<?php esc_attr_e( 'Next', 'avto' ); ?>" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="9 18 15 12 9 6"></polyline>
				</svg>
			</button>
			
			<!-- Image Container -->
			<div class="avto-lightbox-image-wrapper" style="max-width: 90%; max-height: 90%; position: relative; display: flex; align-items: center; justify-content: center;">
				<div class="avto-lightbox-loading" style="position: absolute; display: none;">
					<div class="avto-spinner" style="width: 48px; height: 48px; border: 4px solid rgba(255,255,255,0.2); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
				</div>
				<img src="" alt="" class="avto-lightbox-image" style="max-width: 100%; max-height: 90vh; object-fit: contain; transition: transform 0.3s ease; cursor: zoom-in;">
			</div>
			
			<!-- Zoom Controls -->
			<div class="avto-lightbox-zoom-controls" style="position: absolute; bottom: 80px; right: 20px; display: flex; flex-direction: column; gap: 8px; z-index: 10;">
				<button type="button" class="avto-lightbox-zoom-in" aria-label="<?php esc_attr_e( 'Zoom In', 'avto' ); ?>" style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 44px; height: 44px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="11" cy="11" r="8"></circle>
						<path d="m21 21-4.35-4.35"></path>
						<line x1="11" y1="8" x2="11" y2="14"></line>
						<line x1="8" y1="11" x2="14" y2="11"></line>
					</svg>
				</button>
				<button type="button" class="avto-lightbox-zoom-out" aria-label="<?php esc_attr_e( 'Zoom Out', 'avto' ); ?>" style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 44px; height: 44px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="11" cy="11" r="8"></circle>
						<path d="m21 21-4.35-4.35"></path>
						<line x1="8" y1="11" x2="14" y2="11"></line>
					</svg>
				</button>
				<button type="button" class="avto-lightbox-zoom-reset" aria-label="<?php esc_attr_e( 'Reset Zoom', 'avto' ); ?>" style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white; width: 44px; height: 44px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="1 4 1 10 7 10"></polyline>
						<polyline points="23 20 23 14 17 14"></polyline>
						<path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
					</svg>
				</button>
			</div>
			
			<!-- Metadata Overlay -->
			<div class="avto-lightbox-metadata" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.7); color: white; padding: 12px 20px; border-radius: 8px; max-width: 90%; text-align: center; backdrop-filter: blur(10px);">
				<h4 class="avto-lightbox-title" style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 600;"></h4>
				<p class="avto-lightbox-date" style="margin: 0; font-size: 0.85rem; opacity: 0.8;"></p>
			</div>
			
		</div>
	</div>
	
	<style>
		/* ===================================
		   HISTORY GRID LAYOUT
		   =================================== */
		
		.avto-history-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr); /* Fixed 3 columns on desktop */
			gap: 2rem;
			margin-top: 1.5rem;
		}
		
		/* ===================================
		   HISTORY CARD STRUCTURE
		   =================================== */
		
		.avto-history-item {
			display: flex;
			flex-direction: column;
			border: 1px solid #e1cccb;
			border-radius: 12px;
			overflow: hidden;
			background: #fff;
			box-shadow: 0 2px 8px rgba(125, 90, 104, 0.08);
			transition: all 0.3s ease;
			height: 100%; /* Ensures all cards same height */
		}
		
		.avto-history-item:hover {
			box-shadow: 0 6px 20px rgba(125, 90, 104, 0.15);
			transform: translateY(-2px);
			border-color: #c4cdc3;
		}
		
		/* Card Content Area - Uses flexbox for consistent heights */
		.avto-history-card-content {
			display: flex;
			flex-direction: column;
			flex: 1; /* Fills available space */
			padding: 1.25rem;
		}
		
		/* Info Section - Fixed height for consistency */
		.avto-history-card-info {
			flex: 0 0 auto; /* Don't grow, don't shrink */
			margin-bottom: 1rem;
		}
		
		/* Title Styling */
		.avto-history-card-title {
			margin: 0 0 0.5rem 0;
			font-size: 1rem;
			font-weight: 600;
			line-height: 1.4;
			min-height: 2.8em; /* Accommodates 2 lines consistently */
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		
		.avto-history-card-title a {
			color: #7d5a68;
			text-decoration: none;
			transition: color 0.2s ease;
		}
		
		.avto-history-card-title a:hover {
			color: #63444f;
			text-decoration: underline;
		}
		
		.avto-history-card-title.avto-no-product {
			color: #8b8391;
		}
		
		/* Date Styling */
		.avto-history-card-date {
			margin: 0;
			font-size: 0.875rem;
			color: #8b8391;
			font-weight: 500;
		}
		
		/* Error Message */
		.avto-history-error-message {
			margin: 0.75rem 0 0 0;
			font-size: 0.8125rem;
			color: #dc3232;
			padding: 0.625rem;
			background: #fef2f2;
			border-radius: 6px;
			border-left: 3px solid #dc3232;
			line-height: 1.4;
		}
		
		/* ===================================
		   BUTTON SECTION - Always at bottom
		   =================================== */
		
		.avto-history-actions {
			margin-top: auto; /* Pushes buttons to bottom of card */
			display: flex;
			flex-direction: column;
			gap: 0.625rem;
		}
		
		.avto-history-btn-row {
			display: flex;
			gap: 0.625rem;
		}
		
		/* Base Button Styles */
		.avto-history-actions .button {
			flex: 1;
			text-align: center;
			font-size: 0.875rem;
			padding: 0.625rem 0.5rem;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.375rem;
			min-height: 44px;
			border-radius: 8px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
			border: none;
			text-decoration: none;
			line-height: 1;
		}
		
		.avto-history-actions .button svg {
			flex-shrink: 0;
		}
		
		/* View Button */
		.avto-view-btn {
			background: #7d5a68;
			color: #fff;
			border-color: #7d5a68;
		}
		
		.avto-view-btn:hover {
			background: #63444f;
			border-color: #63444f;
			transform: translateY(-1px);
			box-shadow: 0 2px 8px rgba(125, 90, 104, 0.3);
		}
		
		/* Download Button */
		.avto-download-btn {
			background: #9da99c;
			color: #fff;
			border-color: #9da99c;
		}
		
		.avto-download-btn:hover {
			background: #8b9688;
			border-color: #8b9688;
			transform: translateY(-1px);
			box-shadow: 0 2px 8px rgba(157, 169, 156, 0.3);
		}
		
		/* Delete Button */
		.avto-delete-history-item {
			width: 100%;
			background: #dc3232;
			color: #fff;
			border-color: #dc3232;
		}
		
		.avto-delete-history-item:hover {
			background: #a00;
			border-color: #a00;
			transform: translateY(-1px);
			box-shadow: 0 2px 8px rgba(220, 50, 50, 0.3);
		}
		
		/* Processing/Disabled Button */
		.avto-processing-btn {
			width: 100%;
			background: #f3f4f5;
			color: #8b8391;
			border-color: #e1cccb;
			opacity: 0.7;
			cursor: not-allowed;
		}
		
		/* ===================================
		   IMAGE HOVER EFFECTS
		   =================================== */
		
		.avto-history-image-wrapper:hover .avto-image-hover-overlay {
			background: rgba(0,0,0,0.3) !important;
		}
		
		.avto-history-image-wrapper:hover .avto-image-hover-overlay svg {
			opacity: 1 !important;
		}
		
		/* Lightbox Button Hover States */
		.avto-lightbox-close:hover,
		.avto-lightbox-prev:hover,
		.avto-lightbox-next:hover,
		.avto-lightbox-zoom-in:hover,
		.avto-lightbox-zoom-out:hover,
		.avto-lightbox-zoom-reset:hover {
			background: rgba(255,255,255,0.2) !important;
			border-color: rgba(255,255,255,0.5) !important;
			transform: scale(1.05);
		}
		
		.avto-lightbox-prev:hover,
		.avto-lightbox-next:hover {
			transform: translateY(-50%) scale(1.05) !important;
		}
		
		/* Zoom States */
		.avto-lightbox-image.zoomed {
			cursor: zoom-out !important;
		}
		
		/* Spinner Animation */
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
		
		/* ===================================
		   RESPONSIVE BREAKPOINTS
		   =================================== */
		
		/* Large Tablets & Small Desktops (≤1200px) */
		@media (max-width: 1200px) {
			.avto-history-grid {
				gap: 1.5rem;
			}
		}
		
		/* Tablets (≤1024px) - Switch to 2 columns */
		@media (max-width: 1024px) {
			.avto-history-grid {
				grid-template-columns: repeat(2, 1fr);
				gap: 1.25rem;
			}
			
			.avto-history-card-title {
				font-size: 0.9375rem;
			}
		}
		
		/* Small Tablets & Large Phones (≤768px) */
		@media (max-width: 768px) {
			.avto-history-grid {
				grid-template-columns: repeat(2, 1fr);
				gap: 1rem;
			}
			
			.avto-history-card-content {
				padding: 1rem;
			}
			
			.avto-history-card-title {
				font-size: 0.875rem;
				min-height: 2.6em;
			}
			
			.avto-history-card-date {
				font-size: 0.8125rem;
			}
			
			.avto-history-actions .button {
				font-size: 0.8125rem;
				padding: 0.5rem 0.375rem;
			}
			
			/* Hide button text, show icons only */
			.avto-view-btn span,
			.avto-download-btn span,
			.avto-delete-history-item span {
				display: none;
			}
			
			.avto-history-actions .button svg {
				margin: 0;
			}
		}
		
		/* Mobile Phones (≤600px) - Single column */
		@media (max-width: 600px) {
			.avto-history-grid {
				grid-template-columns: 1fr;
				gap: 1rem;
			}
			
			/* Show button text again on single column */
			.avto-view-btn span,
			.avto-download-btn span,
			.avto-delete-history-item span {
				display: inline;
			}
		}
			
			.avto-lightbox-close,
			.avto-lightbox-prev,
			.avto-lightbox-next {
				width: 40px !important;
				height: 40px !important;
			}
			
			.avto-lightbox-prev {
				left: 10px !important;
			}
			
			.avto-lightbox-next {
				right: 10px !important;
			}
			
			.avto-lightbox-close {
				top: 10px !important;
				right: 10px !important;
			}
			
			.avto-lightbox-zoom-controls {
				bottom: 60px !important;
				right: 10px !important;
			}
			
			.avto-lightbox-metadata {
				font-size: 0.85rem !important;
				padding: 8px 12px !important;
			}
		}
		
		/* Small Mobile (≤480px) - Optimize for compact screens */
		@media (max-width: 480px) {
			.avto-history-card-content {
				padding: 0.875rem;
			}
			
			.avto-history-card-title {
				font-size: 0.875rem;
			}
			
			.avto-history-actions .button {
				font-size: 0.8125rem;
				padding: 0.5rem;
				min-height: 42px;
			}
			
			.avto-lightbox-zoom-controls {
				flex-direction: row !important;
				bottom: 10px !important;
				right: 50% !important;
				transform: translateX(50%);
			}
			
			.avto-lightbox-metadata {
				bottom: 60px !important;
			}
		}
	</style>
	
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		
		/* ========================================
		   LIGHTBOX FUNCTIONALITY
		   ======================================== */
		const AVTOHistoryLightbox = {
			currentIndex: 0,
			images: [],
			zoomLevel: 1,
			$lightbox: null,
			$image: null,
			
			init: function() {
				this.$lightbox = $('#avto-history-lightbox');
				this.$image = $('.avto-lightbox-image');
				this.bindEvents();
				this.collectImages();
			},
			
			collectImages: function() {
				this.images = [];
				$('[data-lightbox-trigger]').each(function(index) {
					const $trigger = $(this);
					AVTOHistoryLightbox.images.push({
						index: index,
						fullUrl: $trigger.data('image-full'),
						thumbUrl: $trigger.data('image-thumb'),
						productName: $trigger.data('product-name'),
						timestamp: $trigger.data('timestamp'),
						$element: $trigger
					});
				});
			},
			
			bindEvents: function() {
				const self = this;
				
				// Open lightbox on image click or View button
				$(document).on('click', '[data-lightbox-trigger], [data-lightbox-open]', function(e) {
					e.preventDefault();
					const sessionId = $(this).data('session-id');
					const imageData = self.images.find(img => img.$element.data('session-id') === sessionId);
					if (imageData) {
						self.open(imageData.index);
					}
				});
				
				// Close button
				$('.avto-lightbox-close').on('click', function() {
					self.close();
				});
				
				// Click backdrop to close
				this.$lightbox.on('click', function(e) {
					if ($(e.target).is('.avto-lightbox') || $(e.target).is('.avto-lightbox-container')) {
						self.close();
					}
				});
				
				// Navigation
				$('.avto-lightbox-prev').on('click', function() {
					self.navigate(-1);
				});
				
				$('.avto-lightbox-next').on('click', function() {
					self.navigate(1);
				});
				
				// Zoom controls
				$('.avto-lightbox-zoom-in').on('click', function() {
					self.zoom(0.25);
				});
				
				$('.avto-lightbox-zoom-out').on('click', function() {
					self.zoom(-0.25);
				});
				
				$('.avto-lightbox-zoom-reset').on('click', function() {
					self.resetZoom();
				});
				
				// Image click to toggle zoom
				this.$image.on('click', function() {
					if (self.zoomLevel === 1) {
						self.zoom(1); // Zoom to 2x
					} else {
						self.resetZoom();
					}
				});
				
				// Keyboard controls
				$(document).on('keydown', function(e) {
					if (!self.$lightbox.is(':visible')) return;
					
					switch(e.key) {
						case 'Escape':
							self.close();
							break;
						case 'ArrowLeft':
							self.navigate(-1);
							break;
						case 'ArrowRight':
							self.navigate(1);
							break;
						case '+':
						case '=':
							self.zoom(0.25);
							break;
						case '-':
						case '_':
							self.zoom(-0.25);
							break;
						case '0':
							self.resetZoom();
							break;
					}
				});
				
				// Touch gestures for mobile
				let touchStartX = 0;
				let touchStartY = 0;
				let touchStartDistance = 0;
				
				this.$image.on('touchstart', function(e) {
					if (e.touches.length === 1) {
						touchStartX = e.touches[0].clientX;
						touchStartY = e.touches[0].clientY;
					} else if (e.touches.length === 2) {
						const dx = e.touches[0].clientX - e.touches[1].clientX;
						const dy = e.touches[0].clientY - e.touches[1].clientY;
						touchStartDistance = Math.sqrt(dx * dx + dy * dy);
					}
				});
				
				this.$image.on('touchmove', function(e) {
					if (e.touches.length === 2 && touchStartDistance > 0) {
						e.preventDefault();
						const dx = e.touches[0].clientX - e.touches[1].clientX;
						const dy = e.touches[0].clientY - e.touches[1].clientY;
						const distance = Math.sqrt(dx * dx + dy * dy);
						const scale = distance / touchStartDistance;
						
						if (scale > 1.1) {
							self.zoom(0.25);
							touchStartDistance = distance;
						} else if (scale < 0.9) {
							self.zoom(-0.25);
							touchStartDistance = distance;
						}
					}
				});
				
				this.$image.on('touchend', function(e) {
					if (e.changedTouches.length === 1 && touchStartX > 0) {
						const touchEndX = e.changedTouches[0].clientX;
						const touchEndY = e.changedTouches[0].clientY;
						const deltaX = touchEndX - touchStartX;
						const deltaY = touchEndY - touchStartY;
						
						// Swipe detection (horizontal swipe > 50px and mostly horizontal)
						if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > Math.abs(deltaY)) {
							if (deltaX > 0) {
								self.navigate(-1); // Swipe right = previous
							} else {
								self.navigate(1); // Swipe left = next
							}
						}
						
						touchStartX = 0;
						touchStartY = 0;
					}
					touchStartDistance = 0;
				});
			},
			
			open: function(index) {
				this.currentIndex = index;
				this.loadImage();
				this.$lightbox.fadeIn(300);
				$('body').css('overflow', 'hidden'); // Prevent body scroll
				this.updateNavigation();
			},
			
			close: function() {
				this.$lightbox.fadeOut(300);
				$('body').css('overflow', '');
				this.resetZoom();
			},
			
			loadImage: function() {
				const imageData = this.images[this.currentIndex];
				if (!imageData) return;
				
				// Show loading
				$('.avto-lightbox-loading').show();
				this.$image.hide();
				
				// Load image
				const img = new Image();
				img.onload = () => {
					this.$image.attr('src', imageData.fullUrl);
					this.$image.attr('alt', imageData.productName);
					$('.avto-lightbox-title').text(imageData.productName);
					$('.avto-lightbox-date').text(imageData.timestamp);
					$('.avto-lightbox-loading').hide();
					this.$image.fadeIn(300);
					this.resetZoom();
				};
				img.onerror = () => {
					$('.avto-lightbox-loading').hide();
					alert('<?php echo esc_js( __( 'Failed to load image.', 'avto' ) ); ?>');
					this.close();
				};
				img.src = imageData.fullUrl;
			},
			
			navigate: function(direction) {
				this.currentIndex += direction;
				
				// Wrap around
				if (this.currentIndex < 0) {
					this.currentIndex = this.images.length - 1;
				} else if (this.currentIndex >= this.images.length) {
					this.currentIndex = 0;
				}
				
				this.loadImage();
				this.updateNavigation();
			},
			
			updateNavigation: function() {
				// Hide/show navigation arrows if only one image
				if (this.images.length <= 1) {
					$('.avto-lightbox-prev, .avto-lightbox-next').hide();
				} else {
					$('.avto-lightbox-prev, .avto-lightbox-next').show();
				}
			},
			
			zoom: function(delta) {
				this.zoomLevel = Math.max(0.5, Math.min(3, this.zoomLevel + delta));
				this.$image.css('transform', `scale(${this.zoomLevel})`);
				
				if (this.zoomLevel > 1) {
					this.$image.addClass('zoomed');
				} else {
					this.$image.removeClass('zoomed');
				}
			},
			
			resetZoom: function() {
				this.zoomLevel = 1;
				this.$image.css('transform', 'scale(1)');
				this.$image.removeClass('zoomed');
			}
		};
		
		// Initialize lightbox
		AVTOHistoryLightbox.init();
		
		/* ========================================
		   DEFAULT IMAGE UPLOAD
		   ======================================== */
		
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

