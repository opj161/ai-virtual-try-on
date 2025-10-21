<?php
/**
 * Uninstall Script
 * 
 * Fired when the plugin is uninstalled via WordPress admin.
 * Cleans up all plugin data from the database.
 *
 * @package AI_Virtual_Try_On
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options from the database
 */
function avto_uninstall_cleanup() {
	// Delete core plugin settings
	delete_option( 'avto_ai_prompt' );
	delete_option( 'avto_aspect_ratio' );
	delete_option( 'avto_max_file_size' );
	delete_option( 'avto_clothing_items' );
	delete_option( 'avto_upload_button_text' );
	delete_option( 'avto_generate_button_text' );
	delete_option( 'avto_show_download_button' );
	delete_option( 'avto_enable_caching' );
	delete_option( 'avto_cache_duration' );
	
	// Delete WooCommerce integration settings (v2.0+)
	delete_option( 'avto_wc_integration_enabled' );
	delete_option( 'avto_wc_display_hook' );
	delete_option( 'avto_wc_hook_priority' );
	delete_option( 'avto_wc_button_text' );
	delete_option( 'avto_wc_target_categories' );
	
	// Delete brand color settings
	delete_option( 'avto_brand_primary_color' );
	delete_option( 'avto_brand_secondary_color' );
	
	// Delete any transients
	delete_transient( 'avto_api_key_notice' );
	
	// Clear any cached data (if caching was enabled)
	global $wpdb;
	$wpdb->query( 
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_avto_' ) . '%'
		)
	);
	$wpdb->query( 
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_timeout_avto_' ) . '%'
		)
	);
	
	// Note: We do NOT delete user-uploaded images or generated try-on results
	// as these are stored in the Media Library and belong to the site owner.
	// Site admins can manually delete them from the Media Library if desired.
}

// Execute cleanup
avto_uninstall_cleanup();
