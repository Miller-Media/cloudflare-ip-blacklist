<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$siteSettings = new CFIP_SiteSettings();
$cfip_settings = $siteSettings->settings;
$credentials = $siteSettings->areCredentialsSet();

// The plugin settings. Add new sections/fields accordingly.
$settings = array(
	__('Cloudflare Credentials', 'ip-blacklist-for-cloudflare') => array(
        'fields' => array(
            array(
                'name' => 'cf_auth_type',
                'type' => 'radio',
                'title' => __('Authentication Method', 'ip-blacklist-for-cloudflare'),
                'description' => '',
                'options' => array(
                    'api_token'  => __('API Token (Recommended)', 'ip-blacklist-for-cloudflare'),
                    'global_key' => __('Global API Key (Legacy)', 'ip-blacklist-for-cloudflare'),
                ),
                'value' => isset($cfip_settings['cf_auth_type']) ? $cfip_settings['cf_auth_type'] : ($credentials ? 'global_key' : 'api_token')
            ),
            array(
                'name' => 'cf_email',
                'type' => 'text',
                'title' => __('Email Address', 'ip-blacklist-for-cloudflare'),
                'description' => __('Your Cloudflare account email address.', 'ip-blacklist-for-cloudflare'),
                'value' => ($credentials && array_key_exists('cf_email', $credentials))? $credentials['cf_email'] : '',
                'auth_group' => 'global_key'
            ),
            array(
                'name' => 'cf_key',
                'type' => 'text',
                'title' => __('Global API Key', 'ip-blacklist-for-cloudflare'),
                'description' => __('Your <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Global API Key</a>.<br />Found under My Profile &rarr; API Tokens &rarr; Global API Key &rarr; View.', 'ip-blacklist-for-cloudflare'),
                'value' => ($credentials && array_key_exists('cf_key', $credentials)) ? $credentials['cf_key'] : '',
                'auth_group' => 'global_key'
            ),
            array(
                'name' => 'cf_token',
                'type' => 'text',
                'title' => __('API Token', 'ip-blacklist-for-cloudflare'),
                'description' => __('A <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">scoped API token</a>.<br />Create one under My Profile &rarr; API Tokens &rarr; Create Token.<br />Required permissions: <strong>Zone &rarr; Zone &rarr; Read</strong>, <strong>Zone &rarr; Firewall Services &rarr; Edit</strong>.', 'ip-blacklist-for-cloudflare'),
                'value' => isset($cfip_settings['cf_token']) ? $cfip_settings['cf_token'] : '',
                'auth_group' => 'api_token'
            ),
        )
	),
	__('Blacklist IPs', 'ip-blacklist-for-cloudflare') => array(
		'fields' => array(
			array(
				'name' => 'enable_ip_blacklist',
				'type' => 'checkbox',
				'title' => __('Enable IP Blacklisting?', 'ip-blacklist-for-cloudflare'),
                'description' => ''
			),
			array(
				'name' => 'banned_usernames',
				'type' => 'textarea',
				'title' => __('Banned Usernames', 'ip-blacklist-for-cloudflare'),
				'description' => __('Comma-separated string, e.g. admin, administrator', 'ip-blacklist-for-cloudflare')
			),
            array(
                'name' => 'enable_logging',
                'type' => 'checkbox',
                'title' => __('Enable Logging?', 'ip-blacklist-for-cloudflare'),
                'description' => ''
            )
		)
	)
);

// Populate options array with banned IP addresses.
$banned_IPs = get_option('cfip_banned_addresses') ?: array();
$banned_IPs_list = array();

if($banned_IPs){
    foreach( $banned_IPs as $ip => $data ){
        $banned_IPs_list[$ip] = $ip;
    }
}

$blacklist_ip_type = count($banned_IPs_list)==0 ? 'span' : 'select';

$blacklist_ip_settings_array = array(
    'name' => 'blacklisted_ips',
    'type' => $blacklist_ip_type,
    'title' => __('Blacklisted IPs', 'ip-blacklist-for-cloudflare'),
    'description' => __('IPs currently blocked from the site', 'ip-blacklist-for-cloudflare')
);

if(count($banned_IPs_list)==0){
    $blacklist_ip_settings_array['value'] = __('No IPs currently blacklisted.', 'ip-blacklist-for-cloudflare');
} else {
    $blacklist_ip_settings_array['options'] = $banned_IPs_list;
}

$settings[__('Blacklist IPs', 'ip-blacklist-for-cloudflare')]['fields'][] = $blacklist_ip_settings_array;

$settings[__('Data Management', 'ip-blacklist-for-cloudflare')] = array(
	'fields' => array(
		array(
			'name' => 'delete_data_on_uninstall',
			'type' => 'checkbox',
			'title' => __('Remove all plugin data when deleted', 'ip-blacklist-for-cloudflare'),
			'description' => __('Check this box if you want all plugin settings and data to be removed when the plugin is deleted.', 'ip-blacklist-for-cloudflare'),
		),
	),
);

/**
 * Render a single field row.
 */
function cfip_render_field($data, $cfip_settings, $banned_IPs, $banned_IPs_list) {
    if ($data['type'] == 'select') {
        ?>
        <tr>
            <td><h3 class="cf-card__title"><label for="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html($data['title']); ?></label></h3>
            </td>
            <td>
                <select id="<?php echo esc_attr($data['name']); ?>"
                        name="<?php echo esc_attr($data['name']); ?>">
                    <?php
                    if ($banned_IPs) {
                        foreach ($banned_IPs_list as $name => $val) {
                            ?>
                            <option name="<?php echo esc_attr($name); ?>"
                                    value="<?php echo esc_attr($val); ?>"><?php echo esc_html($val); ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    } else if ($data['type'] == 'textarea') {
        ?>
        <tr>
            <td><h3 class="cf-card__title"><label
                        for="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html($data['title']); ?>
                    </label></h3>
                <?php
                if (isset($data['description']) && $data['description']) {
                    ?>
                    <span
                        class="cf-card__footer_message"><?php echo wp_kses_post($data['description']); ?></span>
                    <?php
                }
                ?>
            </td>
            <td><textarea id="<?php echo esc_attr($data['name']); ?>"
                          name="<?php echo esc_attr($data['name']); ?>" rows="4"
                          cols="50"><?php echo esc_textarea(array_key_exists($data['name'], $cfip_settings) ? $cfip_settings[$data['name']] : ''); ?></textarea>
            </td>
        </tr>
        <?php
    } else if ($data['type'] == 'checkbox') {
        ?>
        <tr>
            <td><h3 class="cf-card__title"><label
                        for="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html($data['title']); ?>
                    </label></h3>
                <?php
                if (isset($data['description']) && $data['description']) {
                    ?>
                    <span
                        class="cf-card__footer_message"><?php echo wp_kses_post($data['description']); ?></span>
                    <?php
                }
                ?>
            </td>
            <td><input id="<?php echo esc_attr($data['name']); ?>" type="<?php echo esc_attr($data['type']); ?>"
                       name="<?php echo esc_attr($data['name']); ?>" <?php echo(array_key_exists($data['name'], $cfip_settings) && $cfip_settings[$data['name']] == 'on' ? 'checked' : ''); ?>>
            </td>
        </tr>
        <?php
    } else if ($data['type'] == 'span') {
        ?>
        <tr>
            <td><h3 class="cf-card__title"><label
                        for="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html($data['title']); ?>
                    </label></h3>
                <?php
                if (isset($data['description']) && $data['description']) {
                    ?>
                    <span
                        class="cf-card__footer_message"><?php echo wp_kses_post($data['description']); ?></span>
                    <?php
                }
                ?>
            </td>
            <td><span
                    id="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html(array_key_exists($data['name'], $cfip_settings) && $cfip_settings[$data['name']] ? $cfip_settings[$data['name']] : ($data['value'] ?: '')); ?></span>
            </td>
        </tr>
        <?php
    } else if ($data['type'] == 'radio') {
        ?>
        <tr>
            <td colspan="2"><h3 class="cf-card__title"><?php echo esc_html($data['title']); ?></h3>
                <?php foreach ($data['options'] as $val => $label) { ?>
                    <label style="margin-right: 20px;">
                        <input type="radio"
                               name="<?php echo esc_attr($data['name']); ?>"
                               value="<?php echo esc_attr($val); ?>"
                               <?php checked($data['value'], $val); ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php } ?>
            </td>
        </tr>
        <?php
    } else {
        ?>
        <tr<?php if (isset($data['auth_group'])) echo ' class="auth-group-' . esc_attr($data['auth_group']) . '"'; ?>>
            <td colspan="2"><h3 class="cf-card__title"><label
                        for="<?php echo esc_attr($data['name']); ?>"><?php echo esc_html($data['title']); ?>
                    </label></h3>
                <?php
                if (isset($data['description']) && $data['description']) {
                    ?>
                    <span
                        class="cf-card__footer_message"><?php echo wp_kses_post($data['description']); ?></span>
                    <?php
                }
                ?>
                <br />
                <input id="<?php echo esc_attr($data['name']); ?>" type="<?php echo esc_attr($data['type']); ?>"
                       name="<?php echo esc_attr($data['name']); ?>"
                       value="<?php echo esc_attr(array_key_exists($data['name'], $cfip_settings) && $cfip_settings[$data['name']] ? $cfip_settings[$data['name']] : ($data['value'] ?: '')); ?>"
                       style="width: 100%; max-width: 400px;">
            </td>
        </tr>
        <?php
    }
}

?>
<ul class="tabs clearfix" data-tabgroup="first-tab-group">
    <li><a href="#tab1" class="active"><?php esc_html_e('Settings', 'ip-blacklist-for-cloudflare'); ?></a></li>
    <li><a href="#tab2" class="log_tab"><?php esc_html_e('Log', 'ip-blacklist-for-cloudflare'); ?></a></li>
</ul>
<section id="first-tab-group" class="tabgroup">
    <div class="tab-panel" id="tab1">
        <form name="cfip_settings" method="post" action="">
            <table>
                <tbody>
                    <?php
                    $cf_credentials_label = __('Cloudflare Credentials', 'ip-blacklist-for-cloudflare');
                    foreach( $settings as $section=>$fields ){

                        if ($section === $cf_credentials_label) {
                            // Determine which connection state we're in
                            $is_cf_plugin_source = ($credentials && isset($credentials['source']) && $credentials['source'] === 'cf_plugin');
                            $is_manual_connected = ($credentials && isset($credentials['source']) && $credentials['source'] === 'manual');

                            if ($is_cf_plugin_source) {
                                // State A: Credentials from official Cloudflare plugin
                                $cloudflare_admin_url = admin_url('options-general.php?page=cloudflare');
                                ?>
                                <tr>
                                    <td colspan="2">
                                        <div class="cfip-connection-status cfip-connection-status--cf-plugin">
                                            <span class="dashicons dashicons-yes-alt cfip-status-icon"></span>
                                            <span class="cfip-status-text">
                                                <?php
                                                echo wp_kses_post( sprintf(
                                                    /* translators: 1: opening link tag, 2: closing link tag */
                                                    __('Connected via %1$sCloudflare plugin%2$s.', 'ip-blacklist-for-cloudflare'),
                                                    '<a href="' . esc_url($cloudflare_admin_url) . '">',
                                                    '</a>'
                                                ) );
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            } elseif ($is_manual_connected) {
                                // State B: Manual credentials connected — build masked preview
                                $auth_type = isset($cfip_settings['cf_auth_type']) ? $cfip_settings['cf_auth_type'] : 'global_key';
                                if ($auth_type === 'api_token') {
                                    $token = isset($cfip_settings['cf_token']) ? $cfip_settings['cf_token'] : '';
                                    $masked = substr($token, 0, 3) . '...' . substr($token, -4);
                                    $auth_label = __('API Token', 'ip-blacklist-for-cloudflare');
                                    $masked_display = '<code>' . esc_html($masked) . '</code>';
                                } else {
                                    $email = isset($cfip_settings['cf_email']) ? $cfip_settings['cf_email'] : '';
                                    $key = isset($cfip_settings['cf_key']) ? $cfip_settings['cf_key'] : '';
                                    $masked_key = substr($key, 0, 3) . '...' . substr($key, -4);
                                    $auth_label = __('Global API Key (Legacy)', 'ip-blacklist-for-cloudflare');
                                    $masked_display = esc_html($email) . ' / <code>' . esc_html($masked_key) . '</code>';
                                }
                                ?>
                                <tr>
                                    <td colspan="2">
                                        <div class="cfip-connection-status cfip-connection-status--connected">
                                            <span class="dashicons dashicons-yes-alt cfip-status-icon"></span>
                                            <span class="cfip-status-text">
                                                <?php
                                                echo wp_kses_post( sprintf(
                                                    /* translators: %1$s: authentication method name (e.g. "API Token") */
                                                    __('Connected via %1$s', 'ip-blacklist-for-cloudflare'),
                                                    '<strong>' . esc_html($auth_label) . '</strong>'
                                                ) );
                                                ?>
                                                &mdash; <?php echo wp_kses_post( $masked_display ); ?>
                                            </span>
                                            <a href="#" class="cfip-toggle-credentials"><?php esc_html_e('Change Credentials', 'ip-blacklist-for-cloudflare'); ?></a>
                                            <span class="cfip-status-separator">|</span>
                                            <a href="#" class="cfip-disconnect"><?php esc_html_e('Disconnect', 'ip-blacklist-for-cloudflare'); ?></a>
                                        </div>
                                        <div class="cfip-credentials-form" style="display: none;">
                                            <table><tbody>
                                                <?php
                                                foreach ($fields['fields'] as $data) {
                                                    cfip_render_field($data, $cfip_settings, $banned_IPs, $banned_IPs_list);
                                                }
                                                ?>
                                            </tbody></table>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            } else {
                                // State C: Not connected — show prompt + credential form
                                ?>
                                <tr>
                                    <td colspan="2">
                                        <div class="cfip-connect-prompt">
                                            <?php echo wp_kses_post( __( 'Please set your Cloudflare credentials below or install and configure the <a href="http://wordpress.org/plugins/cloudflare/" target="_blank">official Cloudflare plugin</a> to continue.', 'ip-blacklist-for-cloudflare' ) ); ?>
                                        </div>
                                        <table><tbody>
                                            <?php
                                            foreach ($fields['fields'] as $data) {
                                                cfip_render_field($data, $cfip_settings, $banned_IPs, $banned_IPs_list);
                                            }
                                            ?>
                                        </tbody></table>
                                    </td>
                                </tr>
                                <?php
                            }
                            continue;
                        }

                        // Plugin-specific sections — only render if credentials are set
                        if (!$credentials) {
                            continue;
                        }

                        ?>
                        <tr class="tr-section-title">
                            <td><h2 class="cf-heading cf-heading--2"><?php echo esc_html($section); ?></h2></td>
                        </tr>
                        <?php

                        foreach ($fields['fields'] as $data) {
                            cfip_render_field($data, $cfip_settings, $banned_IPs, $banned_IPs_list);
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php wp_nonce_field('cfip_settings_nonce', 'cfip_settings_nonce'); ?>
            <?php submit_button(esc_html__('Save Changes', 'ip-blacklist-for-cloudflare'), 'cf-btn cf-btn--primary'); ?>
        </form>
    </div>
    <div class="tab-panel" id="tab2">
        <h1 class="cf-heading cf-heading--1"><span><?php esc_html_e('Log', 'ip-blacklist-for-cloudflare'); ?></span></h1>
        <?php
        if(!$siteSettings->isLoggingEnabled()) {
            ?>
        <span class="cf-card__footer_message"><?php esc_html_e('Logging is currently disabled. To start logging, enable checkbox on settings tab.', 'ip-blacklist-for-cloudflare'); ?></span>
            <?php
        }
            ?>
        <div id="log_screen">
            <pre></pre>
        </div>
        <button id="clear_log_button" class="button button-primary cf-btn cf-btn--primary"><?php esc_html_e('Clear Log', 'ip-blacklist-for-cloudflare'); ?></button>
        <div id="cfip_ajax_res_clearlog"></div>
    </div>
</section>
<?php
