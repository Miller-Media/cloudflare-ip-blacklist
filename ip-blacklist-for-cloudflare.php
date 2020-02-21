<?php
/**
 * Plugin Name: IP Blacklist for Cloudflare
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username through Cloudflare.
 * Author: Miller Media
 * Author URI:
 * Depends:
 * Version: 1.0.0
 */

define('CFIP_PLUGIN_VERSION', '1.0.0');
define('MAIN_CLOUDFLARE_PLUGIN_DIR', plugins_url().'/cloudflare/');

include_once('classes/Helpers.php');
include_once('classes/Plugin.php');
include_once('classes/SiteSettings.php');
include_once('classes/CloudflareAPIController.php');

$CFIPBlacklist = new CFIPBlacklist();