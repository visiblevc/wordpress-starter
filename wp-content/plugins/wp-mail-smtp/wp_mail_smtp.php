<?php
/*
Plugin Name: WP-Mail-SMTP
Version: 0.10.1
Plugin URI: http://www.callum-macdonald.com/code/wp-mail-smtp/
Description: Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage the settings.
Author: Callum Macdonald
Author URI: http://www.callum-macdonald.com/
*/

/**
 * @author Callum Macdonald
 * @copyright Callum Macdonald, 2007-11, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * http://www.gnu.org/licenses/gpl.txt
 */

/**
 * Setting options in wp-config.php
 *
 * Specifically aimed at WPMU users, you can set the options for this plugin as
 * constants in wp-config.php. This disables the plugin's admin page and may
 * improve performance very slightly. Copy the code below into wp-config.php.
 */
/*
define('WPMS_ON', true);
define('WPMS_MAIL_FROM', 'From Email');
define('WPMS_MAIL_FROM_NAME', 'From Name');
define('WPMS_MAILER', 'smtp'); // Possible values 'smtp', 'mail', or 'sendmail'
define('WPMS_SET_RETURN_PATH', 'false'); // Sets $phpmailer->Sender if true
define('WPMS_SMTP_HOST', 'localhost'); // The SMTP mail host
define('WPMS_SMTP_PORT', 25); // The SMTP server port number
define('WPMS_SSL', ''); // Possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
define('WPMS_SMTP_AUTH', true); // True turns on SMTP authentication, false turns it off
define('WPMS_SMTP_USER', 'username'); // SMTP authentication username, only used if WPMS_SMTP_AUTH is true
define('WPMS_SMTP_PASS', 'password'); // SMTP authentication password, only used if WPMS_SMTP_AUTH is true
*/

// Array of options and their default values
global $wpms_options; // This is horrible, should be cleaned up at some point
$wpms_options = array (
	'mail_from' => '',
	'mail_from_name' => '',
	'mailer' => 'smtp',
	'mail_set_return_path' => 'false',
	'smtp_host' => 'localhost',
	'smtp_port' => '25',
	'smtp_ssl' => 'none',
	'smtp_auth' => false,
	'smtp_user' => '',
	'smtp_pass' => '',
	'pepipost_user' => '',
	'pepipost_pass' => '',
	'pepipost_port' => '2525',
	'pepipost_ssl' => 'none'
);


/**
 * Activation function. This function creates the required options and defaults.
 */
if (!function_exists('wp_mail_smtp_activate')) :
function wp_mail_smtp_activate() {

	global $wpms_options;

	// Create the required options...
	foreach ($wpms_options as $name => $val) {
		add_option($name,$val);
	}

}
endif;

if (!function_exists('wp_mail_smtp_whitelist_options')) :
function wp_mail_smtp_whitelist_options($whitelist_options) {

	global $wpms_options;

	// Add our options to the array
	$whitelist_options['email'] = array_keys($wpms_options);

	return $whitelist_options;

}
endif;

// To avoid any (very unlikely) clashes, check if the function alredy exists
if (!function_exists('phpmailer_init_smtp')) :
// This code is copied, from wp-includes/pluggable.php as at version 2.2.2
function phpmailer_init_smtp($phpmailer) {

	// If constants are defined, apply those options
	if (defined('WPMS_ON') && WPMS_ON) {

		$phpmailer->Mailer = WPMS_MAILER;

		if (WPMS_SET_RETURN_PATH)
			$phpmailer->Sender = $phpmailer->From;

		if (WPMS_MAILER == 'smtp') {
			$phpmailer->SMTPSecure = WPMS_SSL;
			$phpmailer->Host = WPMS_SMTP_HOST;
			$phpmailer->Port = WPMS_SMTP_PORT;
			if (WPMS_SMTP_AUTH) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = WPMS_SMTP_USER;
				$phpmailer->Password = WPMS_SMTP_PASS;
			}
		}

		// If you're using contstants, set any custom options here
		$phpmailer = apply_filters('wp_mail_smtp_custom_options', $phpmailer);

	}
	else {

		// Check that mailer is not blank, and if mailer=smtp, host is not blank
		if ( ! get_option('mailer') || ( get_option('mailer') == 'smtp' && ! get_option('smtp_host') ) ) {
			return;
		}

    // If the mailer is pepipost, make sure we have a username and password
    if (get_option('mailer') == 'pepipost' && (! get_option('pepipost_user') && ! get_option('pepipost_pass'))) {
      return;
    }

		// Set the mailer type as per config above, this overrides the already called isMail method
		$phpmailer->Mailer = get_option('mailer');

		// Set the Sender (return-path) if required
		if (get_option('mail_set_return_path'))
			$phpmailer->Sender = $phpmailer->From;

		// Set the SMTPSecure value, if set to none, leave this blank
		$phpmailer->SMTPSecure = get_option('smtp_ssl') == 'none' ? '' : get_option('smtp_ssl');

		// If we're sending via SMTP, set the host
		if (get_option('mailer') == "smtp") {

			// Set the SMTPSecure value, if set to none, leave this blank
			$phpmailer->SMTPSecure = get_option('smtp_ssl') == 'none' ? '' : get_option('smtp_ssl');

			// Set the other options
			$phpmailer->Host = get_option('smtp_host');
			$phpmailer->Port = get_option('smtp_port');

			// If we're using smtp auth, set the username & password
			if (get_option('smtp_auth') == "true") {
				$phpmailer->SMTPAuth = TRUE;
				$phpmailer->Username = get_option('smtp_user');
				$phpmailer->Password = get_option('smtp_pass');
			}
		} elseif (get_option('mailer') == 'pepipost') {
      // Set the Pepipost settings
      $phpmailer->Mailer = 'smtp';
      $phpmailer->Host = 'smtp.pepipost.com';
      $phpmailer->Port = get_option('pepipost_port');
      $phpmailer->SMTPSecure = get_option('pepipost_ssl') == 'none' ? '' : get_option('pepipost_ssl');;
      $phpmailer->SMTPAuth = TRUE;
      $phpmailer->Username = get_option('pepipost_user');
      $phpmailer->Password = get_option('pepipost_pass');
    }

		// You can add your own options here, see the phpmailer documentation for more info:
		// http://phpmailer.sourceforge.net/docs/
		$phpmailer = apply_filters('wp_mail_smtp_custom_options', $phpmailer);


		// STOP adding options here.

	}

} // End of phpmailer_init_smtp() function definition
endif;



/**
 * This function outputs the plugin options page.
 */
if (!function_exists('wp_mail_smtp_options_page')) :
// Define the function
function wp_mail_smtp_options_page() {

	// Load the options
	global $wpms_options, $phpmailer;

	// Make sure the PHPMailer class has been instantiated
	// (copied verbatim from wp-includes/pluggable.php)
	// (Re)create it, if it's gone missing
	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$phpmailer = new PHPMailer( true );
	}

	// Send a test mail if necessary
	if (isset($_POST['wpms_action']) && $_POST['wpms_action'] == __('Send Test', 'wp_mail_smtp') && is_email($_POST['to'])) {

		check_admin_referer('test-email');

		// Set up the mail variables
		$to = $_POST['to'];
		$subject = 'WP Mail SMTP: ' . __('Test mail to ', 'wp_mail_smtp') . $to;
		$message = __('This is a test email generated by the WP Mail SMTP WordPress plugin.', 'wp_mail_smtp');

		// Set SMTPDebug to true
		$phpmailer->SMTPDebug = true;

		// Start output buffering to grab smtp debugging output
		ob_start();

		// Send the test mail
		$result = wp_mail($to,$subject,$message);

		// Strip out the language strings which confuse users
		//unset($phpmailer->language);
		// This property became protected in WP 3.2

		// Grab the smtp debugging output
		$smtp_debug = ob_get_clean();

		// Output the response
		?>
<div id="message" class="updated fade"><p><strong><?php _e('Test Message Sent', 'wp_mail_smtp'); ?></strong></p>
<p><?php _e('The result was:', 'wp_mail_smtp'); ?></p>
<pre><?php var_dump($result); ?></pre>
<p><?php _e('The full debugging output is shown below:', 'wp_mail_smtp'); ?></p>
<pre><?php var_dump($phpmailer); ?></pre>
<p><?php _e('The SMTP debugging output is shown below:', 'wp_mail_smtp'); ?></p>
<pre><?php echo $smtp_debug ?></pre>
</div>
		<?php

		// Destroy $phpmailer so it doesn't cause issues later
		unset($phpmailer);

	}

	?>
<div class="wrap">
<h2><?php _e('Advanced Email Options', 'wp_mail_smtp'); ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('email-options'); ?>

<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="mail_from"><?php _e('From Email', 'wp_mail_smtp'); ?></label></th>
<td><input name="mail_from" type="text" id="mail_from" value="<?php print(get_option('mail_from')); ?>" size="40" class="regular-text" />
<p class="description"><?php _e('You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', 'wp_mail_smtp'); if(get_option('db_version') < 6124) { print('<br /><span style="color: red;">'); _e('<strong>Please Note:</strong> You appear to be using a version of WordPress prior to 2.3. Please ignore the From Name field and instead enter Name&lt;email@domain.com&gt; in this field.', 'wp_mail_smtp'); print('</span>'); } ?></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><label for="mail_from_name"><?php _e('From Name', 'wp_mail_smtp'); ?></label></th>
<td><input name="mail_from_name" type="text" id="mail_from_name" value="<?php print(get_option('mail_from_name')); ?>" size="40" class="regular-text" />
<p class="description"><?php _e('You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', 'wp_mail_smtp'); ?></p>
</td>
</tr>
</table>


<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><?php _e('Mailer', 'wp_mail_smtp'); ?> </th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Mailer', 'wp_mail_smtp'); ?></span></legend>
<p><input id="mailer_smtp" class="wpms_mailer" type="radio" name="mailer" value="smtp" <?php checked('smtp', get_option('mailer')); ?> />
<label for="mailer_smtp"><?php _e('Send all WordPress emails via SMTP.', 'wp_mail_smtp'); ?></label></p>
<p><input id="mailer_pepipost" class="wpms_mailer" type="radio" name="mailer" value="pepipost" <?php checked('pepipost', get_option('mailer')); ?> />
<label for="mailer_pepipost"><?php _e('Use Pepipost SMTP to send emails.', 'wp_mail_smtp'); ?></label></p>
<p><input id="mailer_mail" class="wpms_mailer" type="radio" name="mailer" value="mail" <?php checked('mail', get_option('mailer')); ?> />
<label for="mailer_mail"><?php _e('Use the PHP mail() function to send emails.', 'wp_mail_smtp'); ?></label></p>
</fieldset>
<p class="description">Looking for high inbox delivery? Try Pepipost with easy setup and free emails. Learn more <a href="https://app1.pepipost.com/index.php/login/wp_mail_smtp?page=signup&utm_source=WordPress&utm_campaign=Plugins&utm_medium=wp_mail_smtp&utm_term=organic&code=WP-MAIL-SMTP" target="_blank">here</a>.</p>
</td>
</tr>
</table>


<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><?php _e('Return Path', 'wp_mail_smtp'); ?> </th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Return Path', 'wp_mail_smtp'); ?></span></legend><label for="mail_set_return_path">
<input name="mail_set_return_path" type="checkbox" id="mail_set_return_path" value="true" <?php checked('true', get_option('mail_set_return_path')); ?> />
<?php _e('Set the return-path to match the From Email'); ?></label>
</fieldset></td>
</tr>
</table>

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>

<div id="wpms_section_smtp" class="wpms_section">
<h3><?php _e('SMTP Options', 'wp_mail_smtp'); ?></h3>
<p><?php _e('These options only apply if you have chosen to send mail by SMTP above.', 'wp_mail_smtp'); ?></p>

<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="smtp_host"><?php _e('SMTP Host', 'wp_mail_smtp'); ?></label></th>
<td><input name="smtp_host" type="text" id="smtp_host" value="<?php print(get_option('smtp_host')); ?>" size="40" class="regular-text" /></td>
</tr>
<tr valign="top">
<th scope="row"><label for="smtp_port"><?php _e('SMTP Port', 'wp_mail_smtp'); ?></label></th>
<td><input name="smtp_port" type="text" id="smtp_port" value="<?php print(get_option('smtp_port')); ?>" size="6" class="regular-text" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Encryption', 'wp_mail_smtp'); ?> </th>
<td>
  <fieldset>
    <legend class="screen-reader-text"><span><?php _e('Encryption', 'wp_mail_smtp'); ?></span></legend>
<input id="smtp_ssl_none" type="radio" name="smtp_ssl" value="none" <?php checked('none', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_none"><span><?php _e('No encryption.', 'wp_mail_smtp'); ?></span></label><br />
<input id="smtp_ssl_ssl" type="radio" name="smtp_ssl" value="ssl" <?php checked('ssl', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_ssl"><span><?php _e('Use SSL encryption.', 'wp_mail_smtp'); ?></span></label><br />
<input id="smtp_ssl_tls" type="radio" name="smtp_ssl" value="tls" <?php checked('tls', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_tls"><span><?php _e('Use TLS encryption.', 'wp_mail_smtp'); ?></span></label>
<p class="description"><?php esc_html_e('TLS is not the same as STARTTLS. For most servers SSL is the recommended option.'); ?></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Authentication', 'wp_mail_smtp'); ?> </th>
<td>
  <fieldset>
    <legend class="screen-reader-text"><span><?php _e('Authentication', 'wp_mail_smtp'); ?></span></legend>
<input id="smtp_auth_false" type="radio" name="smtp_auth" value="false" <?php checked('false', get_option('smtp_auth')); ?> />
<label for="smtp_auth_false"><span><?php _e('No: Do not use SMTP authentication.', 'wp_mail_smtp'); ?></span></label><br />
<input id="smtp_auth_true" type="radio" name="smtp_auth" value="true" <?php checked('true', get_option('smtp_auth')); ?> />
<label for="smtp_auth_true"><span><?php _e('Yes: Use SMTP authentication.', 'wp_mail_smtp'); ?></span></label><br />
    <p class="description"><?php _e('If this is set to no, the values below are ignored.', 'wp_mail_smtp'); ?></p>
  </fieldset>
</td>
</tr>
<tr valign="top">
<th scope="row"><label for="smtp_user"><?php _e('Username', 'wp_mail_smtp'); ?></label></th>
<td><input name="smtp_user" type="text" id="smtp_user" value="<?php print(get_option('smtp_user')); ?>" size="40" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><label for="smtp_pass"><?php _e('Password', 'wp_mail_smtp'); ?></label></th>
<td>
  <input name="smtp_pass" type="text" id="smtp_pass" value="<?php print(get_option('smtp_pass')); ?>" size="40" class="code" />
  <p class="description"><?php printf(esc_html__('This is in plain text because it must be stored encrypted. For more information, click %1$shere%2$s'), '<a href="">', '</a>'); ?>.</p>
</td>
</tr>
</table>

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
</div><!-- #wpms_section_smtp -->

<div id="wpms_section_pepipost" class="wpms_section">
  <h3><?php _e('Pepipost SMTP Options', 'wp_mail_smtp'); ?></h3>
  <p>You need to signup on <a href="https://app1.pepipost.com/index.php/login/wp_mail_smtp?page=signup&utm_source=WordPress&utm_campaign=Plugins&utm_medium=wp_mail_smtp&utm_term=organic&code=WP-MAIL-SMTP" target="_blank">Pepipost</a> to get the SMTP username/password. Refer this <a href="http://support.pepipost.com/knowledge_base/topics/wp-mail-smtp?utm_source=WordPress&utm_campaign=Plugins&utm_medium=wp_mail_smtp&utm_term=organic" target="_blank">doc</a> for more help.</p>
  <table class="optiontable form-table">
    <tr valign="top">
      <th scope="row"><label for="pepipost_user"><?php _e('Username', 'wp_mail_smtp'); ?></label></th>
      <td><input name="pepipost_user" type="text" id="pepipost_user" value="<?php print(get_option('pepipost_user')); ?>" size="40" class="code" /></td>
    </tr>
    <tr valign="top">
      <th scope="row"><label for="pepipost_pass"><?php _e('Password', 'wp_mail_smtp'); ?></label></th>
      <td><input name="pepipost_pass" type="text" id="pepipost_pass" value="<?php print(get_option('pepipost_pass')); ?>" size="40" class="code" /></td>
    </tr>
		<tr valign="top">
			<th scope="row"><label for="pepipost_port"><?php _e('SMTP Port', 'wp_mail_smtp'); ?></label></th>
			<td><input name="pepipost_port" type="text" id="pepipost_port" value="<?php print(get_option('pepipost_port')); ?>" size="6" class="regular-text" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Encryption', 'wp_mail_smtp'); ?> </th>
			<td>
			  <fieldset>
			    <legend class="screen-reader-text">
						<span>
							<?php _e('Encryption', 'wp_mail_smtp'); ?>
						</span>
					</legend>
				<input id="pepipost_ssl_none" type="radio" name="pepipost_ssl" value="none" <?php checked('none', get_option('pepipost_ssl')); ?> />
				<label for="pepipost_ssl_none"><span><?php _e('No encryption.', 'wp_mail_smtp'); ?></span></label><br />
				<input id="pepipost_ssl_ssl" type="radio" name="pepipost_ssl" value="ssl" <?php checked('ssl', get_option('pepipost_ssl')); ?> />
				<label for="pepipost_ssl_ssl"><span><?php _e('Use SSL encryption.', 'wp_mail_smtp'); ?></span></label><br />
				<input id="pepipost_ssl_tls" type="radio" name="pepipost_ssl" value="tls" <?php checked('tls', get_option('pepipost_ssl')); ?> />
				<label for="pepipost_ssl_tls"><span><?php _e('Use TLS encryption.', 'wp_mail_smtp'); ?></span></label>
			</td>
		</tr>
  </table>

  <p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
</div><!-- #wpms_section_pepipost -->


<input type="hidden" name="action" value="update" />
</p>
<input type="hidden" name="option_page" value="email">
</form>

<h3><?php _e('Send a Test Email', 'wp_mail_smtp'); ?></h3>

<form method="POST" action="options-general.php?page=<?php echo plugin_basename(__FILE__); ?>">
<?php wp_nonce_field('test-email'); ?>
<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="to"><?php _e('To', 'wp_mail_smtp'); ?></label></th>
<td><input name="to" type="text" id="to" value="" size="40" class="code" />
<p class="description"><?php _e('Type an email address here and then click Send Test to generate a test email.', 'wp_mail_smtp'); ?></p>
</td>
</tr>
</table>
<p class="submit"><input type="submit" name="wpms_action" id="wpms_action" class="button-primary" value="<?php _e('Send Test', 'wp_mail_smtp'); ?>" /></p>
</form>

<script type="text/javascript">
  var wpmsOnMailerChange = function(mailer) {
    // Hide all the mailer forms
    jQuery('.wpms_section').hide()
    // Show the target mailer form
    jQuery('#wpms_section_' + mailer).show()
  }
  jQuery(document).ready(function(){
    // Call wpmsOnMailerChange() on startup with the current mailer
    wpmsOnMailerChange(jQuery('input.wpms_mailer:checked').val())

    // Watch the mailer for any changes
    jQuery('input.wpms_mailer').on('change', function(e) {
      // Call the wpmsOnMailerChange() handler, passing the value of the newly
      // selected mailer
      wpmsOnMailerChange(jQuery(e.target).val())
    })
  })
</script>

</div>
	<?php

} // End of wp_mail_smtp_options_page() function definition
endif;


/**
 * This function adds the required page (only 1 at the moment).
 */
if (!function_exists('wp_mail_smtp_menus')) :
function wp_mail_smtp_menus() {

	if (function_exists('add_submenu_page')) {
		add_options_page(__('Advanced Email Options', 'wp_mail_smtp'),__('Email', 'wp_mail_smtp'),'manage_options',__FILE__,'wp_mail_smtp_options_page');
	}

} // End of wp_mail_smtp_menus() function definition
endif;


/**
 * This function sets the from email value
 */
if (!function_exists('wp_mail_smtp_mail_from')) :
function wp_mail_smtp_mail_from ($orig) {

	// This is copied from pluggable.php lines 348-354 as at revision 10150
	// http://trac.wordpress.org/browser/branches/2.7/wp-includes/pluggable.php#L348

	// Get the site domain and get rid of www.
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}

	$default_from = 'wordpress@' . $sitename;
	// End of copied code

	// If the from email is not the default, return it unchanged
	if ( $orig != $default_from ) {
		return $orig;
	}

	if (defined('WPMS_ON') && WPMS_ON) {
		if (defined('WPMS_MAIL_FROM') && WPMS_MAIL_FROM != false)
			return WPMS_MAIL_FROM;
	}
	elseif (is_email(get_option('mail_from'), false))
		return get_option('mail_from');

	// If in doubt, return the original value
	return $orig;

} // End of wp_mail_smtp_mail_from() function definition
endif;


/**
 * This function sets the from name value
 */
if (!function_exists('wp_mail_smtp_mail_from_name')) :
function wp_mail_smtp_mail_from_name ($orig) {

	// Only filter if the from name is the default
	if ($orig == 'WordPress') {
		if (defined('WPMS_ON') && WPMS_ON) {
			if (defined('WPMS_MAIL_FROM_NAME') && WPMS_MAIL_FROM_NAME != false)
				return WPMS_MAIL_FROM_NAME;
		}
		elseif ( get_option('mail_from_name') != "" && is_string(get_option('mail_from_name')) )
			return get_option('mail_from_name');
	}

	// If in doubt, return the original value
	return $orig;

} // End of wp_mail_smtp_mail_from_name() function definition
endif;

function wp_mail_plugin_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __( 'Settings', 'wp_mail_smtp' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

// Add an action on phpmailer_init
add_action('phpmailer_init','phpmailer_init_smtp');

if (!defined('WPMS_ON') || !WPMS_ON) {
	// Whitelist our options
	add_filter('whitelist_options', 'wp_mail_smtp_whitelist_options');
	// Add the create pages options
	add_action('admin_menu','wp_mail_smtp_menus');
	// Add an activation hook for this plugin
	register_activation_hook(__FILE__,'wp_mail_smtp_activate');
	// Adds "Settings" link to the plugin action page
	add_filter( 'plugin_action_links', 'wp_mail_plugin_action_links',10,2);
}

// Add filters to replace the mail from name and emailaddress
add_filter('wp_mail_from','wp_mail_smtp_mail_from');
add_filter('wp_mail_from_name','wp_mail_smtp_mail_from_name');

load_plugin_textdomain('wp_mail_smtp', false, dirname(plugin_basename(__FILE__)) . '/langs');

?>
