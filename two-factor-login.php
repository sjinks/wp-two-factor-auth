<?php
/*
Plugin Name: Two Factor Auth
Plugin URI: http://oskarhane.com/plugin-two-factor-auth-for-wordpress
Description: Secure your WordPress login with this two factor auth. Users will be prompted with a page to enter a one time code that was emailed to them.
Author: Oskar Hane
Author URI: http://oskarhane.com
Version: 2.0
License: GPLv2 or later
*/


function showAdminSettingsPage()
{
	include 'admin_settings.php';
}


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
	
	//So we show full form for users that dont exist
	$is_activated_for_user = true;

	//Render form anyway so we don't reveal if the username exists or not
	if($user)
	{
		$is_activated_for_user = tfaIsActivatedForUser($user->ID);
		
		if($is_activated_for_user)
		{
			delete_user_meta($user->ID, 'two_factor_login_code');
			add_user_meta($user->ID, 'two_factor_login_code', $code);
			sendTwoFactorEmail($user->user_email, $code);
		}
	}
	
	include 'form.php';
	
	
	exit;
}


function tfaIsActivatedForUser($user_id)
{
	$user = new WP_User($user_id);

	foreach($user->roles as $role)
	{
		$db_val = get_option('tfa_'.$role);
		$db_val = $db_val === false || $db_val ? 1 : 0; //Nothing saved or > 0 returns 1;
		
		if($db_val)
			return true;
	}
	
	return false;
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
	
	if(!$user_ID)
		sendBackToLogin();
	
	$code = get_user_meta($user_ID, 'two_factor_login_code', true);
	
	//Remove code after one guess
	delete_user_meta($user_ID, 'two_factor_login_code');
	
	if(!tfaIsActivatedForUser($user_ID))
		return;
	
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



/**
* For Admin menu and settings
*/
function registerTwoFactorAuthSettings()
{
	global $wp_roles;
	if (!isset($wp_roles))
		$wp_roles = new WP_Roles();
	
	foreach($wp_roles->role_names as $id => $name)
	{
		register_setting('tfa_user_roles_group', 'tfa_'.$id);
	}
	
}

function tfaListUserRolesCheckboxes()
{
	global $wp_roles;
	if (!isset($wp_roles))
		$wp_roles = new WP_Roles();
	
	foreach($wp_roles->role_names as $id => $name)
	{	
		$setting = get_option('tfa_'.$id);
		$setting = $setting === false || $setting ? 1 : 0;
		
		print '<input type="checkbox" name="tfa_'.$id.'" value="1" '.($setting ? 'checked="checked"' :'').'> '.$name."<br>\n";
	}
	
}

function addTwoFactorAuthAdminMenu()
{
	add_action( 'admin_init', 'registerTwoFactorAuthSettings' );
	add_options_page('Two Factor Auth', 'Two Factor Auth', 'manage_options', 'two-factor-auth', 'showAdminSettingsPage');
}

function addPluginSettingsLink($links)
{
	$link = '<a href="options-general.php?page=two-factor-auth">'.__('Settings').'</a>';
	array_unshift($links, $link);
	return $links;
}

if(is_admin())
{
	//Add to Settings menu
	add_action('admin_menu', 'addTwoFactorAuthAdminMenu');
	
	//Add settings link in plugin list
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_".$plugin, 'addPluginSettingsLink' );
}

?>