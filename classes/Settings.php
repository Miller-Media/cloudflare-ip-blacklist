<?php
/**
 * Settings Class File
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
 * Plugin Settings
 *
 * @Wordpress\Options
 * @Wordpress\Options\Section( title="Cloudflare Credentials" )
 * @Wordpress\Options\Field( name="cf_email", type="text", title="Email Address" )
 * @Wordpress\Options\Field( name="cf_key", type="text", title="API Key" )
 * @Wordpress\Options\Section( title="Blacklist IPs" )
 * @Wordpress\Options\Field( name="enable_ip_blacklist", type="checkbox", title="Enable IP Blacklisting?" )
 * @Wordpress\Options\Field( name="banned_usernames", type="textarea", title="Banned Usernames", description="Comma-separated list of usernames.")
 * @Wordpress\Options\Field( name="unblacklist_ip", type="select", title="Unblacklist IP", options="blacklistedIPs" )
 */
class Settings extends \Modern\Wordpress\Plugin\Settings
{
	/**
	 * Instance Cache - Required for singleton
	 *
	 * @var    self
	 */
	protected static $_instance;

	/**
	 * @var string    Settings Access Key ( default: main )
	 */
	public $key = 'main';
	
	/**
	 * Blacklisted IPs
	 * @see: class annotation for unblacklist_ip
	 *
	 * @param		mixed			$currentValue				Current settings value
	 * @return		array
	 */ 
	public function blacklistedIPs( $currentValue )
	{
		$banned_IPs = $this->getPlugin()->getBannedIPs();
		$options = array();
		foreach( $banned_IPs as $ip => $data ){
			$options[$ip] = $ip;
		}
		return $options;
	}
		
}