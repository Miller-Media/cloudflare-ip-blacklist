<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Class CFIPSettings
 *
 * Handles setting up the network admin settings menu
 * and screen.
 *
 */
class CFIPSettings
{

	/**
	 * CFIPSettings constructor.
	 *
	 * Runs on instantiation.
	 *
	 */
	public function __construct()
	{
		// Hooks
		add_action('network_admin_menu', array($this, 'addNetworkMenu'));
		add_action('network_admin_menu', array($this, 'verifyNonce'));
	}

	/**
	 * Registers the admin menu page.
	 *
	 */
	public function addNetworkMenu()
	{
		add_menu_page(
			'Cloudflare IP Blacklist',
			'Cloudflare IP Blacklist',
			'manage_options',
			'cfip-menu',
			array($this, 'addNetworkMenuCB')
		);
	}

	/**
	 * Callback for add_menu_page to display the
	 * settings page.
	 *
	 */
	public function addNetworkMenuCB()
	{
		$this->displaySettingsMenu();
	}

	/**
	 * Include the settings menu template.
	 *
	 */
	public function displaySettingsMenu()
	{
		include_once(plugin_dir_path(plugin_dir_path(__FILE__)).'views/settings-view.php');
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
	 * Update site option 'cfip_settings' with $_POST data.
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
			$settings['applicable_domains'] = isset($_POST['applicable_domains']) ? sanitize_text_field($_POST['applicable_domains']) : '';
			$settings['banned_usernames'] = isset($_POST['banned_usernames']) ? sanitize_text_field($_POST['banned_usernames']) : '';

			// Cache Clearing settings
			$settings['after_scheduled_sale'] = isset($_POST['after_scheduled_sale']) ? $_POST['after_scheduled_sale'] : '';
			$settings['when_product_out_of_stock'] = isset($_POST['when_product_out_of_stock']) ? $_POST['when_product_out_of_stock'] : '';
			$settings['when_sitewide_notice_updated'] = isset($_POST['when_sitewide_notice_updated']) ? $_POST['when_sitewide_notice_updated'] : '';

			if( update_site_option('cfip_settings', $settings) )
				return true;

		}

		return false;

	}
		
}