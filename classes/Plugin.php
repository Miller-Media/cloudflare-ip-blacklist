<?php
/**
 * Plugin Class File
 *
 * @vendor: Miller Media
 * @package: Cloudflare IP Blacklist
 * @author: Michael Robinson
 * @link: 
 * @since: November 28, 2017
 */
namespace MillerMedia\CloudflareIP;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Plugin Class
 */
class Plugin extends \Modern\Wordpress\Plugin
{
	/**
	 * Instance Cache - Required
	 * @var	self
	 */
	protected static $_instance;
	
	/**
	 * @var string		Plugin Name
	 */
	public $name = 'Cloudflare IP Blacklist';
	
	/**
	 * Main Stylesheet
	 *
	 * @Wordpress\Stylesheet
	 */
	public $mainStyle = 'assets/css/style.css';
	
	/**
	 * Main Javascript Controller
	 *
	 * @Wordpress\Script( deps={"mwp"} )
	 */
	public $mainScript = 'assets/js/main.js';
	
	/**
	 * Enqueue scripts and stylesheets
	 * 
	 * @Wordpress\Action( for="wp_enqueue_scripts" )
	 *
	 * @return	void
	 */
	public function enqueueScripts()
	{
		$this->useStyle( $this->mainStyle );
		$this->useScript( $this->mainScript );
	}

	/**
	 * Check if login name has been blacklisted.
	 *
	 * @Wordpress\Action( for="wp_authenticate", priority=10, args=1 )
	 * @param $username string
	 * @return void
	 */
	public function checkUserLoginName( $username )
	{
		$banned_usernames = explode(', ', $this->getSetting('banned_usernames'));
		if( in_array($username, $banned_usernames) ){
			// Get user's IP address.
			$ip = $this->getUserIPAddress();
			if( $ip ){
				// Save IP address to site option with other banned IPs
				// Block IP in cloudflare via api
				// https://api.cloudflare.com/#organization-level-firewall-access-rule-create-access-rule
			}
		}
	}

	/**
	 * Get a user's IP address.
	 *
	 * @return string | bool
	 */
	public function getUserIPAddress()
	{
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
			if (array_key_exists($key, $_SERVER) === true){
				foreach (explode(',', $_SERVER[$key]) as $ip){
					$ip = trim($ip);
					// filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) // for live
					if (filter_var($ip, FILTER_VALIDATE_IP) !== false){
						return $ip;
					}
				}
			}
		}
		return false;
	}
	
}