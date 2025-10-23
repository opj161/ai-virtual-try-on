/**
 * AI Virtual Try-On - Frontend JavaScript
 * 
 * Dual-mode support:
 * 1. WooCommerce modal mode (product pages)
 * 2. Shortcode mode (dedicated pages) - backward compatibility
 * 
 * Version 2.7.0+ improvements:
 * - Focus trap for modal accessibility
 * - ARIA live region announcements
 * - Skeleton loaders for better UX
 * - Enhanced error handling
 */

(function($) {
	'use strict';

	/**
	 * Accessibility Helper - ARIA Announcements
	 */
	const AVTOAccessibility = {
		/**
		 * Announce status to screen readers
		 */
		announceStatus: function(message) {
			const $status = $('#avto-status-message');
			if ($status.length) {
				$status.text(message);
			}
		},

		/**
		 * Focus trap for modal
		 */
		trapFocus: function($modal) {
			const focusableSelector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]):not([disabled])';
			const $focusableElements = $modal.find(focusableSelector);
			const $firstElement = $focusableElements.first();
			const $lastElement = $focusableElements.last();

			// Remove any existing trap listener
			$modal.off('keydown.focustrap');

			// Add trap listener
			$modal.on('keydown.focustrap', function(e) {
				if (e.key === 'Tab') {
					if (e.shiftKey) {
						// Shift + Tab
						if (document.activeElement === $firstElement[0]) {
							e.preventDefault();
							$lastElement.focus();
						}
					} else {
						// Tab
						if (document.activeElement === $lastElement[0]) {
							e.preventDefault();
							$firstElement.focus();
						}
					}
				} else if (e.key === 'Escape') {
					// Close modal on Escape
					AVTOModal.closeModal();
				}
			});

			// Focus first element after a short delay (for transitions)
			setTimeout(function() {
				const $closeBtn = $modal.find('.avto-modal-close');
				if ($closeBtn.length) {
					$closeBtn.focus();
				} else {
					$firstElement.focus();
				}
			}, 100);
		},

		/**
		 * Remove focus trap
		 */
		removeFocusTrap: function($modal) {
			$modal.off('keydown.focustrap');
		}
	};

	/**
	 * Toast Notification System
	 * 
	 * Shows persistent notifications for completed try-on jobs.
	 * Uses localStorage for cross-page persistence within the same session.
	 */
	const AVTOToast = {
		storageKey: 'avto_pending_notifications',
		toastDuration: 15000, // 15 seconds auto-dismiss

		/**
		 * Initialize toast system - check for pending notifications on page load
		 */
		init: function() {
			this.checkPendingNotifications();
			this.bindToastEvents();
		},

		/**
		 * Check localStorage for pending notifications and display them
		 */
		checkPendingNotifications: function() {
			const notifications = this.getPendingNotifications();
			
			if (notifications && notifications.length > 0) {
				// Show toast for pending notifications
				const count = notifications.length;
				const message = count === 1 
					? 'Your virtual try-on is ready!' 
					: count + ' virtual try-on results are ready!';
				
				this.showToast(message, count);
			}
		},

		/**
		 * Get pending notifications from localStorage
		 */
		getPendingNotifications: function() {
			try {
				const stored = localStorage.getItem(this.storageKey);
				return stored ? JSON.parse(stored) : [];
			} catch (e) {
				console.error('AVTO: Error reading notifications from localStorage', e);
				return [];
			}
		},

		/**
		 * Add a new notification to localStorage
		 */
		addNotification: function(sessionId, imageUrl) {
			const notifications = this.getPendingNotifications();
			
			// Add new notification if not already present
			if (!notifications.find(n => n.sessionId === sessionId)) {
				notifications.push({
					sessionId: sessionId,
					imageUrl: imageUrl,
					timestamp: Date.now()
				});
				
				try {
					localStorage.setItem(this.storageKey, JSON.stringify(notifications));
				} catch (e) {
					console.error('AVTO: Error saving notification to localStorage', e);
				}
			}
		},

		/**
		 * Clear all notifications from localStorage
		 */
		clearNotifications: function() {
			try {
				localStorage.removeItem(this.storageKey);
			} catch (e) {
				console.error('AVTO: Error clearing notifications from localStorage', e);
			}
		},

		/**
		 * Show toast notification
		 */
		showToast: function(message, count) {
			// Remove existing toast if present
			$('#avto-toast-notification').remove();

			// Create toast HTML
			const historyUrl = typeof avtoProductData !== 'undefined' && avtoProductData.historyUrl 
				? avtoProductData.historyUrl 
				: '/my-account/try-on-history/';

			const toastHtml = `
				<div id="avto-toast-notification" class="avto-toast" role="alert" aria-live="polite" aria-atomic="true">
					<div class="avto-toast-content">
						<svg class="avto-toast-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
							<polyline points="22 4 12 14.01 9 11.01"></polyline>
						</svg>
						<div class="avto-toast-body">
							<p class="avto-toast-message">${message}</p>
							<a href="${historyUrl}" class="avto-toast-cta">View Result${count > 1 ? 's' : ''}</a>
						</div>
						<button type="button" class="avto-toast-close" aria-label="Dismiss notification">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<line x1="18" y1="6" x2="6" y2="18"></line>
								<line x1="6" y1="6" x2="18" y2="18"></line>
							</svg>
						</button>
					</div>
				</div>
			`;

			// Append to body
			$('body').append(toastHtml);

			// Trigger animation after a tiny delay (for CSS transition)
			setTimeout(() => {
				$('#avto-toast-notification').addClass('avto-toast-show');
			}, 10);

			// Auto-dismiss after duration
			setTimeout(() => {
				this.hideToast();
			}, this.toastDuration);
		},

		/**
		 * Hide toast notification
		 */
		hideToast: function() {
			const $toast = $('#avto-toast-notification');
			$toast.removeClass('avto-toast-show');
			
			// Remove from DOM after animation completes
			setTimeout(() => {
				$toast.remove();
			}, 300);
		},

		/**
		 * Bind toast-specific events
		 */
		bindToastEvents: function() {
			const self = this;

			// Close button click
			$(document).on('click', '.avto-toast-close', function() {
				self.hideToast();
			});

			// CTA link click - clear notifications
			$(document).on('click', '.avto-toast-cta', function() {
				self.clearNotifications();
				self.hideToast();
			});
		}
	};

	/**
	 * WooCommerce Modal Mode
	 */
	const AVTOModal = {
		isModalMode: false,
		productId: 0,

		/**
		 * Initialize modal functionality
		 */
		init: function() {
			// Check if we're in WooCommerce mode (modal exists)
			if ( $('#avto-modal').length ) {
				this.isModalMode = true;
				this.bindModalEvents();
			} else {
				// Shortcode mode - use original initialization
				AVTOCore.init();
			}
		},

		/**
		 * Bind modal-specific events
		 */
		bindModalEvents: function() {
			const self = this;

			// Trigger button click
			$(document).on('click', '.avto-wc-tryon-trigger', function(e) {
				e.preventDefault();
				const $button = $(this);
				self.productId = $button.data('product-id');
				self.openModal();
			});

			// Close button and overlay
			$(document).on('click', '.avto-modal-close, .avto-modal-overlay', function() {
				self.closeModal();
			});

			// ESC key to close
			$(document).on('keydown', function(e) {
				if ( e.key === 'Escape' && $('#avto-modal').is(':visible') ) {
					self.closeModal();
				}
			});

			// Variable product support - listen for variation changes
			self.bindVariationEvents();
		},

		/**
		 * Bind WooCommerce variation events for variable products
		 */
		bindVariationEvents: function() {
			const self = this;

			// Listen for variation selection
			$('form.variations_form').on('found_variation', function(event, variation) {
				console.log('AVTO: Variation selected', variation);
				
				// Store the selected variation data
				self.selectedVariation = variation;
				
				// If modal is open, update the gallery with variation images
				if ( $('#avto-modal').is(':visible') && variation.image && variation.image.full_src ) {
					self.updateGalleryWithVariation(variation);
				}
			});

			// Listen for variation reset (when user clears selections)
			$('form.variations_form').on('reset_data', function() {
				console.log('AVTO: Variation reset');
				self.selectedVariation = null;
				
				// If modal is open, reload the original product images
				if ( $('#avto-modal').is(':visible') ) {
					self.reloadProductImages();
				}
			});
		},

		/**
		 * Update gallery with variation-specific images
		 */
		updateGalleryWithVariation: function(variation) {
			const $gallery = $('.avto-clothing-gallery');
			
			// Create variation image array
			const variationImages = [];
			
			// Add main variation image
			if ( variation.image && variation.image.full_src ) {
				variationImages.push({
					id: variation.image_id || 0,
					url: variation.image.full_src,
					alt: variation.image.alt || variation.image.title || 'Variation image',
					name: variation.image.caption || 'Variation Image'
				});
			}
			
			// Add gallery images if available
			if ( variation.variation_gallery_images && Array.isArray(variation.variation_gallery_images) ) {
				variation.variation_gallery_images.forEach((img, index) => {
					variationImages.push({
						id: img.image_id || 0,
						url: img.full_src || img.url,
						alt: img.alt || img.title || 'Variation gallery image',
						name: img.caption || 'Variation Image ' + (index + 2)
					});
				});
			}
			
			// If we have variation images, update the gallery
			if ( variationImages.length > 0 ) {
				this.populateGallery(variationImages);
				
				// Re-initialize core to bind new gallery item events
				AVTOCore.bindEvents();
			}
		},

		/**
		 * Reload original product images (after variation reset)
		 */
		reloadProductImages: function() {
			const self = this;
			
			if ( ! avtoProductData || ! avtoProductData.productId ) {
				return;
			}
			
			$.ajax({
				url: avtoProductData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'avto_get_product_images',
					product_id: avtoProductData.productId,
					nonce: avtoProductData.nonce
				},
				success: function(response) {
					if ( response.success && response.data.images && response.data.images.length > 0 ) {
						self.populateGallery(response.data.images);
						AVTOCore.bindEvents();
					}
				},
				error: function(xhr, status, error) {
					console.error('AVTO: Error reloading product images', status, error);
				}
			});
		},

	/**
	 * Open modal and initialize try-on UI
	 */
	openModal: function() {
		const $modal = $('#avto-modal');
		const $container = $('#avto-container');

		// Debug: Log all available data
		console.log('AVTO Debug - Opening modal...');
		console.log('AVTO Debug - avtoProductData exists:', typeof avtoProductData !== 'undefined');
		console.log('AVTO Debug - avtoAjax exists:', typeof avtoAjax !== 'undefined');
		
		if (typeof avtoProductData !== 'undefined') {
			console.log('AVTO Debug - avtoProductData:', avtoProductData);
		}

		// Check if we have product data
		if ( typeof avtoProductData === 'undefined' || ! avtoProductData.productId ) {
			console.error('AVTO: Product data not available');
			console.error('AVTO: Available globals:', Object.keys(window).filter(k => k.startsWith('avto')));
			alert('Unable to load product data. Please refresh the page.');
			return;
		}

		// Fetch product images via AJAX
		$.ajax({
			url: avtoProductData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'avto_get_product_images',
				product_id: avtoProductData.productId,
				nonce: avtoProductData.nonce
			},
			beforeSend: function() {
				// Show loading state
				$modal.addClass('avto-loading').attr('aria-hidden', 'false').fadeIn(300);
				$('body').addClass('avto-modal-open');
			},
			success: function(response) {
				$modal.removeClass('avto-loading');
				
				if ( response.success && response.data.images && response.data.images.length > 0 ) {
					// Store images
					AVTOModal.productImages = response.data.images;
					
					// Create UI if not present
					if ( ! $container.find('.avto-upload-section').length ) {
						AVTOModal.createUIStructure();
					}
					
					// Populate gallery with fetched images
					AVTOModal.populateGallery(response.data.images);
					
					// Initialize core functionality
					AVTOCore.init();
					
					// Trap focus for accessibility (after UI is ready)
					setTimeout(function() {
						AVTOAccessibility.trapFocus($modal);
						AVTOAccessibility.announceStatus('Virtual Try-On modal opened. Upload your photo to begin.');
					}, 350);
				} else {
					$modal.fadeOut(300);
					$('body').removeClass('avto-modal-open');
					alert(response.data && response.data.message ? response.data.message : 'No images found for this product.');
				}
			},
			error: function(xhr, status, error) {
				console.error('AVTO AJAX Error:', status, error);
				$modal.removeClass('avto-loading').fadeOut(300);
				$('body').removeClass('avto-modal-open');
				alert('Error loading product images. Please try again.');
			}
		});
	},		/**
		 * Close modal and reset state
		 */
		closeModal: function() {
			const $modal = $('#avto-modal');
			
			// Remove focus trap before closing
			AVTOAccessibility.removeFocusTrap($modal);
			
			$modal.attr('aria-hidden', 'true').fadeOut(300);
			$('body').removeClass('avto-modal-open');

			// Reset UI state
			AVTOCore.reset();
		},

		/**
		 * Create full UI structure
		 */
		createUIStructure: function() {
			const $container = $('#avto-container');
			const uploadButtonText = typeof avtoAjax !== 'undefined' && avtoAjax.strings ? 
				avtoAjax.strings.uploadText || 'Click to upload or drag and drop' : 
				'Click to upload or drag and drop';

			$container.html(`
				<!-- Upload Section -->
				<div class="avto-section avto-upload-section">
					<h3 class="avto-section-title">Step 1: Upload Your Photo</h3>
					<div class="avto-upload-area">
						<input 
							type="file" 
							id="avto-user-image" 
							class="avto-file-input" 
							accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
							aria-label="Upload your photo"
						>
						<label for="avto-user-image" class="avto-file-label">
							<svg class="avto-upload-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
								<polyline points="17 8 12 3 7 8"></polyline>
								<line x1="12" y1="3" x2="12" y2="15"></line>
							</svg>
							<span class="avto-upload-text">${uploadButtonText}</span>
							<span class="avto-upload-hint">JPG, PNG, WebP, HEIC, or HEIF (max 5MB)</span>
						</label>
						<div id="avto-image-preview" class="avto-image-preview" style="display: none;">
							<img id="avto-preview-img" src="" alt="Your photo preview">
							<button type="button" id="avto-remove-image" class="avto-remove-btn" aria-label="Remove image">
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
					<h3 class="avto-section-title">Step 2: Select Product Image</h3>
					<div class="avto-clothing-gallery"></div>
				</div>

				<!-- Generate Button Section -->
				<div class="avto-section avto-action-section">
					<button id="avto-generate-btn" class="avto-btn avto-btn-primary" disabled>
						Generate Virtual Try-On
					</button>
				</div>

				<!-- Results Section -->
				<div id="avto-results-section" class="avto-section avto-results-section" style="display: none;">
					<div id="avto-loading" class="avto-loading" style="display: none;">
						<div class="avto-spinner"></div>
						<p>Generating your virtual try-on...</p>
					</div>

					<div id="avto-error" class="avto-error" style="display: none;">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="12" y1="8" x2="12" y2="12"></line>
							<line x1="12" y1="16" x2="12.01" y2="16"></line>
						</svg>
						<p id="avto-error-message"></p>
						<button id="avto-try-again-btn" class="avto-btn avto-btn-secondary">Try Again</button>
					</div>

					<div id="avto-success" class="avto-success" style="display: none;">
						<img id="avto-final-image" src="" alt="Virtual try-on result">
						<div class="avto-success-actions">
							<button id="avto-download-btn" class="avto-btn avto-btn-secondary">Download Image</button>
							<button id="avto-new-tryon-btn" class="avto-btn avto-btn-primary">Try Another</button>
						</div>
					</div>
				</div>
			`);
		},

		/**
		 * Populate gallery with product images
		 */
		populateGallery: function(images) {
			const $gallery = $('.avto-clothing-gallery');
			$gallery.empty();

			images.forEach((image, index) => {
				const $item = $('<div>')
					.addClass('avto-clothing-item')
					.attr({
						'data-clothing-id': image.id,
						'data-product-id': this.productId,
						'tabindex': '0',
						'role': 'button',
						'aria-label': image.alt || 'Product image ' + (index + 1)
					})
					.html(`
						<img src="${image.url}" alt="${image.alt || ''}" loading="lazy">
						<span class="avto-clothing-name">${image.name || 'Image ' + (index + 1)}</span>
					`);

				$gallery.append($item);
			});

			// Store product ID for AJAX
			$('#avto-generate-btn').data('product-id', this.productId);
		}
	};

	/**
	 * Core Try-On Functionality (shared between modal and shortcode)
	 */
	const AVTOCore = {
		userImageFile: null,
		selectedClothingId: null,
		selectedClothingFile: null,
		defaultUserImageId: null, // Store default image ID for logged-in users

		/**
		 * Initialize core functionality
		 */
		init: function() {
			this.bindEvents();
			this.loadDefaultUserImage();
			this.checkGenerateButtonState();
		},

		/**
		 * Load and display default user image if available
		 */
		loadDefaultUserImage: function() {
			// Check if we have product data with default image
			if (typeof avtoProductData !== 'undefined' && avtoProductData.defaultUserImage) {
				const defaultImage = avtoProductData.defaultUserImage;
				
				// Store the default image ID
				this.defaultUserImageId = defaultImage.id;
				
				// Display the default image in preview
				$('#avto-preview-img').attr('src', defaultImage.url);
				$('.avto-file-label').hide();
				$('#avto-image-preview').fadeIn(300);
				
				// Add a notice that default image is being used
				if (!$('.avto-default-image-notice').length) {
					$('#avto-image-preview').prepend(
						'<div class="avto-default-image-notice" style="background: #e7f7ff; border: 1px solid #00a0d2; padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px; font-size: 0.85rem;">' +
							'<strong>Using your default try-on photo.</strong> Upload a new image to use a different photo.' +
						'</div>'
					);
				}
				
				console.log('AVTO: Loaded default user image', defaultImage);
			}
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			const self = this;

			// File input change
			$(document).on('change', '#avto-user-image', function(e) {
				self.handleFileSelect(e);
			});

			// Drag and drop
			$(document).on('dragover', '.avto-file-label', function(e) {
				self.handleDragOver(e);
			});
			$(document).on('dragleave', '.avto-file-label', function(e) {
				self.handleDragLeave(e);
			});
			$(document).on('drop', '.avto-file-label', function(e) {
				self.handleFileDrop(e);
			});

			// Remove image button
			$(document).on('click', '#avto-remove-image', function(e) {
				self.handleRemoveImage(e);
			});

			// Clothing item selection
			$(document).on('click', '.avto-clothing-item', function(e) {
				self.handleClothingSelect(e, $(this));
			});

			// Keyboard navigation for clothing items
			$(document).on('keydown', '.avto-clothing-item', function(e) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					$(this).click();
				}
			});

			// Generate button
			$(document).on('click', '#avto-generate-btn', function(e) {
				self.handleGenerate(e);
			});

			// Try again button
			$(document).on('click', '#avto-try-again-btn', function(e) {
				self.handleTryAgain(e);
			});

			// Download button
			$(document).on('click', '#avto-download-btn', function(e) {
				self.handleDownload(e);
			});

			// New try-on button
			$(document).on('click', '#avto-new-tryon-btn', function(e) {
				self.handleNewTryon(e);
			});
		},

		/**
		 * Handle file selection from input
		 */
		handleFileSelect: function(e) {
			const file = e.target.files[0];
			if (file) {
				// Clear default image ID when user uploads a new file
				this.defaultUserImageId = null;
				$('.avto-default-image-notice').remove();
				this.validateAndPreviewFile(file);
			}
		},

		/**
		 * Handle drag over event
		 */
		handleDragOver: function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(e.currentTarget).addClass('drag-over');
		},

		/**
		 * Handle drag leave event
		 */
		handleDragLeave: function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(e.currentTarget).removeClass('drag-over');
		},

		/**
		 * Handle file drop event
		 */
		handleFileDrop: function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(e.currentTarget).removeClass('drag-over');

			const files = e.originalEvent.dataTransfer.files;
			if (files.length > 0) {
				this.validateAndPreviewFile(files[0]);
			}
		},

		/**
		 * Validate and preview the selected file
		 */
		validateAndPreviewFile: function(file) {
			// Validate file type
			const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
			const unsupportedFormats = ['image/avif'];

			if (unsupportedFormats.includes(file.type)) {
				alert('AVIF format is not supported by the AI service. Please convert your image to JPG, PNG, WebP, or HEIC format first.');
				return;
			}

			if (!validTypes.includes(file.type)) {
				alert('Invalid file type. Supported formats: JPG, PNG, WebP, HEIC, and HEIF.');
				return;
			}

			// Validate file size (max 5MB)
			const maxSize = 5 * 1024 * 1024;
			if (file.size > maxSize) {
				alert('File size must be less than 5MB.');
				return;
			}

			// Store file
			this.userImageFile = file;

			// Preview image
			const reader = new FileReader();
			const self = this;
			reader.onload = function(e) {
				$('#avto-preview-img').attr('src', e.target.result);
				$('.avto-file-label').hide();
				$('#avto-image-preview').fadeIn(300);
				self.checkGenerateButtonState();
			};
			reader.readAsDataURL(file);
		},

		/**
		 * Handle remove image button click
		 */
		handleRemoveImage: function(e) {
			e.preventDefault();
			this.userImageFile = null;
			$('#avto-user-image').val('');
			$('.avto-default-image-notice').remove();
			
			// Check if we should restore default image
			if (typeof avtoProductData !== 'undefined' && avtoProductData.defaultUserImage) {
				// Restore default image
				this.loadDefaultUserImage();
			} else {
				// No default image, show upload prompt
				$('#avto-image-preview').hide();
				$('.avto-file-label').fadeIn(300);
			}
			
			this.checkGenerateButtonState();
		},

		/**
		 * Handle clothing item selection
		 */
		handleClothingSelect: function(e, $item) {
			e.preventDefault();

			// Remove selected class from all items
			$('.avto-clothing-item').removeClass('selected').attr('aria-pressed', 'false');

			// Add selected class to clicked item
			$item.addClass('selected').attr('aria-pressed', 'true');

			// Store selection - check if we're in WooCommerce mode
			this.selectedClothingId = $item.data('clothing-id');
			this.selectedClothingFile = $item.data('clothing-file') || '';

			this.checkGenerateButtonState();
		},

		/**
		 * Check if generate button should be enabled
		 */
		checkGenerateButtonState: function() {
			const $generateBtn = $('#avto-generate-btn');
			// Enable button if we have (user file OR default image) AND clothing selected
			const hasImage = this.userImageFile || this.defaultUserImageId;
			if (hasImage && this.selectedClothingId) {
				$generateBtn.prop('disabled', false);
			} else {
				$generateBtn.prop('disabled', true);
			}
		},

		/**
		 * Handle generate button click
		 */
		handleGenerate: function(e) {
			e.preventDefault();

			// Validate inputs - check if we have either a new file OR default image
			if (!this.userImageFile && !this.defaultUserImageId) {
				alert('Please upload your photo first.');
				return;
			}

			if (!this.selectedClothingId) {
				alert('Please select a clothing item.');
				return;
			}

			// Show loading state
			this.showLoadingState();

			// Prepare form data
			const formData = new FormData();
			formData.append('action', 'avto_generate_image');
			formData.append('nonce', avtoAjax.nonce);
			
			// Send either new image file OR existing image ID
			if (this.userImageFile) {
				// New upload
				formData.append('user_image', this.userImageFile);
			} else if (this.defaultUserImageId) {
				// Use default image ID
				formData.append('user_image_id', this.defaultUserImageId);
			}

			// Check if we're in WooCommerce mode (modal) or shortcode mode
			const $generateBtn = $('#avto-generate-btn');
			const productId = $generateBtn.data('product-id');

			if (productId) {
				// WooCommerce mode: send product_id and clothing_image_id
				formData.append('product_id', productId);
				formData.append('clothing_image_id', this.selectedClothingId);
			} else {
				// Shortcode mode: send clothing_id and clothing_file
				formData.append('clothing_id', this.selectedClothingId);
				formData.append('clothing_file', this.selectedClothingFile);
			}

			// Make AJAX request
			const self = this;
			$.ajax({
				url: avtoAjax.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				timeout: 65000,
				success: function(response) {
					if (response.success) {
						// Check if response is async (background job)
						if (response.data.status === 'pending' && response.data.session_id) {
							// Show background processing message
							self.showBackgroundProcessingState(response.data.message, response.data.session_id);
						} else {
							// Synchronous response (fallback)
							self.showSuccessState(response.data.image_url);
						}
					} else {
						self.showErrorState(response.data.message || 'An error occurred. Please try again.');
					}
				},
				error: function(xhr, status, error) {
					let errorMsg = 'An error occurred. Please try again.';

					if (status === 'timeout') {
						errorMsg = 'Request timed out. The image generation is taking longer than expected. Please try again.';
					} else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMsg = xhr.responseJSON.data.message;
					}

					self.showErrorState(errorMsg);
				}
			});
		},

		/**
		 * Show background processing state with polling
		 */
		showBackgroundProcessingState: function(message, sessionId) {
			$('#avto-generate-btn').prop('disabled', true);
			$('#avto-results-section').fadeIn(300);
			$('#avto-loading').hide();
			$('#avto-error').hide();
			$('#avto-success').hide();

			// Create or update background processing UI
			let $bgProcessing = $('#avto-background-processing');
			
			if (!$bgProcessing.length) {
				$bgProcessing = $('<div id="avto-background-processing" class="avto-background-processing">' +
					'<div class="avto-spinner"></div>' +
					'<p class="avto-bg-message"></p>' +
					'<p class="avto-bg-hint">You can close this window and continue shopping. We\'ll notify you when your try-on is ready!</p>' +
					'<button type="button" id="avto-continue-shopping-btn" class="avto-btn avto-btn-secondary">Continue Shopping</button>' +
					'</div>');
				$('#avto-results-section').append($bgProcessing);
			}

			$bgProcessing.find('.avto-bg-message').text(message);
			$bgProcessing.fadeIn(300);

			// Scroll to results
			const $results = $('#avto-results-section');
			if ($results.length) {
				this.scrollToElement($results, 100);
			}

			// Start polling for job status
			this.startJobPolling(sessionId);
		},

		/**
		 * Start polling for job status
		 */
		startJobPolling: function(sessionId) {
			const self = this;
			let pollAttempts = 0;
			const maxPollAttempts = 30; // Poll for up to 5 minutes (30 * 10 seconds)

			const pollInterval = setInterval(function() {
				pollAttempts++;

				// Stop polling after max attempts
				if (pollAttempts > maxPollAttempts) {
					clearInterval(pollInterval);
					$('#avto-background-processing').hide();
					self.showErrorState('The generation is taking longer than expected. Please check your history page later.');
					return;
				}

				// Check job status
				$.ajax({
					url: avtoAjax.ajaxUrl,
					type: 'POST',
					data: {
						action: 'avto_check_job_status',
						nonce: avtoAjax.nonce,
						session_id: sessionId
					},
					success: function(response) {
						if (response.success) {
							if (response.data.status === 'completed') {
								// Job completed successfully
								clearInterval(pollInterval);
								$('#avto-background-processing').hide();
								
								// Check if modal is still open
								const modalOpen = $('#avto-modal').is(':visible') || $('#avto-container').is(':visible');
								
								if (modalOpen) {
									// Modal open - show result directly
									self.showSuccessState(response.data.image_url);
								} else {
									// Modal closed - save notification and show toast
									AVTOToast.addNotification(sessionId, response.data.image_url);
									AVTOToast.showToast('Your virtual try-on is ready!', 1);
								}
							}
							// If status is 'pending' or 'processing', continue polling
						} else {
							// Job failed
							clearInterval(pollInterval);
							$('#avto-background-processing').hide();
							self.showErrorState(response.data.message || 'Generation failed. Please try again.');
						}
					},
					error: function() {
						// Continue polling on network errors
						console.log('AVTO: Poll attempt ' + pollAttempts + ' failed, retrying...');
					}
				});
			}, 10000); // Poll every 10 seconds

			// Store interval ID for cleanup
			this.pollingInterval = pollInterval;

			// Add continue shopping button handler
			$(document).off('click', '#avto-continue-shopping-btn').on('click', '#avto-continue-shopping-btn', function() {
				clearInterval(pollInterval);
				
				// Close modal if in WooCommerce mode
				if (typeof AVTOModal !== 'undefined' && AVTOModal.isModalMode) {
					AVTOModal.closeModal();
				} else {
					// Shortcode mode - just hide results
					$('#avto-results-section').fadeOut(300);
					$('#avto-generate-btn').prop('disabled', false);
				}
			});
		},

		/**
		 * Scroll to element - handles both modal and page contexts
		 * @param {jQuery} $element - Element to scroll to
		 * @param {number} offset - Offset from top (default: 100)
		 */
		scrollToElement: function($element, offset = 100) {
			if (!$element || !$element.length) {
				return;
			}

			// Check if we're inside a modal
			const $modal = $('.avto-modal-container');
			
			if ($modal.length && $modal.is(':visible')) {
				// Modal context - scroll the modal container
				const elementTop = $element.position().top;
				const modalScrollTop = $modal.scrollTop();
				const targetScroll = modalScrollTop + elementTop - offset;
				
				$modal.animate({
					scrollTop: targetScroll
				}, 500);
			} else {
				// Page context - scroll the page (shortcode mode)
				$('html, body').animate({
					scrollTop: $element.offset().top - offset
				}, 500);
			}
		},

		/**
		 * Show loading state
		 */
		showLoadingState: function() {
			$('#avto-generate-btn').prop('disabled', true);
			$('#avto-results-section').fadeIn(300);
			$('#avto-loading').show();
			$('#avto-error').hide();
			$('#avto-success').hide();

			// Announce to screen readers
			AVTOAccessibility.announceStatus('Generating your virtual try-on. This may take up to 30 seconds.');

			// Scroll to results (modal-aware)
			const $results = $('#avto-results-section');
			if ($results.length) {
				this.scrollToElement($results, 100);
			}
		},

		/**
		 * Show error state
		 */
		showErrorState: function(message) {
			$('#avto-loading').hide();
			
			// Announce error to screen readers
			AVTOAccessibility.announceStatus('Error: ' + message);
			$('#avto-success').hide();
			$('#avto-error-message').text(message);
			$('#avto-error').fadeIn(300);
			$('#avto-generate-btn').prop('disabled', false);
		},

		/**
		 * Show success state
		 */
		showSuccessState: function(imageUrl) {
			$('#avto-loading').hide();
			$('#avto-error').hide();
			$('#avto-final-image').attr('src', imageUrl);
			$('#avto-success').fadeIn(300);
			$('#avto-generate-btn').prop('disabled', false);
			
			// Announce success to screen readers
			AVTOAccessibility.announceStatus('Virtual try-on complete. View your result below.');
		},

		/**
		 * Handle try again button click
		 */
		handleTryAgain: function(e) {
			e.preventDefault();
			$('#avto-results-section').fadeOut(300);
			$('#avto-generate-btn').prop('disabled', false);
		},

		/**
		 * Handle download button click
		 */
		handleDownload: function(e) {
			e.preventDefault();
			const imageUrl = $('#avto-final-image').attr('src');

			// Create temporary link and trigger download
			const link = document.createElement('a');
			link.href = imageUrl;
			link.download = 'virtual-tryon-' + Date.now() + '.png';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		},

		/**
		 * Handle new try-on button click
		 */
		handleNewTryon: function(e) {
			e.preventDefault();

			// Reset only clothing selection (keep user image)
			this.selectedClothingId = null;
			this.selectedClothingFile = null;

			// Reset UI
			$('.avto-clothing-item').removeClass('selected').attr('aria-pressed', 'false');
			$('#avto-results-section').fadeOut(300);

			// Re-enable generate button if user image is still loaded
			this.checkGenerateButtonState();

			// Scroll to clothing selection (modal-aware)
			const $gallery = $('.avto-gallery-section');
			if ($gallery.length) {
				this.scrollToElement($gallery, 100);
			}
		},

		/**
		 * Reset all state (for modal close)
		 */
		reset: function() {
			this.userImageFile = null;
			this.selectedClothingId = null;
			this.selectedClothingFile = null;
			this.defaultUserImageId = null;

			$('#avto-user-image').val('');
			$('#avto-image-preview').hide();
			$('.avto-file-label').show();
			$('.avto-default-image-notice').remove();
			$('.avto-clothing-item').removeClass('selected').attr('aria-pressed', 'false');
			$('#avto-results-section').hide();
			$('#avto-generate-btn').prop('disabled', true);
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		// Initialize toast notification system
		AVTOToast.init();
		
		// Initialize modal/shortcode mode
		AVTOModal.init();
	});

})(jQuery);