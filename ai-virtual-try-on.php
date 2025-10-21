<?php
/**
 * Plugin Name:       AI Virtual Try-On
 * Plugin URI:        https://github.com/yourusername/ai-virtual-try-on
 * Description:       AI-powered virtual try-on experience using Google's Gemini 2.5 Flash Image API. WooCommerce integration for seamless product page try-ons. Supports JPEG, PNG, WebP, HEIC, and HEIF formats. Fully customizable via admin settings.
 * Version:           2.2.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       avto
 * Domain Path:       /languages
 * 
 * WC requires at least: 5.0
 * WC tested up to:      9.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants
 */
define( 'AVTO_VERSION', '2.2.2' );
define( 'AVTO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AVTO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AVTO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Configuration Constants (can be overridden via filters)
 */
if ( ! defined( 'AVTO_MAX_FILE_SIZE_MB' ) ) {
	define( 'AVTO_MAX_FILE_SIZE_MB', 5 );
}

if ( ! defined( 'AVTO_AJAX_TIMEOUT' ) ) {
	define( 'AVTO_AJAX_TIMEOUT', 60 );
}

if ( ! defined( 'AVTO_ALLOWED_MIME_TYPES' ) ) {
	define( 'AVTO_ALLOWED_MIME_TYPES', 'image/jpeg,image/png,image/webp,image/heic,image/heif' );
}

/**
 * Declare WooCommerce HPOS compatibility
 * Required for WooCommerce 8.0+
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

/**
 * Plugin Activation Hook
 * 
 * Runs when the plugin is activated.
 */
function avto_activate() {
	// Check PHP version
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( AVTO_PLUGIN_BASENAME );
		wp_die(
			__( 'AI Virtual Try-On requires PHP version 7.4 or higher.', 'avto' ),
			__( 'Plugin Activation Error', 'avto' ),
			array( 'back_link' => true )
		);
	}

	// Check and run upgrade routine if needed
	$installed_version = get_option( 'avto_version', '0.0.0' );
	
	if ( version_compare( $installed_version, AVTO_VERSION, '<' ) ) {
		avto_upgrade_routine( $installed_version );
		update_option( 'avto_version', AVTO_VERSION );
	}

	// Check if API key is configured
	if ( ! defined( 'AVTO_GEMINI_API_KEY' ) ) {
		// Set a transient to show admin notice
		set_transient( 'avto_api_key_notice', true, 60 );
	}

	// Flush rewrite rules (for future enhancements)
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'avto_activate' );

/**
 * Upgrade routine - handles data migration between versions
 *
 * @param string $from_version The version being upgraded from.
 */
function avto_upgrade_routine( $from_version ) {
	// Upgrade to 2.2.0 - Set default caching values if not set
	if ( version_compare( $from_version, '2.2.0', '<' ) ) {
		if ( false === get_option( 'avto_enable_caching' ) ) {
			add_option( 'avto_enable_caching', false );
		}
		if ( false === get_option( 'avto_cache_duration' ) ) {
			add_option( 'avto_cache_duration', 86400 );
		}
	}
	
	// Future upgrades can add more version checks here
	// Example:
	// if ( version_compare( $from_version, '3.0.0', '<' ) ) {
	//     // Migration logic for 3.0.0
	// }
	
	// Set upgrade notice transient
	set_transient( 'avto_upgraded_notice', AVTO_VERSION, 60 );
}

/**
 * Plugin Deactivation Hook
 * 
 * Runs when the plugin is deactivated.
 */
function avto_deactivate() {
	// Clean up any temporary data
	delete_transient( 'avto_api_key_notice' );
	
	// Flush rewrite rules
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'avto_deactivate' );

/**
 * Comprehensive Admin Notice System
 */
function avto_admin_notices() {
	// 1. API Key Missing Notice
	if ( get_transient( 'avto_api_key_notice' ) ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'AI Virtual Try-On:', 'avto' ); ?></strong>
				<?php esc_html_e( 'Please add your Gemini API key to wp-config.php:', 'avto' ); ?>
				<code>define( 'AVTO_GEMINI_API_KEY', 'your-api-key-here' );</code>
			</p>
		</div>
		<?php
		delete_transient( 'avto_api_key_notice' );
	}

	// 2. Upgrade Success Notice
	if ( $upgraded_version = get_transient( 'avto_upgraded_notice' ) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'AI Virtual Try-On:', 'avto' ); ?></strong>
				<?php
				printf(
					/* translators: %s: version number */
					esc_html__( 'Successfully upgraded to version %s!', 'avto' ),
					esc_html( $upgraded_version )
				);
				?>
			</p>
		</div>
		<?php
		delete_transient( 'avto_upgraded_notice' );
	}

	// 3. WooCommerce Inactive Notice (only on plugin settings page)
	$screen = get_current_screen();
	if ( $screen && $screen->id === 'settings_page_avto' ) {
		$wc_enabled = get_option( 'avto_wc_integration_enabled', false );
		if ( $wc_enabled && ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'AI Virtual Try-On:', 'avto' ); ?></strong>
					<?php esc_html_e( 'WooCommerce integration is enabled but WooCommerce is not active. Please install and activate WooCommerce or disable the integration.', 'avto' ); ?>
				</p>
			</div>
			<?php
		}
	}

	// 4. PHP Deprecation Warning (PHP < 7.4)
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'AI Virtual Try-On:', 'avto' ); ?></strong>
				<?php
				printf(
					/* translators: %s: current PHP version */
					esc_html__( 'Your site is running PHP %s. Future versions of this plugin will require PHP 8.0 or higher. Please contact your hosting provider to upgrade.', 'avto' ),
					esc_html( PHP_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	// 5. Rate Limit Exceeded Warning (temporary notice)
	if ( get_transient( 'avto_rate_limit_warning' ) ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'AI Virtual Try-On:', 'avto' ); ?></strong>
				<?php esc_html_e( 'Multiple rate limit violations detected. Consider adjusting rate limit settings or monitoring user activity.', 'avto' ); ?>
			</p>
		</div>
		<?php
		delete_transient( 'avto_rate_limit_warning' );
	}
}
add_action( 'admin_notices', 'avto_admin_notices' );

/**
 * Enqueue Frontend Assets
 * 
 * Conditionally loads CSS and JavaScript for:
 * 1. Shortcode mode (backward compatibility)
 * 2. WooCommerce product pages (when integration enabled)
 */
function avto_enqueue_frontend_assets() {
	global $post;
	
	$should_load = false;

	// Check for shortcode presence (original functionality)
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ai_virtual_tryon' ) ) {
		$should_load = true;
	}

	// Check for WooCommerce product page (new functionality)
	if ( function_exists( 'is_product' ) && is_product() ) {
		$wc_enabled = get_option( 'avto_wc_integration_enabled', false );
		
		if ( $wc_enabled ) {
			// Additional check: is this product in a targeted category?
			$target_categories = get_option( 'avto_wc_target_categories', array() );
			
			if ( empty( $target_categories ) ) {
				// No categories specified, show on all products
				$should_load = true;
			} else {
				// Check if current product is in target categories
				global $product;
				if ( is_a( $product, 'WC_Product' ) ) {
					$product_categories = $product->get_category_ids();
					$is_targeted = ! empty( array_intersect( $product_categories, $target_categories ) );
					
					if ( $is_targeted ) {
						$should_load = true;
					}
				}
			}
		}
	}

	if ( ! $should_load ) {
		return;
	}
	
	// Debug logging (remove after testing)
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AVTO: Enqueuing frontend assets. is_product: ' . ( is_product() ? 'yes' : 'no' ) );
	}
	
	// Enqueue CSS
	wp_enqueue_style(
		'avto-frontend-style',
		AVTO_PLUGIN_URL . 'assets/css/avto-frontend.css',
		array(),
		AVTO_VERSION
	);

	// Enqueue JavaScript
	wp_enqueue_script(
		'avto-frontend-script',
		AVTO_PLUGIN_URL . 'assets/js/avto-frontend.js',
		array( 'jquery' ),
		AVTO_VERSION,
		true
	);

	// Localize script with AJAX URL and nonce (for shortcode mode)
	wp_localize_script(
		'avto-frontend-script',
		'avtoAjax',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'avto-generate-image-nonce' ),
			'strings' => array(
				'generating'     => __( 'Generating your virtual try-on...', 'avto' ),
				'success'        => __( 'Success! Here is your result:', 'avto' ),
				'error'          => __( 'An error occurred. Please try again.', 'avto' ),
				'selectImage'    => __( 'Please upload your photo first.', 'avto' ),
				'selectClothing' => __( 'Please select a clothing item.', 'avto' ),
				'fileTooLarge'   => __( 'File size must be less than 5MB.', 'avto' ),
				'invalidType'    => __( 'Please upload a JPG or PNG image.', 'avto' ),
			)
		)
	);

}
add_action( 'wp_enqueue_scripts', 'avto_enqueue_frontend_assets' );

/**
 * Localize WooCommerce product data for frontend script.
 * Runs on a later hook to ensure product object is available.
 *
 * @since 2.2.2
 */
function avto_localize_product_data() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AVTO: avto_localize_product_data() called' );
	}
	
	// Only run on product pages with WC integration enabled
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AVTO: Not a product page, exiting localization' );
		}
		return;
	}
	
	$wc_enabled = get_option( 'avto_wc_integration_enabled', false );
	if ( ! $wc_enabled ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AVTO: WC integration not enabled, exiting localization' );
		}
		return;
	}
	
	// Get product object
	global $product;
	if ( ! $product ) {
		$product = wc_get_product( get_the_ID() );
	}
	
	if ( ! is_a( $product, 'WC_Product' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AVTO: Product object not available on product page ID ' . get_the_ID() );
		}
		return;
	}
	
	// Check category filtering
	$target_categories = get_option( 'avto_wc_target_categories', array() );
	if ( ! empty( $target_categories ) ) {
		$product_categories = $product->get_category_ids();
		$is_targeted = ! empty( array_intersect( $product_categories, $target_categories ) );
		
		if ( ! $is_targeted ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AVTO: Product not in target categories, exiting localization' );
			}
			return;
		}
	}
	
	// Debug logging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AVTO: SUCCESS - Localizing product data for product ID ' . $product->get_id() );
	}
	
	// Localize product data
	wp_localize_script(
		'avto-frontend-script',
		'avtoProductData',
		array(
			'productId'    => (int) $product->get_id(),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'avto-product-images-nonce' ),
			'fetchViaAjax' => true,
		)
	);
}
add_action( 'wp_footer', 'avto_localize_product_data', 5 ); // Priority 5 to run early in footer

/**
 * Include Required Files
 */
require_once AVTO_PLUGIN_DIR . 'includes/avto-shortcode.php';
require_once AVTO_PLUGIN_DIR . 'includes/avto-ajax-handler.php';
require_once AVTO_PLUGIN_DIR . 'includes/avto-admin.php';

// WooCommerce integration (only load if WooCommerce exists)
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	require_once AVTO_PLUGIN_DIR . 'includes/avto-woocommerce.php';
}
