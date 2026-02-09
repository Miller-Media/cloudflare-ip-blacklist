<?php
$siteSettings = new CFIP_SiteSettings();
$cfip_settings = $siteSettings->settings;
$credentials = $siteSettings->areCredentialsSet();

// The plugin settings. Add new sections/fields accordingly.
$settings = array(
	__('Cloudflare Credentials', 'ip-blacklist-cloudflare') => array(
        'fields' => array(
            array(
                'name' => 'cf_auth_type',
                'type' => 'radio',
                'title' => __('Authentication Method', 'ip-blacklist-cloudflare'),
                'description' => '',
                'options' => array(
                    'global_key' => __('Global API Key', 'ip-blacklist-cloudflare'),
                    'api_token'  => __('API Token (Recommended)', 'ip-blacklist-cloudflare'),
                ),
                'value' => isset($cfip_settings['cf_auth_type']) ? $cfip_settings['cf_auth_type'] : 'global_key'
            ),
            array(
                'name' => 'cf_email',
                'type' => 'text',
                'title' => __('Email Address', 'ip-blacklist-cloudflare'),
                'description' => __('Your Cloudflare account email address.', 'ip-blacklist-cloudflare'),
                'value' => ($credentials && array_key_exists('cf_email', $credentials))? $credentials['cf_email'] : '',
                'auth_group' => 'global_key'
            ),
            array(
                'name' => 'cf_key',
                'type' => 'text',
                'title' => __('Global API Key', 'ip-blacklist-cloudflare'),
                'description' => __('Your <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Global API Key</a>.<br />Found under My Profile &rarr; API Tokens &rarr; Global API Key &rarr; View.', 'ip-blacklist-cloudflare'),
                'value' => ($credentials && array_key_exists('cf_key', $credentials)) ? $credentials['cf_key'] : '',
                'auth_group' => 'global_key'
            ),
            array(
                'name' => 'cf_token',
                'type' => 'text',
                'title' => __('API Token', 'ip-blacklist-cloudflare'),
                'description' => __('A <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">scoped API token</a>.<br />Create one under My Profile &rarr; API Tokens &rarr; Create Token.', 'ip-blacklist-cloudflare'),
                'value' => isset($cfip_settings['cf_token']) ? $cfip_settings['cf_token'] : '',
                'auth_group' => 'api_token'
            ),
        )
	),
	__('Blacklist IPs', 'ip-blacklist-cloudflare') => array(
		'fields' => array(
			array(
				'name' => 'enable_ip_blacklist',
				'type' => 'checkbox',
				'title' => __('Enable IP Blacklisting?', 'ip-blacklist-cloudflare'),
                'description' => ''
			),
			array(
				'name' => 'banned_usernames',
				'type' => 'textarea',
				'title' => __('Banned Usernames', 'ip-blacklist-cloudflare'),
				'description' => __('Comma-separated string, e.g. admin, administrator', 'ip-blacklist-cloudflare')
			),
            array(
                'name' => 'enable_logging',
                'type' => 'checkbox',
                'title' => __('Enable Logging?', 'ip-blacklist-cloudflare'),
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
    'title' => __('Blacklisted IPs', 'ip-blacklist-cloudflare'),
    'description' => __('IPs currently blocked from the site', 'ip-blacklist-cloudflare')
);

if(count($banned_IPs_list)==0){
    $blacklist_ip_settings_array['value'] = __('No IPs currently blacklisted.', 'ip-blacklist-cloudflare');
} else {
    $blacklist_ip_settings_array['options'] = $banned_IPs_list;
}

$settings[__('Blacklist IPs', 'ip-blacklist-cloudflare')]['fields'][] = $blacklist_ip_settings_array;

?>
<ul class="tabs clearfix" data-tabgroup="first-tab-group">
    <li><a href="#tab1" class="active"><?php esc_html_e('Settings', 'ip-blacklist-cloudflare'); ?></a></li>
    <li><a href="#tab2" class="log_tab"><?php esc_html_e('Log', 'ip-blacklist-cloudflare'); ?></a></li>
</ul>
<section id="first-tab-group" class="tabgroup">
    <div class="tab-panel" id="tab1">
        <form name="cfip_settings" method="post" action="">
            <table>
                <tbody>
                    <?php
                    $cf_credentials_label = __('Cloudflare Credentials', 'ip-blacklist-cloudflare');
                    foreach( $settings as $section=>$fields ){
                        /**
                        * Only display full form if credentials are set
                        */
                        if($section==$cf_credentials_label || ($section!==$cf_credentials_label && $credentials)) {
                            ?>
                            <tr class="tr-section-title">
                                <td><h2 class="cf-heading cf-heading--2"><?php echo esc_html($section); ?></h2></td>
                                <?php
                                if($section==$cf_credentials_label && !$credentials){
                                    echo '<span class="cf-card__footer_message">' . __('Please manually set credentials below or install and configure the <a href="http://wordpress.org/plugins/cloudflare/" target="_blank">official Cloudflare plugin</a> to continue.', 'ip-blacklist-cloudflare') . '</span>';
                                }
                                ?>
                            </tr>

                            <?php
                            // If Cloudflare credentials are filled out from Cloudflare plugin
                            if($section==$cf_credentials_label && $this->isCloudflarePluginActive() && ($credentials && $credentials['source']=='cf_plugin')) {
                                $cloudflare_admin_url = admin_url()."options-general.php?page=cloudflare";
                                ?>
                                <tr>
                                    <td><?php printf(__('Credentials imported from %sCloudflare%s plugin.', 'ip-blacklist-cloudflare'), '<a href="' . esc_url($cloudflare_admin_url) . '">', '</a>'); ?></td>
                                </tr>
                                <?php continue;
                            }


                            foreach ($fields['fields'] as $field => $data) {
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
                                                    class="cf-card__footer_message"><?php echo $data['description']; ?></span>
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
                                                    class="cf-card__footer_message"><?php echo $data['description']; ?></span>
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
                                                    class="cf-card__footer_message"><?php echo $data['description']; ?></span>
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
                                                    class="cf-card__footer_message"><?php echo $data['description']; ?></span>
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
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php wp_nonce_field('cfip_settings_nonce', 'cfip_settings_nonce'); ?>
            <?php submit_button(esc_html__('Save Changes', 'ip-blacklist-cloudflare'), 'cf-btn cf-btn--primary'); ?>
        </form>
    </div>
    <div class="tab-panel" id="tab2">
        <h1 class="cf-heading cf-heading--1"><span><?php esc_html_e('Log', 'ip-blacklist-cloudflare'); ?></span></h1>
        <?php
        if(!$siteSettings->isLoggingEnabled()) {
            ?>
        <span class="cf-card__footer_message"><?php esc_html_e('Logging is currently disabled. To start logging, enable checkbox on settings tab.', 'ip-blacklist-cloudflare'); ?></span>
            <?php
        }
            ?>
        <div id="log_screen">
            <pre></pre>
        </div>
        <button id="clear_log_button" class="button button-primary cf-btn cf-btn--primary"><?php esc_html_e('Clear Log', 'ip-blacklist-cloudflare'); ?></button>
        <div id="cfip_ajax_res_clearlog"></div>
    </div>
</section>
<?php
