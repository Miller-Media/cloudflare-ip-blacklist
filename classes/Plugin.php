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
	
}