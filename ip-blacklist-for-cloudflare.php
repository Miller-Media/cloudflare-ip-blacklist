<?php
/**
 * Plugin Name: IP Blacklist for Cloudflare
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username through Cloudflare.
 * Author: Miller Media
 * Author URI: www.millermedia.io
 * Depends:
 * Version: 1.1.1
 * Requires PHP: 8.1
 * Tested up to: 6.9.1
 * Text Domain: ip-blacklist-cloudflare
 */

define('CFIP_PLUGIN_VERSION', '1.1.1');

add_action('plugins_loaded', function() {
    load_plugin_textdomain('ip-blacklist-cloudflare', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
define('CFIP_MAIN_CLOUDFLARE_PLUGIN_DIR', plugins_url('cloudflare'));

include_once('classes/Helpers.php');
include_once('classes/Plugin.php');
include_once('classes/SiteSettings.php');
include_once('classes/CloudflareAPIController.php');
include_once('classes/ReviewNotice.php');

register_activation_hook( __FILE__, function() {
	if ( ! get_option( 'cfip_activated_on' ) ) {
		update_option( 'cfip_activated_on', time() );
	}
});

$CFIP_Blacklist = new CFIP_Blacklist();
new CFIP_ReviewNotice( 'IP Blacklist for Cloudflare', 'ip-blacklist-cloudflare', 'cfip_activated_on', 'ip-blacklist-cloudflare', plugin_dir_url( __FILE__ ) . 'assets/icon-128x128.jpg' );