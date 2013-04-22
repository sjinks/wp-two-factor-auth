<?php
/*
Plugin Name: Two Factor Auth
Plugin URI: http://oskarhane.com/plugin-two-factor-auth-for-wordpress
Description: Add extra security to your WordPress login with this two factor auth. Users will be prompted with a page to enter a code that was emailed to them.
Author: Oskar Hane
Author URI: http://oskarhane.com
Version: 1.1
License: GPLv2 or later
*/


function breakAuth($log)
{
	$params = $_POST;
	
	if(!$params)
		return;
	if(!$params['log'])
		return;
	
	if(!$params['two_factor_code_submitted'])
		loadTwoFactorForm($params);
	else
		checkTwoFactorCode($params);
}
add_action('wp_authenticate', 'breakAuth');


function loadTwoFactorForm($params)
{
	global $wpdb;
	$query = $wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE user_login=%s", $params['log']);
	$user = $wpdb->get_row($query);
		
	$code = generateTwoFactorCode();
	
	//Render form anyway so we don't reveal if the username exists or not
	if($user)
	{
		delete_user_meta($user->ID, 'two_factor_login_code');
		add_user_meta($user->ID, 'two_factor_login_code', $code);
		sendTwoFactorEmail($user->user_email, $code);
	}
	
	include 'form.php';
	
	
	exit;
}


function hideAndEmptyPasswordField()
{
	if($_POST['log'] && !$_POST['two_factor_code_submitted'])
		return;
	
	?>
	<script type="text/javascript">
		var pw_field = document.getElementsByName('pwd')[0];
		pw_field.value = '';
		pw_field.parentNode.parentNode.style.display = 'none';
	</script>
	<?php
}
add_action('login_footer', 'hideAndEmptyPasswordField');



function checkTwoFactorCode($params)
{
	global $wpdb;
	$query = $wpdb->prepare("SELECT ID from ".$wpdb->users." WHERE user_login=%s", $params['log']);
	$user_ID = $wpdb->get_var($query);
	
	$code = get_user_meta($user_ID, 'two_factor_login_code', true);
	
	//Remove code after one guess
	delete_user_meta($user_ID, 'two_factor_login_code');
	
	if(!$code)
		sendBackToLogin();

	if($code != strtoupper($params['two_factor_code']))
		sendBackToLogin();
}


function sendTwoFactorEmail($email, $code)
{
	wp_mail( $email, 'Login Code for '.get_bloginfo('name'), "\n\nEnter this code to log in: ".$code."\n\n\n".site_url(), "Content-Type: text/plain");
}


function sendBackToLogin()
{
	header('Location: '.$_SERVER['REQUEST_URI']);
	exit;
}


function generateTwoFactorCode($len = 5)
{
	$chars = '123456789QWERTYUIPASDFGHJKLZXCVBNM';
	$chars = str_split($chars);
	shuffle($chars);
	$code = implode('', array_splice($chars, 0, $len));
	
	return $code;
}

?>