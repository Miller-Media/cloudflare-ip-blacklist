<?php
/**
 * Plugin Class File
 *
 * Created:   December 14, 2017
 *
 * @package:  Cloudflare IP Blacklist
 * @author:   Michael Robinson
 * @since:    {build_version}
 */
namespace MillerMedia\CloudflareIP;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * CloudflareAPIController Class
 */
class CloudflareAPIController
{
	/**
	 * @var 	\Modern\Wordpress\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;

	/**
	 * The base URL for this API version.
	 *
	 * @var string
	 */
	public $url = "https://api.cloudflare.com/client/v4/";
	
	/**
 	 * Get plugin
	 *
	 * @return	\Modern\Wordpress\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * Set plugin
	 *
	 * @param \Modern\Wordpress\Plugin|null $plugin
	 * @return CloudflareAPIController
	 */
	public function setPlugin( \Modern\Wordpress\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin;
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * @param	\Modern\Wordpress\Plugin	$plugin			The plugin to associate this class with, or NULL to auto-associate
	 */
	public function __construct( \Modern\Wordpress\Plugin $plugin=NULL )
	{
		$this->setPlugin( $plugin ?: \MillerMedia\CloudflareIP\Plugin::instance() );
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
		$key = $this->plugin->getSetting('cf_key');
		$email = $this->plugin->getSetting('cf_email');

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
				$this->plugin->log(sprintf("Error code %s: %s", $error->code, $error->message));
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
		$domains = $this->plugin->getMultisiteDomains();
		// $domains = array('trimhealthymembership.com', 'trimhealthymama.com'); // for testing
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
}
