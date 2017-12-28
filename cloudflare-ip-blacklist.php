<?php
/**
 * Plugin Name: Cloudflare IP Blacklist
 * Plugin URI:
 * Description: Blacklist IP addresses that attempt to login with a banned username.
 * Author: Michael Robinson
 * Author URI:
 * Depends:
 * Version: 0.1.2
 */

include_once('classes/Plugin.php');
include_once('classes/Settings.php');
include_once('classes/CloudflareAPIController.php');

$CFIPBlacklist = new CFIPBlacklist();