<?php
/**
 * Plugin Name: IP Blacklist for Cloudflare
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username through Cloudflare.
 * Author: Miller Media
 * Author URI: www.millermedia.io
 * Depends:
 * Version: 1.0.5
 * Requires PHP: 8.1
 * Tested up to: 6.9
 * Text Domain: ip-blacklist-cloudflare
 */

define('CFIP_PLUGIN_VERSION', '1.0.5');

add_action('plugins_loaded', function() {
    load_plugin_textdomain('ip-blacklist-cloudflare', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
define('CFIP_MAIN_CLOUDFLARE_PLUGIN_DIR', plugins_url('cloudflare'));

include_once('classes/Helpers.php');
include_once('classes/Plugin.php');
include_once('classes/SiteSettings.php');
include_once('classes/CloudflareAPIController.php');

$CFIP_Blacklist = new CFIP_Blacklist();