<?php

if (!defined('ABSPATH')) {
    die('Access denied.');
}

/**
 * Plugin Class
 */
class CFIPBlacklist
{

    /**
     * The settings class.
     *
     * @var CFIPSettings
     */
    public $settingsPage;

    /**
     * The plugin settings.
     *
     * @var mixed
     */
    public $settings;

    /**
     * The API class.
     *
     * @var CloudflareAPIController
     */
    public $API;

    /**
     * Admin stylesheet file.
     *
     */
    public $adminStyle;

    /**
     * Admin javascript file.
     *
     */
    public $adminScript;

    /**
     * CFIPBlacklist constructor.
     *
     * Initialize plugin properties/hooks.
     *
     */
    public function __construct ()
    {
        $this->settingsPage = new CFIPSettings();
        $this->API = new CloudflareAPIController();
        $this->settings = get_site_option('cfip_settings');

        $this->adminStyle = plugins_url('cloudflare-ip-blacklist/assets/css/admin.css', 'cloudflare-ip-blacklist.php');
        $this->adminScript = plugins_url('cloudflare-ip-blacklist/assets/js/admin.js', 'cloudflare-ip-blacklist.php');

        // Hooks
        add_action('admin_enqueue_scripts', array ($this, 'adminEnqueueScripts'), 40, 1);

        // IP Blacklist
        add_action('wp_authenticate', array ($this, 'checkUserLoginName'), 10, 1);

        // Cache Clear on scheduled sale end.
        add_action('wc_after_products_ending_sales', array ($this, 'clearCacheScheduledSaleEnd'), 90, 1);

        // Cache Clear on product out-of-stock.
        add_action('woocommerce_no_stock_notification', array ($this, 'clearCacheProductOutOfStock'), 40, 1);

        // Cache Clear on store notice update.
        add_action('pre_update_option_woocommerce_demo_store_notice', array ($this, 'clearCacheSitewideStoreNoticeUpdated'), 40, 1);

        // AJAX requests
        add_action('wp_ajax_cfip_unblacklist_ip', array ($this, 'ajaxUnblacklistIP'));
        add_action('wp_ajax_cfip_clearlog', array ($this, 'ajaxClearLog'));
	    add_action('wp_ajax_cfip_loadlog', array ($this, 'ajaxLoadLog'));
    }

    /**
     * Enqueue admin scripts and stylesheets.
     *
     * @param $hook string
     */
    public function adminEnqueueScripts ($hook)
    {
        // Only enqueue on appropriate admin screen.
        if ($hook !== 'toplevel_page_cfip-menu')
            return;

        wp_enqueue_style('cfip_admin_style', $this->adminStyle);
        wp_enqueue_script('cfip_admin_script', $this->adminScript, array ('jquery'));
    }

    /**
     * Function that triggers a cache-clear for all product and
     * category endpoints when a scheduled sale ends.
     *
     * @param $product_ids
     */
    public function clearCacheScheduledSaleEnd ($product_ids)
    {
        // If the option is disabled, bail.
        if (!($this->getSetting('after_scheduled_sale') == 'on'))
            return;

        $this->API->purgeCache();
    }

    /**
     * Function that triggers a cache-clear for a product and
     * its category endpoints when it crosses the Out-of-Stock
     * threshold.
     *
     * @param $product WC_Product
     */
    public function clearCacheProductOutOfStock ($product)
    {
        // If the option is disabled, bail.
        if (!($this->getSetting('when_product_out_of_stock') == 'on'))
            return;

        $files = $this->getProductAndCategoryURLs($product);
        $this->API->clearCacheByFiles($files);
    }

    /**
     * Function that triggers a cache-clear for all product and
     * category endpoints when the sitewide store notice is updated.
     *
     * @param $option | string
     * @return string
     */
    public function clearCacheSitewideStoreNoticeUpdated ($option)
    {
        // If the option is disabled, bail.
        if (!($this->getSetting('when_sitewide_notice_updated') == 'on'))
            return $option;

        // Get old value of store notice.
        $previous_notice = get_option('cfip_previous_store_notice', '');

        // If the store notice has not changed, bail.
        if ($option == $previous_notice)
            return $option;

        // Store new notice as previous notice.
        update_option('cfip_previous_store_notice', $option);

        // Clear cache.
	    $this->API->purgeCache();

        return $option;
    }

	/**
	 * Get product and category URLs for given product.
	 *
	 * @param $product WC_Product
	 * @return array
	 */
    public function getProductAndCategoryURLs ($product)
    {
        // Array to hold all product and category URLs
        $files = array ();

        // Add product and category permalinks to $files array, skipping dupes.
        $prod_id = $product->get_id();
        $terms = get_the_terms($prod_id, 'product_cat');
        if ($terms) {
            foreach ($terms as $term) {
                $product_cat_id = $term->term_id;
                $cat_url = get_term_link((int)$product_cat_id, 'product_cat');
                if ($cat_url && !in_array($cat_url, $files))
                    $files[] = $cat_url;
            }
        }

        $product_url = get_permalink($prod_id);
        if ($product_url && !in_array($product_url, $files))
            $files[] = $product_url;

        return $files;
    }

	/**
	 * Get all product and category URLs
	 *
	 * @return array
	 */
    public function getAllProductAndCategoryURLs ()
    {
        // Array to hold all product and category URLs
        $files = array ();

        // Get all published products.
        $args = array (
            "status" => "publish",
            "limit" => -1
        );
        $products = wc_get_products($args);

        // Add product and category permalinks to $files array, skipping dupes.
        foreach ($products as $product) {
	        /**
	         * @var $product WC_Product
	         */
            $prod_id = $product->get_id();
            $terms = get_the_terms($prod_id, 'product_cat');
            if ($terms) {
                foreach ($terms as $term) {
                    $product_cat_id = $term->term_id;
                    $cat_url = get_term_link((int)$product_cat_id, 'product_cat');
                    if ($cat_url && !in_array($cat_url, $files))
                        $files[] = $cat_url;
                }
            }

            $product_url = get_permalink($prod_id);
            if ($product_url && !in_array($product_url, $files))
                $files[] = $product_url;
        }

        return $files;
    }

	/**
	 * Get all URLs for a site, including product and category URLs.
	 *
	 * @return array
	 */
    public function getAllURLs ()
    {
        $urls = $this->getAllProductAndCategoryURLs();

        $posts = new WP_Query('post_type=any&posts_per_page=-1&post_status=publish');
        $posts = $posts->posts;

        foreach ($posts as $post) {
            $permalink = null;
            switch ($post->post_type) {
                case 'revision':
                case 'nav_menu_item':
                case 'attachment':
                case 'product':
                    break;
                case 'page':
                    $permalink = get_page_link($post->ID);
                    break;
                case 'post':
                    $permalink = get_permalink($post->ID);
                    break;
                default:
                    $permalink = get_post_permalink($post->ID);
                    break;
            }
            if ($permalink && !in_array($permalink, $urls)) {
                $urls[] = $permalink;
            }
        }

        return $urls;
    }

    /**
     * Check if login name has been blacklisted.
     *
     * @param $username string
     * @return void
     */
    public function checkUserLoginName ($username)
    {
        // If IP Blacklisting is disabled, bail.
        if (!($this->getSetting('enable_ip_blacklist') == 'on'))
            return;

        $banned_usernames = array_map('trim', explode(',', $this->getSetting('banned_usernames')));
        if (in_array($username, $banned_usernames)) {
            $this->blacklistIPAddress();
        }
    }

    /**
     * Add user's IP address to site option and blacklist it in Cloudflare.
     *
     * @return void
     */
    public function blacklistIPAddress ()
    {
        // Get user's IP address.
        $ip = $this->getUserIPAddress();

        // If an IP address was retrieved, add to site option along with zone/request data and blacklist in Cloudflare.
        if ($ip) {
            $results = $this->API->blacklistIP($ip);
            $ip_data = array (
                $ip => array (
                    'zones' => array ()
                )
            );
            foreach ($results as $zone_id => $result) {
                $ip_data[$ip]['zones'][$zone_id]['request_id'] = $result->result->id;
            }
            $this->updatedBannedIPs($ip_data);
        }
    }

    /**
     * Get a user's IP address.
     *
     * @return string | bool
     */
    public function getUserIPAddress ()
    {
        foreach (array ('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $IP) {
                    $IP = trim($IP);
                    if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $IP;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get banned IP addresses from site option.
     * (found in wp_sitemeta)
     *
     * @return array
     */
    public function getBannedIPs ()
    {
        return (get_site_option('cfip_banned_addresses') ?: array ());
    }

    /**
     * Update site option with IP Address to ban (if it doesn't already exist).
     *
     * @param $ip_data array
     */
    public function updatedBannedIPs ($ip_data)
    {
        $banned_IPs = $this->getBannedIPs();
        foreach ($ip_data as $ip => $data) {
            $banned_IPs[$ip] = $data;
            update_site_option('cfip_banned_addresses', $banned_IPs);
        }
    }

    /**
     * Remove $ip from blacklisted IPs in site option after unblocking
     * in Cloudflare API.
     *
     * @param $ip
     */
    public function removeBannedIP ($ip)
    {
        $banned_IPs = $this->getBannedIPs();
        if (isset($banned_IPs[$ip]))
            unset($banned_IPs[$ip]);

        update_site_option('cfip_banned_addresses', $banned_IPs);
    }

    /**
     * Get domains for each site on the multisite network.
     *
     * @return array|int
     */
    public function getMultisiteDomains ()
    {
        $sites = get_sites();
        $domains = array ();

        // Loop through and get all domains; we'll use each of these in API call to blacklist IP.
        foreach ($sites as $site) {
            $domains[] = $site->domain;
        }

        return $domains;
    }

    /**
     * Remove an IP address from Cloudflare firewall rules.
     *
     */
    public function ajaxUnblacklistIP ()
    {
        $response = array (
            'result' => '',
            'errors' => array ()
        );

        if (!isset($_POST['ip_address']) || !$_POST['ip_address']) {
            $response['errors'][] = array ('error' => 'No IP address provided.');
            wp_die(json_encode($response));
        }

        $ip_address = preg_replace(array ('/\s{2,}/', '/[\t\n]/'), '', $_POST['ip_address']);
        $response['ip'] = $ip_address;

        $banned_IPs = $this->getBannedIPs();
        foreach ($banned_IPs as $ip => $data) {
            if ($ip == $ip_address) {
                foreach ($data['zones'] as $zone_id => $zone_data) {
                	if( !$zone_id )
                		continue;

                    $request_id = $zone_data['request_id'];
                    if( !$request_id )
                    	continue;
                    
                    $r = $this->API->deleteBlacklistedIP($zone_id, $request_id);
                    if (!$r->success && $r->errors) {
                        foreach ($r->errors as $error){
                        	if( !in_array($error->code, array(1003, 7000)) )
	                            $response['errors'][] = array ($error->code => $error->message);
                        }
                    }
                }
            }
        }

        if (!$response['errors']) {
            $this->removeBannedIP($ip_address);
            $response['result'] = 'success';
        }

        wp_die(json_encode($response));
    }

	/**
	 * Load/refresh log via AJAX when log tab is opened.
	 *
	 */
    public function ajaxLoadLog ()
    {
    	$response = array('log' => array());
	    $cfip_log = get_site_option('cfip_log')?:array();
	    if( $cfip_log ){
		    foreach( $cfip_log as $entry ){
		    	ob_start();
			    print_r(PHP_EOL);
		    	print_r($entry);
		    	$response['log'][] = ob_get_clean();
		    }
	    }

	    wp_die(json_encode($response));
    }

	/**
	 * Clear log via AJAX.
	 *
	 */
    public function ajaxClearLog ()
    {
        $response = array ();
        if (!isset($_POST['clear_log']) || !$_POST['clear_log']) {
            $response['error'] = 'An error occurred.';
            wp_die(json_encode($response));
        }

        if (!update_site_option('cfip_log', array ())) {
            $response['error'] = array ('error' => 'Failed to clear log.');
            wp_die(json_encode($response));
        }

        $response['result'] = 'success';
        wp_die(json_encode($response));
    }

    /**
     * Function to log events in a site option.
     *
     * @param $message
     */
    public function log ($message)
    {
        if( $this->getSetting('enable_logging') !== 'on' )
            return;

        $log = get_site_option('cfip_log') ?: array ();
        $log[] = $message;
        update_site_option('cfip_log', $log);
    }

    /**
     * Helper function to get plugin settings.
     *
     * @param $setting
     * @return string|array|null
     */
    public function getSetting ($setting)
    {
        return $this->settings[$setting];
    }
}