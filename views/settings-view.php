<?php

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
				'description' => ''
			),
			array(
				'name' => 'cf_key',
				'type' => 'text',
				'title' => 'API Key',
				'description' => ''
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
				'name' => 'banned_usernames',
				'type' => 'textarea',
				'title' => 'Banned Usernames',
				'description' => '(a comma-separated string, e.g. admin, trimhealthy, etc)'
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
<form name="cfip_settings" method="post" action="">
	<table>
		<tbody>
			<?php
			foreach( $settings as $section=>$fields ){
				?>
				<tr>
					<td><h2><?php echo $section; ?></h2></td>
				</tr>
				<?php
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
							<td><input id="<?php echo $data['name']; ?>" type="<?php echo $data['type']; ?>" name="<?php echo $data['name']; ?>" value="<?php echo (array_key_exists($data['name'], $cfip_settings)?$cfip_settings[$data['name']]: ''); ?>"></td>
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
<?php
