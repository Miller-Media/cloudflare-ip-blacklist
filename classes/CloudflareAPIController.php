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

		// Inherit API and Key from Cloudflare plugin if non-existent
        if(empty($this->settings['cf_email'])){$this->settings['cf_email'] = get_site_option('cloudflare_api_email');}
        if(empty($this->settings['cf_key'])){$this->settings['cf_key'] = get_site_option('cloudflare_api_key');}
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
		$this->log("=== REQUEST ===");
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
		if( !$result || ( !property_exists($result, 'success') && ( property_exists($result, 'errors') || property_exists($result, 'error' ) ) ) ) {
			$this->log("=== RESPONSE (Error) ===");
		} else {
			$this->log("=== RESPONSE (Success) ===");
		}
		$this->log($result);

		return $result;
	}

	/**
	 * Blacklist IP in CloudFlare via API call.
	 *
	 * @see      https://api.cloudflare.com/#firewall-access-rule-for-a-zone-create-access-rule
	 *
	 * @param $zone_id, $ip
	 * @return array|mixed|object
	 */
	public function blacklistIP( $zone_id, $ip )
	{
        $url = $this->url . "zones/$zone_id/firewall/access_rules/rules";
        $body = json_encode(array(
            'mode' => 'block',
            'configuration' => array(
                'target' => 'ip',
                'value' => $ip
            ),
            'notes' => 'Blocked for attempting to login using a banned username.'
        ));

        $result = $this->request("POST", $url, $body);
        return $result;
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
	 * Purge all caches.
	 *
	 * @return array|bool
	 */
	public function purgeCache()
	{
		// Get zone ID for this site.
		$zone_id = $this->getZoneId();

		if( !$zone_id )
			return false;

		// Configure endpoint url with zone id.
		$url = $this->url . "zones/$zone_id/purge_cache/";

		// Make request
		$body = json_encode(array(
			"purge_everything" => true
		));
		$result = $this->request("DELETE", $url, $body);

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
			} else if( $count == 0 || $count%30 != 0 ) {
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
     * Get zone ID for current site.
     *
     * @return mixed
     */
    public function getZoneId( $domain )
    {
        $domain = is_multisite() ? $this->getCurrentSiteDomain( $domain ) : get_site_url();

        // Check for saved zone ID and return, if exists. Saves an API call.
        $zone_id = null;
        if( isset( $this->settings['zone_ids'] ) && isset( $this->settings['zone_ids'][$domain] ) ){
            $zone_id = $this->settings['zone_ids'][$domain];
        }

        if( $zone_id )
            return $zone_id;

        // Build URL and make request.
        // @todo Remove:
        $domain='millermedia.io';
        $url = $this->url . "zones?name=$domain";
        $result = $this->request("GET", $url);
        if( !$result || $result->errors || !$result->result ){
            return false;
        }

        // Get zone ID and update settings for future use.
        $zone_id = $result->result[0]->id;
        $settings = get_site_option('cfip_settings');
        $settings['zone_ids'][$domain] = $zone_id;
        update_site_option('cfip_settings', $settings);

        return $zone_id;
    }

	/**
	 * MULTISITE: Get multiple Cloudflare domain zone IDs.
	 *
	 * @return array|mixed|object
	 */
	public function getZoneIDs()
	{
        $domains = $this->getDomains();
		$zone_ids = array();

		foreach( $domains as $domain ){
		    if($zone_id = $this->getZoneID($domain))
		        $zone_ids[] = $zone_id;
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
        $settings = get_site_option('cfip_settings');

        // Only log if logging is enabled
        if ($settings['enable_logging'] == 'on') {
            $log = get_site_option('cfip_log') ?: array();
            $log[] = $message;
            update_site_option('cfip_log', $log);
        }
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
