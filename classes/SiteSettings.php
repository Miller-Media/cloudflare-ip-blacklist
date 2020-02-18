<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Class CFIPSiteSettings
 *
 * Handles setting up the admin settings menus
 * and screens.
 *
 */
class CFIPSiteSettings
{

	/**
	 * CFIPSiteSettings constructor.
	 *
	 * Runs on instantiation.
	 *
	 */
	public function __construct()
	{
		// Hooks
		// Single Site Settings Screen
		add_action('admin_menu', array($this, 'addSiteMenu'));
		add_action('admin_menu', array($this, 'verifyNonce'));
	}

	public function isCloudflarePluginActive(){
		return is_plugin_active( 'cloudflare/cloudflare.php' );
	}
	
	public function areCloudflareCredentialsSet(){
		if(!empty(get_option('cloudflare_api_key', null)) && !empty(get_option('cloudflare_api_email', null)))
			return array('cf_key'=>get_option('cloudflare_api_key', null),'cf_email'=>get_option('cloudflare_api_email', null));

		return false;
	}

	/**
	 * Registers the network admin menu page.
	 *
	 */
	public function addSiteMenu()
	{
		add_menu_page(
			'Cloudflare IP Blacklist',
			'Cloudflare IP Blacklist',
			'manage_options',
			'cfip-menu',
			array($this, 'addSiteMenuCB'),
			plugin_dir_url(plugin_dir_path(__FILE__)).'assets/media/cf-facebook-card.png'
		);
	}

	/**
	 * Callback for add_menu_page to display the
	 * settings page.
	 *
	 */
	public function addSiteMenuCB()
	{
		$this->displaySiteSettingsMenu();
	}

	/**
	 * Include the settings menu template.
	 *
	 */
	public function displaySiteSettingsMenu()
	{
		include_once(plugin_dir_path(plugin_dir_path(__FILE__)).'views/site-settings-view.php');
	}

	/**
	 * Verify nonce on form submit, then update settings.
	 *
	 */
	public function verifyNonce() {
		if ( isset($_POST['submit']) ) {

			// Bail if nonce not set.
			if ( !isset( $_POST['cfip_settings_nonce'] ) )
				return false;

			// Verify nonce.
			if ( @!wp_verify_nonce($_POST['cfip_settings_nonce'], 'cfip_settings_nonce') )
				return false;

			return $this->updateSettings();
		}
		return false;
	}

	/**
	 * Update option 'cfip_settings' with $_POST data.
	 *
	 * @return bool
	 */
	public function updateSettings() {
		$settings = array();

		if (isset($_POST['cfip_settings_nonce'])) {

			// Cloudflare Credentials
			$settings['cf_email'] = isset($_POST['cf_email']) ? sanitize_text_field($_POST['cf_email']) : '';
			$settings['cf_key'] = isset($_POST['cf_key']) ?sanitize_text_field($_POST['cf_key']) : '';

			// IP Blacklist Settings
			$settings['enable_ip_blacklist'] = isset($_POST['enable_ip_blacklist']) ? $_POST['enable_ip_blacklist'] : '';
            $settings['enable_logging'] = isset($_POST['enable_logging']) ? $_POST['enable_logging'] : '';
			$settings['applicable_domains'] = isset($_POST['applicable_domains']) ? sanitize_text_field($_POST['applicable_domains']) : '';
			$settings['banned_usernames'] = isset($_POST['banned_usernames']) ? sanitize_text_field($_POST['banned_usernames']) : '';
			$settings['zone_ids'] = array();

			if( update_option('cfip_settings', $settings) )
				return true;

		}

		return false;

	}
		
}