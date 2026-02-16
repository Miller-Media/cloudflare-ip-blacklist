<?php
/**
 * Uninstall handler for IP Blacklist for Cloudflare.
 *
 * @package IP_Blacklist_Cloudflare
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cfip_settings = get_option( 'cfip_settings' );
$delete_data = is_array( $cfip_settings ) && ! empty( $cfip_settings['delete_data_on_uninstall'] );

if ( ! $delete_data ) {
	return;
}

// Delete plugin options.
delete_option( 'cfip_settings' );
delete_option( 'cfip_banned_addresses' );
delete_option( 'cfip_activated_on' );

// Clean up user meta for review notice dismissals.
global $wpdb;
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'ip-blacklist-cloudflare_review_dismissed' ) );
