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
			foreach( $result->errors as $error ){
				$this->log(sprintf("Error code %s: %s", $error->code, $error->message));
			}
		}

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
	 * Get Cloudflare domain zone IDs.
	 *
	 * @see     https://api.cloudflare.com/#zone-list-zones
	 *
	 * @return array|mixed|object
	 */
	public function getZoneIDs()
	{
		$domains = $this->getMultisiteDomains();
//		$domains = array('trimhealthymembership.com', 'trimhealthymama.com'); // for testing
		$zone_ids = array();
		foreach( $domains as $domain ){
			$url = $this->url . "zones?name=$domain";
			$result = $this->request("GET", $url);
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
