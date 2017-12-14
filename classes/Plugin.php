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
	 * @var CloudflareAPIController
	 */
	public $API;
	
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
	 * Admin Stylesheet
	 *
	 * @Wordpress\Stylesheet
	 */
	public $adminStyle = 'assets/css/admin.css';

	/**
	 * Main Admin Javascript Controller
	 *
	 * @Wordpress\Script( deps={"jquery"} )
	 */
	public $adminScript = 'assets/js/admin.js';

	/**
	 * Initialize the plugin.
	 *
	 * @Wordpress\Action( for="init" )
	 *
	 * @return void
	 */
	public function init()
	{
		$this->API = new CloudflareAPIController();
	}
	
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
	 * Enqueue admin scripts and stylesheets.
	 *
	 * @Wordpress\Action( for="admin_enqueue_scripts" )
	 */
	public function adminEnqueueScripts()
	{
		$this->useStyle( $this->adminStyle );
		$this->useScript( $this->adminScript );
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
		// If IP Blacklisting is disabled, bail.
		if( !$this->getSetting('enable_ip_blacklist') )
			return;

		$banned_usernames = explode(', ', $this->getSetting('banned_usernames'));
		if( in_array($username, $banned_usernames) ){
			$this->blacklistIPAddress();
		}
	}

	/**
	 * Add user's IP address to site option and blacklist it in Cloudflare.
	 *
	 * @return void
	 */
	public function blacklistIPAddress()
	{
		// Get user's IP address.
		$ip = $this->getUserIPAddress();

		// If an IP address was retrieved, add to site option along with zone/request data and blacklist in Cloudflare.
		if( $ip ){
			$results = $this->API->blacklistIP($ip);
			$ip_data = array(
				$ip => array(
					'zones' => array()
				)
			);
			foreach( $results as $zone_id => $result ){
				$ip_data[$ip]['zones'][$zone_id]['request_id'] = $result->result->id;
			}
			$this->updatedBannedIPs($ip_data);
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
				foreach (explode(',', $_SERVER[$key]) as $IP){
					$IP = trim($IP);
//					if (filter_var($IP, FILTER_VALIDATE_IP) !== false){
//						return $IP;
//					}
					if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
						return $IP;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Get banned IP addresses from site option.
	 * (found in wp_sitemeta)
	 *
	 * @return array
	 */
	public function getBannedIPs()
	{
		return ( get_site_option('cfipbl_banned_addresses') ?: array() );
	}

	/**
	 * Update site option with IP Address to ban (if it doesn't already exist).
	 *
	 * @param $ip_data array
	 */
	public function updatedBannedIPs( $ip_data )
	{
		$banned_IPs = $this->getBannedIPs();
		foreach( $ip_data as $ip => $data ){
			$banned_IPs[$ip] = $data;
			update_site_option('cfipbl_banned_addresses', $banned_IPs);
		}
	}

	/**
	 * Remove $ip from blacklisted IPs in site option after unblocking
	 * in Cloudflare API.
	 *
	 * @param $ip
	 */
	public function removeBannedIP( $ip )
	{
		$banned_IPs = $this->getBannedIPs();
		if( isset($banned_IPs[$ip]) )
			unset($banned_IPs[$ip]);

		update_site_option('cfipbl_banned_addresses', $banned_IPs);
	}

	/**
	 * Get domains for each site on the multisite network.
	 *
	 * @return array|int
	 */
	public function getMultisiteDomains()
	{
		$sites = get_sites();
		$domains = array();
		// Loop through and get all domains; we'll use each of these in API call to blacklist IP.
		foreach( $sites as $site ){
			$domains[] = $site->domain;
		}
		return $domains;
	}

	/**
	 * Remove an IP address from Cloudflare firewall rules.
	 *
	 * @Wordpress\Action( for="wp_ajax_cfip_unblacklist_ip" )
	 */
	public function ajaxUnblacklistIP()
	{
		$ip_address = preg_replace(array('/\s{2,}/', '/[\t\n]/'), '', $_POST['ip_address']);

		$response = array(
			'result' => '',
			'errors' => array(),
			'ip' => $ip_address
		);

		$banned_IPs = $this->getBannedIPs();
		foreach( $banned_IPs as $ip => $data ){
			if( $ip == $ip_address ){
				foreach( $data['zones'] as $zone_id => $zone_data ){
					$request_id = $zone_data['request_id'];
					$r = $this->API->deleteBlacklistedIP($zone_id, $request_id);
					if( !$r->success && $r->errors ) {
						foreach ($r->errors as $error)
							$response['errors'][] = array($error->code => $error->message);
					}
				}
			}
		}

		if( !$response['errors'] ){
			$this->removeBannedIP($ip_address);
			$response['result'] = 'success';
		}

		wp_die(json_encode($response));
	}

	/**
	 * Function to log events in a site option.
	 *
	 * @param $message
	 */
	public function log( $message )
	{
		$log = get_site_option('cfip_log') ?: array();
		$log[] = $message;
		update_site_option('cfip_log', $log);
	}
}