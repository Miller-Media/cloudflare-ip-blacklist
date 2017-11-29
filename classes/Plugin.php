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
		$IP = $this->getUserIPAddress();

		// If an IP address was retrieved, add to site option and blacklist in Cloudflare.
		if( $IP ){
			$this->updatedBannedIPs($IP);
			$this->blacklistCloudflareIP($IP);
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
					// filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) // for live
					if (filter_var($IP, FILTER_VALIDATE_IP) !== false){
						return $IP;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Get banned IP addresses from site option.
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
	 * @param $IP
	 */
	public function updatedBannedIPs( $IP )
	{
		$banned_IPs = $this->getBannedIPs();
		if( !in_array($IP, $banned_IPs) ){
			$banned_IPs[] = $IP;
			update_site_option('cfipbl_banned_addresses', $banned_IPs);
		}
	}

	/**
	 * Blacklist IP in CloudFlare via API call.
	 *
	 * @see     https://api.cloudflare.com/#firewall-access-rule-for-a-zone-create-access-rule
	 *
	 * @param $IP
	 * @return array|mixed|object
	 */
	public function blacklistCloudflareIP( $IP )
	{
		$zone_ids = $this->getCloudflareZoneIDs();
		$results = array();
		foreach( $zone_ids as $zone_id ){
			$url = "https://api.cloudflare.com/client/v4/zones/$zone_id/firewall/access_rules/rules/";
			$body = json_encode(array(
				'mode' => 'block',
				'configuration' => array(
					'target' => 'ip',
					'value' => '4.3.2.1' // use $IP in production
				),
				'notes' => 'TESTBlocked for attempting to login using a banned usernameTEST.'
			));

			$result = $this->doCloudflareAPICall("POST", $url, $body);
			$results[] = $result;
		}

		var_dump($results);
		return $results;
	}

	/**
	 * Utility function to delete a blacklisted IP rule in Cloudflare.
	 * (Perhaps a text input and button in settings page to accomplish this?)
	 *
	 * @see      https://api.cloudflare.com/#firewall-access-rule-for-a-zone-delete-access-rule
	 *
	 * @param $zone_id
	 * @param $rule_id
	 * @return array|mixed|object
	 */
	public function deleteBlacklistedCloudflareIP( $zone_id, $rule_id )
	{
		$url = "https://api.cloudflare.com/client/v4/zones/$zone_id/firewall/access_rules/rules/$rule_id";
		$result = $this->doCloudflareAPICall("DELETE", $url);
		var_dump($result);
		return $result;
	}

	/**
	 * Get Cloudflare domain zone IDs.
	 *
	 * @see     https://api.cloudflare.com/#zone-list-zones
	 *
	 * @return array|mixed|object
	 */
	public function getCloudflareZoneIDs()
	{
//		$domains = $this->getMultisiteDomains();
		$domains = array('trimhealthymembership.com', 'trimhealthymama.com'); // for testing
		$zone_ids = array();
		foreach( $domains as $domain ){
			$url = "https://api.cloudflare.com/client/v4/zones?name=$domain";
			$result = $this->doCloudflareAPICall("GET", $url);
			$zone_ids[] = $result->result[0]->id;
		}
		return $zone_ids;
	}

	/**
	 * Get access rules for a given zone_id.
	 *
	 * @param $zone_id
	 * @return array|mixed|object
	 */
	public function getZoneAccessRules($zone_id)
	{
		$url = "https://api.cloudflare.com/client/v4/zones/$zone_id/firewall/access_rules/rules?mode=block&scope_type=zone&configuration_target=ip";
		$result = $this->doCloudflareAPICall("GET", $url);
		return json_decode($result['body']);
	}

	/**
	 * Master function to perform a request on the Cloudflare API.
	 *
	 * @param $method | e.g. GET, POST, DELETE
	 * @param $url
	 * @param $body | null or a JSON-encoded array of values
	 * @return array|mixed|object
	 */
	public function doCloudflareAPICall( $method, $url, $body=null )
	{
		$key = $this->getSetting('cf_key');
		$email = $this->getSetting('cf_email');

		$headers = array(
			'X-Auth-Email' => $email,
			'X-Auth-Key' => $key,
			'Content-type' => 'application/json'
		);
		$args = array(
			'method' => $method,
			'headers' => $headers
		);
		if( $body )
			$args['body'] = $body;

		switch( $method ){
			case 'GET':
				$result = wp_remote_get($url, $args);
				break;
			default:
				$result = wp_remote_post($url, $args);
				break;
		}

		// DO ERROR HANDLING HERE

		return json_decode($result['body']);
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
}