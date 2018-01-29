<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * CloudflareAPIController Class
 */
class CloudflareAPIController
{

	public $settings;

	/**
	 * The base URL for this API version.
	 *
	 * @var string
	 */
	public $url = "https://api.cloudflare.com/client/v4/";
	
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->settings = get_site_option('cfip_settings');
	}

	/**
	 * Master function to perform a request on the Cloudflare API.
	 *
	 * @param $method | e.g. GET, POST, DELETE
	 * @param $url
	 * @param $body | null or a JSON-encoded array of values
	 * @return array|mixed|object
	 */
	public function request( $method, $url, $body=null )
	{
		// Log request
		$this->log("Timestamp: ".current_time('mysql'));
		$this->log("Method: ".$method);
		$this->log("URL: ".$url);
		if( $body ){
			$this->log(json_decode($body));
		}

		// Get credentials from plugin settings.
		$key = $this->getSetting('cf_key');
		$email = $this->getSetting('cf_email');

		if( !$key || !$email )
			return false;

		// Set headers.
		$headers = array(
			'X-Auth-Email' => $email,
			'X-Auth-Key' => $key,
			'Content-type' => 'application/json'
		);

		// Set args.
		$args = array(
			'method' => $method,
			'headers' => $headers
		);

		// Add body content to args array, if exists.
		if( $body )
			$args['body'] = $body;

		// Handle request method types.
		switch( $method ){
			case 'GET':
				$result = wp_remote_get($url, $args);
				break;
			default:
				$result = wp_remote_post($url, $args);
				break;
		}

		$result = json_decode($result['body']);

		// Handle errors
		if( !$result->success && $result->errors ) {
			$this->log("Response (Error):");
		} else {
			$this->log("Response (Success):");
		}
		$this->log($result);

		return $result;
	}

	/**
	 * Blacklist IP in CloudFlare via API call.
	 *
	 * @see      https://api.cloudflare.com/#firewall-access-rule-for-a-zone-create-access-rule
	 *
	 * @param $ip
	 * @return array|mixed|object
	 */
	public function blacklistIP( $ip )
	{
		$zone_ids = $this->getZoneIDs();
		$results = array();
		foreach( $zone_ids as $zone_id ){
			if( !$zone_id )
				continue;

			$url = $this->url . "zones/$zone_id/firewall/access_rules/rules/";
			$body = json_encode(array(
				'mode' => 'block',
				'configuration' => array(
					'target' => 'ip',
					'value' => $ip
				),
				'notes' => 'Blocked for attempting to login using a banned username.'
			));

			$result = $this->request("POST", $url, $body);
			$results[$zone_id] = $result;
		}

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
	public function deleteBlacklistedIP( $zone_id, $rule_id )
	{
		$url = $this->url . "zones/$zone_id/firewall/access_rules/rules/$rule_id";
		$result = $this->request("DELETE", $url);
		return $result;
	}

	/**
	 * Clear product and category caches.
	 *
	 * @param $files array URLs to clear cache for
	 * @return array|bool
	 */
	public function clearCacheByFiles($files)
	{
		// Get zone ID for this site.
		$zone_id = $this->getZoneId();

		if( !$zone_id )
			return false;

		// Configure endpoint url with zone id.
		$url = $this->url . "zones/$zone_id/purge_cache/";

		// Results array
		$results = array();

		$count = 0;
		$urls = array();

		foreach( $files as $file ){

			if( count($files) == $count+1 ){
				// If this is the last file, add it and make the request.
				$urls[] = $file;

				// Add $urls array to body argument.
				$body = json_encode(array(
					"files" => $urls
				));

				// Make request.
				$result = $this->request("DELETE", $url, $body);
				$results[] = $result;
			} else if( $count%30 != 0 ) {
				// Limit body to 30 URLs per request.
				$urls[] = $file;
				$count++;
			} else {
				// Maximum payload, add $urls array to body argument and make request.
				$body = json_encode(array(
					"files" => $urls
				));

				// Make request.
				$result = $this->request("DELETE", $url, $body);
				$results[] = $result;

				// Empty $urls array.
				$urls = array();
				$count++;
			}
		}

		return $results;
	}

	/**
	 * Get all Cloudflare zones.
	 *
	 * @return array|mixed|object
	 */
	public function getZones()
	{
		$url = $this->url . "zones";
		$result = $this->request("GET", $url);
		return $result;
	}

	/**
	 * Get Cloudflare domain zone IDs.
	 *
	 * @see     https://api.cloudflare.com/#zone-list-zones
	 *
	 * @return array|mixed|object
	 */
	public function getZoneIDs()
	{
		$domains = $this->getDomains();
		$zone_ids = array();
		foreach( $domains as $domain ){
			$url = $this->url . "zones?name=$domain";
			$result = $this->request("GET", $url);
			if( !$result->errors && $result->result )
				$zone_ids[] = $result->result[0]->id;
		}
		return $zone_ids;
	}

	/**
	 * Get zone ID for current site.
	 *
	 * @return mixed
	 */
	public function getZoneId()
	{
		$domain = $this->getCurrentSiteDomain( get_current_blog_id() );
		$url = $this->url . "zones?name=$domain";
		$result = $this->request("GET", $url);
		if( $result->errors || !$result->result ){
			$this->log($result);
			return false;
		}

		return $result->result[0]->id;
	}

	/**
	 * Get access rules for a given zone_id.
	 *
	 * @param $zone_id
	 * @return array|mixed|object
	 */
	public function getZoneAccessRules($zone_id)
	{
		$url = $this->url . "zones/$zone_id/firewall/access_rules/rules?mode=block&scope_type=zone&configuration_target=ip";
		$result = $this->request("GET", $url);
		return json_decode($result['body']);
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

	/**
	 * Helper function to get plugin settings.
	 *
	 * @param $setting
	 * @return string|array|null
	 */
	public function getSetting($setting)
	{
		return $this->settings[$setting];
	}

	/**
	 * Get domains defined in plugin settings.
	 * Domains should not include subdomains or 'www', etc.
	 *
	 * @return array
	 */
	public function getDomains()
	{
		return explode(', ', $this->getSetting('applicable_domains'));
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
	 * Get domain for current site.
	 *
	 * @param $blog_id
	 * @return string
	 */
	public function getCurrentSiteDomain($blog_id)
	{
		$sites = get_sites( array( 'site__in' => array($blog_id) ) );
		$domain = $sites[0]->domain;
		$base_domain = '';
		$applicable_domains = $this->getDomains();

		// Extract base domain (sans any subdomain) according to applicable_domains setting.
		foreach($applicable_domains as $ad){
			if( strpos($domain, $ad) ){
				$base_domain = $ad;
				break;
			}
		}
		return $base_domain;
	}
}
