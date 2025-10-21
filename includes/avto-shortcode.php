<?php
/**
 * Shortcode: AI Virtual Try-On UI
 *
 * @package AI_Virtual_Try_On
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get default clothing items for frontend (matches admin structure)
 *
 * @return array Default clothing items
 */
function avto_get_default_clothing_items_frontend() {
	$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
	return array(
		array(
			'id'    => 'shirt-1',
			'name'  => __( 'Classic White Shirt', 'avto' ),
			'image' => $plugin_url . 'assets/images/shirt-1.jpg',
		),
		array(
			'id'    => 'dress-1',
			'name'  => __( 'Summer Dress', 'avto' ),
			'image' => $plugin_url . 'assets/images/dress-1.jpg',
		),
	);
}

/**
 * Render the AI Virtual Try-On shortcode
 *
 * @return string HTML output
 */
function avto_render_ui_shortcode() {
	// Start output buffering
	ob_start();
	?>

	<div id="avto-container" class="avto-container">
		
		<!-- Upload Section -->
		<div class="avto-section avto-upload-section">
			<h3 class="avto-section-title"><?php esc_html_e( 'Step 1: Upload Your Photo', 'avto' ); ?></h3>
			<div class="avto-upload-area">
				<input 
					type="file" 
					id="avto-user-image" 
					class="avto-file-input" 
					accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
					aria-label="<?php esc_attr_e( 'Upload your photo', 'avto' ); ?>"
				>
				<label for="avto-user-image" class="avto-file-label">
					<svg class="avto-upload-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="17 8 12 3 7 8"></polyline>
						<line x1="12" y1="3" x2="12" y2="15"></line>
					</svg>
					<span class="avto-upload-text"><?php echo esc_html( get_option( 'avto_upload_button_text', __( 'Click to upload or drag and drop', 'avto' ) ) ); ?></span>
					<span class="avto-upload-hint"><?php esc_html_e( 'JPG, PNG, WebP, HEIC, or HEIF (max 5MB)', 'avto' ); ?></span>
				</label>
				<div id="avto-image-preview" class="avto-image-preview" style="display: none;">
					<img id="avto-preview-img" src="" alt="<?php esc_attr_e( 'Your photo preview', 'avto' ); ?>">
					<button type="button" id="avto-remove-image" class="avto-remove-btn" aria-label="<?php esc_attr_e( 'Remove image', 'avto' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
			</div>
		</div>

		<!-- Clothing Gallery Section -->
		<div class="avto-section avto-gallery-section">
			<h3 class="avto-section-title"><?php esc_html_e( 'Step 2: Select Clothing Item', 'avto' ); ?></h3>
			<div class="avto-clothing-gallery">
				<?php
				// Get clothing items from settings - using consistent data structure
				$clothing_items = get_option( 'avto_clothing_items', avto_get_default_clothing_items_frontend() );

				foreach ( $clothing_items as $item ) :
					// Support both old and new data structures for backward compatibility
					$item_id = isset( $item['id'] ) ? $item['id'] : '';
					$item_name = isset( $item['name'] ) ? $item['name'] : ( isset( $item['title'] ) ? $item['title'] : '' );
					$image_url = isset( $item['image'] ) ? $item['image'] : ( isset( $item['file_url'] ) ? $item['file_url'] : '' );
					
					// Extract filename for the data attribute (needed for backend processing)
					$filename = basename( $image_url );
					
					// Generate placeholder if no image
					if ( empty( $image_url ) ) {
						$image_url = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="400" viewBox="0 0 300 400"%3E%3Crect fill="%23ddd" width="300" height="400"/%3E%3Ctext fill="%23999" font-family="sans-serif" font-size="18" x="50%25" y="50%25" text-anchor="middle" dominant-baseline="middle"%3E' . esc_attr( $item_name ) . '%3C/text%3E%3C/svg%3E';
					}
					?>
					<div 
						class="avto-clothing-item" 
						data-clothing-id="<?php echo esc_attr( $item_id ); ?>" 
						data-clothing-file="<?php echo esc_attr( $filename ); ?>"
						role="button"
						tabindex="0"
						aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'avto' ), $item_name ) ); ?>"
					>
						<div class="avto-clothing-image-wrapper">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $item_name ); ?>">
						</div>
						<span class="avto-clothing-title"><?php echo esc_html( $item_name ); ?></span>
						<div class="avto-selected-indicator" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
								<polyline points="20 6 9 17 4 12"></polyline>
							</svg>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Action Section -->
		<div class="avto-section avto-action-section">
			<button 
				type="button" 
				id="avto-generate-btn" 
				class="avto-generate-btn" 
				disabled
				aria-label="<?php esc_attr_e( 'Generate virtual try-on', 'avto' ); ?>"
			>
				<?php echo esc_html( get_option( 'avto_generate_button_text', __( 'Generate Virtual Try-On', 'avto' ) ) ); ?>
			</button>
		</div>

		<!-- Results Section -->
		<div id="avto-results-section" class="avto-results-section" style="display: none;">
			<div class="avto-results-content">
				
				<!-- Loading Spinner -->
				<div id="avto-loading" class="avto-loading">
					<div class="avto-spinner">
						<svg class="avto-spinner-svg" xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="10"></circle>
						</svg>
					</div>
					<p class="avto-loading-text"><?php esc_html_e( 'Generating your virtual try-on...', 'avto' ); ?></p>
					<p class="avto-loading-hint"><?php esc_html_e( 'This may take 30-60 seconds', 'avto' ); ?></p>
				</div>

				<!-- Error Message -->
				<div id="avto-error" class="avto-error" style="display: none;">
					<svg class="avto-error-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"></circle>
						<line x1="12" y1="8" x2="12" y2="12"></line>
						<line x1="12" y1="16" x2="12.01" y2="16"></line>
					</svg>
					<p id="avto-error-message" class="avto-error-message"></p>
					<button type="button" id="avto-try-again-btn" class="avto-try-again-btn">
						<?php esc_html_e( 'Try Again', 'avto' ); ?>
					</button>
				</div>

				<!-- Success Result -->
				<div id="avto-success" class="avto-success" style="display: none;">
					<h3 class="avto-success-title"><?php esc_html_e( 'Your Virtual Try-On Result', 'avto' ); ?></h3>
					<div class="avto-result-image-container">
						<img id="avto-final-image" class="avto-final-image" src="" alt="<?php esc_attr_e( 'Virtual try-on result', 'avto' ); ?>">
					</div>
					<div class="avto-result-actions">
						<button type="button" id="avto-download-btn" class="avto-download-btn">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
								<polyline points="7 10 12 15 17 10"></polyline>
								<line x1="12" y1="15" x2="12" y2="3"></line>
							</svg>
							<?php esc_html_e( 'Download Image', 'avto' ); ?>
						</button>
						<button type="button" id="avto-new-tryon-btn" class="avto-new-tryon-btn">
							<?php esc_html_e( 'Create New Try-On', 'avto' ); ?>
						</button>
					</div>
				</div>

			</div>
		</div>

	</div>

	<?php
	// Return the buffered content
	return ob_get_clean();
}

// Register the shortcode
add_shortcode( 'ai_virtual_tryon', 'avto_render_ui_shortcode' );
