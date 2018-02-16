<?php
/**
 * Plugin Name: Cloudflare IP Blacklist
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username.
 * Author: Michael Robinson
 * Author URI:
 * Depends:
 * Version: 0.1.9
 */

/** Detect if WooCommerce version is at least 3.3.0 (required for a couple of hooks).
 *
 * @param string $version
 * @return bool
 */
function version_check( $version = '3.3.0' ) {

	// Loop through child sites and find WooCommerce version.
	$sites = get_sites();
	$woo_version = null;
	foreach( $sites as $site ){
		switch_to_blog($site->blog_id);
		$woo_version = get_option('woocommerce_version');
		restore_current_blog();

		// If we retrieved a version number, we're done.
		if( $woo_version )
			break;
	}

	// Compare $woo_version with minimum requirement.
	if ( $woo_version && version_compare( $woo_version, $version, ">=" ) ) {
		return true;
	}

	return false;
}

/**
 * If minimum WooCommerce version is not met, display a notice indicating as much.
 *
 */
function cfip_version_check()
{
	if( !version_check('3.3.0') ){
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php _e("Cloudflare IP Blacklist requires a WooCommerce version of at least 3.3.0 to function correctly. Please update.", 'cfip_blacklist'); ?></p>
		</div>
		<?php
	}
}
add_action('network_admin_notices', 'cfip_version_check');

include_once('classes/Plugin.php');
include_once('classes/Settings.php');
include_once('classes/CloudflareAPIController.php');

$CFIPBlacklist = new CFIPBlacklist();