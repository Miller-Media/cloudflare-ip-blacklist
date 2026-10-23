<?php
/**
 * Plugin Name: IP Blacklist for Cloudflare
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username through Cloudflare.
 * Author: Miller Media
 * Author URI: https://mattmiller.ai
 * Depends:
 * Version:           1.2.3
 * Requires PHP: 8.1
 * Tested up to: 7.1
 * Text Domain: ip-blacklist-cloudflare
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CFIP_PLUGIN_VERSION', '1.2.3' );

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

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
	$settings_link = '<a href="' . admin_url('admin.php?page=cfip-menu') . '">' . __('Settings', 'ip-blacklist-cloudflare') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
});

$CFIP_Blacklist = new CFIP_Blacklist();
new CFIP_ReviewNotice( 'IP Blacklist for Cloudflare', 'ip-blacklist-for-cloudflare', 'cfip_activated_on', 'ip-blacklist-cloudflare', plugin_dir_url( __FILE__ ) . 'assets/plugin-icon.jpg' );
