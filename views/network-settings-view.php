<?php

// Get API email and key from CloudFlare plugin settings, if available.
$sites = get_sites();
$cf_key = null;
$cf_email = null;
foreach( $sites as $site ){
	switch_to_blog($site->blog_id);
	$cf_key = get_option('cloudflare_api_key', null);
	$cf_email = get_option('cloudflare_api_email', null);
	restore_current_blog();

	// If we retrieved a key and email, we're done.
	if( $cf_key && $cf_email )
		break;
}

// Populate options array with banned IP addresses.
$banned_IPs = get_site_option('cfip_banned_addresses') ?: array();
$options = array();
if( $banned_IPs ){
	foreach( $banned_IPs as $ip => $data ){
		$options[$ip] = $ip;
	}
}

// Get the existing settings.
$cfip_settings = get_site_option('cfip_settings') ?: array();

// The plugin settings. Add new sections/fields accordingly.
$settings = array(
	'Cloudflare Credentials' => array(
		'fields' => array(
			array(
				'name' => 'cf_email',
				'type' => 'text',
				'title' => 'Email Address',
				'description' => '',
                'value' => $cf_email
			),
			array(
				'name' => 'cf_key',
				'type' => 'text',
				'title' => 'API Key',
				'description' => '',
				'value' => $cf_key
			)
		)
	),
	'Blacklist IPs' => array(
		'fields' => array(
			array(
				'name' => 'enable_ip_blacklist',
				'type' => 'checkbox',
				'title' => 'Enable IP Blacklisting?',
                'description' => ''
			),
			array(
				'name' => 'applicable_domains',
				'type' => 'textarea',
				'title' => 'Applicable Cloudflare Domains',
				'description' => '(omit subdomains, e.g. example.com, website.com, etc)'
			),
			array(
				'name' => 'banned_usernames',
				'type' => 'textarea',
				'title' => 'Banned Usernames',
				'description' => '(a comma-separated string, e.g. admin, administrator, etc)'
			),
			array(
				'name' => 'unblacklist_ip',
				'type' => 'select',
				'title' => 'Unblacklist IP',
				'description' => '',
				'options' => $options?: null
			)
		)
	)
);
?>
<ul class="tabs clearfix" data-tabgroup="first-tab-group">
    <li><a href="#tab1" class="active">Settings</a></li>
    <li><a href="#tab2" class="log_tab">Log</a></li>
</ul>
<section id="first-tab-group" class="tabgroup">
    <div class="tab-panel" id="tab1">
        <form name="cfip_settings" method="post" action="">
            <table>
                <tbody>
                    <?php
                    foreach( $settings as $section=>$fields ){
                        ?>
                        <tr class="tr-section-title">
                            <td><h2><?php echo $section; ?></h2></td>
                        </tr>

                        <?php
                        // If Cloudflare credentials are filled out from Cloudflare plugin
                        if($section=='Cloudflare Credentials' && $this->isCloudflarePluginActive()) {
                            $cloudflare_admin_url = admin_url()."options-general.php?page=cloudflare";
                            ?>
                            <tr>
                                <td>Credentials imported from <a href="<?php echo $cloudflare_admin_url; ?>">Cloudflare</a> plugin.</td>
                            </tr>
                            <?php continue;
                        }

                        foreach( $fields['fields'] as $field=>$data ){
                            if( $data['type'] == 'select' ) {
                                ?>
                                <tr>
                                    <td><label for="<?php echo $data['name']; ?>"><strong><?php echo $data['title']; ?></strong></label></td>
                                    <td>
                                        <select id="<?php echo $data['name']; ?>" name="<?php echo $data['name']; ?>">
                                            <?php
                                            if ($options) {
                                                foreach ($options as $name => $val) {
                                                    ?>
                                                    <option name="<?php echo $name; ?>"
                                                            value="<?php echo $val; ?>"><?php echo $val; ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php
                            } else if( $data['type'] == 'textarea' ) {
                                ?>
                                <tr>
                                    <td><strong><label for="<?php echo $data['name']; ?>"><?php echo $data['title']; ?></strong></label>
                                        <?php
                                        if( isset($data['description']) && $data['description'] ){
                                            ?>
                                            <br><?php echo $data['description']; ?>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td><textarea id="<?php echo $data['name']; ?>" name="<?php echo $data['name']; ?>" rows="4"
                                                  cols="50"><?php echo(array_key_exists($data['name'], $cfip_settings) ? $cfip_settings[$data['name']] : ''); ?></textarea>
                                    </td>
                                </tr>
                                <?php
                            } else if ( $data['type'] == 'checkbox' ) {
                                ?>
                                <tr>
                                    <td><strong><label for="<?php echo $data['name']; ?>"><?php echo $data['title']; ?></strong></label>
                                        <?php
                                        if( isset($data['description']) && $data['description'] ){
                                            ?>
                                            <br><?php echo $data['description']; ?>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td><input id="<?php echo $data['name']; ?>" type="<?php echo $data['type']; ?>" name="<?php echo $data['name']; ?>" <?php echo (array_key_exists($data['name'], $cfip_settings) && $cfip_settings[$data['name']] == 'on' ? 'checked': ''); ?>></td>
                                </tr>
                                <?php
                            } else {
                                ?>
                                <tr>
                                    <td><strong><label for="<?php echo $data['name']; ?>"><?php echo $data['title']; ?></strong></label>
                                        <?php
                                        if( isset($data['description']) && $data['description'] ){
                                            ?>
                                            <br><?php echo $data['description']; ?>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td><input id="<?php echo $data['name']; ?>" type="<?php echo $data['type']; ?>" name="<?php echo $data['name']; ?>" value="<?php echo (array_key_exists($data['name'], $cfip_settings) && $cfip_settings[$data['name']] ? $cfip_settings[$data['name']]:($data['value'] ?: '')); ?>"></td>
                                </tr>
                                <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php wp_nonce_field('cfip_settings_nonce', 'cfip_settings_nonce'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <div class="tab-panel" id="tab2">
        <div id="log_screen">
            <pre></pre>
        </div>
        <button id="clear_log_button" class="button button-primary">Clear Log</button>
        <div id="cfip_ajax_res_clearlog"></div>
    </div>
</section>
<?php
