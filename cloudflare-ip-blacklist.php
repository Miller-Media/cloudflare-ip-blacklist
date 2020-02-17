<?php
/**
 * Plugin Name: Cloudflare IP Blacklist
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username.
 * Author: Miller Media
 * Author URI:
 * Depends:
 * Version: 1.0.0
 */

include_once('classes/Plugin.php');

if(is_multisite())
	include_once('classes/NetworkSettings.php');

include_once('classes/SiteSettings.php');
include_once('classes/CloudflareAPIController.php');

$CFIPBlacklist = new CFIPBlacklist();