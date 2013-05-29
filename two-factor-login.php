<?php
/*
Plugin Name: Two Factor Auth
Plugin URI: http://oskarhane.com/plugin-two-factor-auth-for-wordpress
Description: Secure your WordPress login with this two factor auth. Users will be prompted with a page to enter a one time code that was emailed to them.
Author: Oskar Hane
Author URI: http://oskarhane.com
Version: 3.0.1
License: GPLv2 or later
*/
//error_reporting(E_ALL);
//ini_set("display_errors", true);
define('TFA_MAIN_PLUGIN_PATH', dirname( __FILE__ ));

include_once TFA_MAIN_PLUGIN_PATH.'/hotp-php-master/hotp.php';
include_once TFA_MAIN_PLUGIN_PATH.'/Base32/Base32.php';


function tfaInitLogin()
{		
	tfaPrepareTwoFactorAuth(array('log' => $_POST['user']));
	print json_encode(array('status' => true));
	exit;
}
add_action( 'wp_ajax_nopriv_tfa-init-otp', 'tfaInitLogin');



function tfaVerifyCodeAndUser($user, $username, $password)
{
	//If already failed, we don't bother to check the code
	if(is_wp_error($user))
		return $user;

	$params = $_POST;
	$code_ok = checkTwoFactorCode($params);
	
	if(!$code_ok)
		return new WP_Error('authentication_failed', __('<strong>ERROR</strong>: The Two Factor Code you entered was incorrect.'));
	
	if($user)
		return $user;
		
	return wp_authenticate_username_password(null, $username, $password);
}
add_filter('authenticate', 'tfaVerifyCodeAndUser', 99999999999, 3);//We want to be the last filter that runs.



function tfaPrepareTwoFactorAuth($params)
{
	global $wpdb;
	$query = $wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE user_login=%s", $params['log']);
	$user = $wpdb->get_row($query);
	
	$tfa_priv_key = get_user_meta($user->ID, 'tfa_priv_key', true);
	
	//So we show full form for users that dont exist
	$is_activated_for_user = true;

	//Render form anyway so we don't reveal if the username exists or not
	if($user)
	{
		$is_activated_for_user = tfaIsActivatedForUser($user->ID);
		
		if($is_activated_for_user)
		{
			$delivery_type = get_user_meta($user->ID, 'tfa_delivery_type', true);
			
			//Default is email
			if(!$delivery_type || $delivery_type == 'email')
			{
				//No private key yet, generate one.
				//This is safe to do since the code is emailed to the user.
				//Not safe to do if the user has disabled email.
				if(!$tfa_priv_key)
					$tfa_priv_key = addTFAPrivKey($user->ID);
					
				$code = generateTwoFactorCode($tfa_priv_key);
				sendTwoFactorEmail($user->user_email, $code);
			}
		}
	}
	return true;
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



function checkTwoFactorCode($params)
{
	global $wpdb;
	$query = $wpdb->prepare("SELECT ID from ".$wpdb->users." WHERE user_login=%s", $params['log']);
	$user_ID = $wpdb->get_var($query);
	$user_code = trim(@$params['two_factor_code']);
	
	if(!$user_ID)
		return true;
	
	if(!tfaIsActivatedForUser($user_ID))
		return true;
	$tfa_priv_key = get_user_meta($user_ID, 'tfa_priv_key', true);
	
	//Give the user 1,5 minutes time span to enter/retrieve the code
	$codes = HOTP::generateByTimeWindow($tfa_priv_key, 30, -2, 0);

	$match = false;
	foreach($codes as $code)
	{
		if($code->toHotp(6) == $user_code)
		{
			$match = true;
			break;
		}
	}
	
	//Check panic codes
	if(!$match)
	{
		$panic_codes = get_user_meta($user_ID, 'tfa_panic_codes');
		
		if(!@$panic_codes[0])
			return $match;
			
		$panic_codes = current($panic_codes);
		$in_array = array_search($user_code, $panic_codes);
		$match = $in_array !== false;
		
		if($match)//Remove panic code
		{
			array_splice($panic_codes, $in_array, 1);
			update_user_meta($user_ID, 'tfa_panic_codes', $panic_codes);
		}
	}
	
	return $match;
}



function sendTwoFactorEmail($email, $code)
{
	wp_mail( $email, 'Login Code for '.get_bloginfo('name'), "\n\nEnter this code to log in: ".$code."\n\n\n".site_url(), "Content-Type: text/plain");
}


function generateTwoFactorCode($tfa_priv_key)
{
	$otp_res = HOTP::generateByTime($tfa_priv_key, 30);
	$code = $otp_res->toHotp(6);
	
	return $code;
}

function tfaGeneratePrivateKey($len = 6)
{
	$chars = '23456789QWERTYUPASDFGHJKLZXCVBNM';
	$chars = str_split($chars);
	shuffle($chars);
	$code = implode('', array_splice($chars, 0, $len));
	
	return $code;
}

function addTFAPrivKey($user_ID)
{
	//Generate a private key for the user. 
	//To work with Google Authenticator it has to be 10 bytes = 16 chars in base32
	$code = strtoupper(tfaGeneratePrivateKey(10));
	
	//Add private key to users meta
	add_user_meta($user_ID, 'tfa_priv_key', $code);
	
	//Add some panic codes as well
	add_user_meta($user_ID, 'tfa_panic_codes', array(tfaGeneratePrivateKey(8), tfaGeneratePrivateKey(8), tfaGeneratePrivateKey(8)));
	
	return $code;
}


/**
* For Admin menu and settings
*/
function tfaRegisterTwoFactorAuthSettings()
{
	global $wp_roles;
	if (!isset($wp_roles))
		$wp_roles = new WP_Roles();
	
	foreach($wp_roles->role_names as $id => $name)
	{
		register_setting('tfa_user_roles_group', 'tfa_'.$id);
	}
	
}


function tfaListDeliveryRadios($user_id)
{
	if(!$user_id)
		return;
		
	$types = array('email' => 'Email', 'third-party-apps' => 'Third party apps (Google Authenticator etc)'); 
	
	foreach($types as $id => $name)
	{	
		$setting = get_user_meta($user_id, 'tfa_delivery_type', true);
		$setting = $setting === false || !$setting ? 'email' : $setting;
		
		print '<input type="radio" name="tfa_delivery_type" value="'.$id.'" '.($setting == $id ? 'checked="checked"' :'').'> '.$name."<br>\n";
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

function tfaShowAdminSettingsPage()
{
	global $wp_roles;
	include TFA_MAIN_PLUGIN_PATH.'/admin_settings.php';
}

function tfaShowUserSettingsPage()
{
	global $current_user;
	include TFA_MAIN_PLUGIN_PATH.'/user_settings.php';
}

add_action('admin_menu', 'tfaAddUserSettingsMenu');

function tfaAddUserSettingsMenu() 
{
	add_users_page('Two Factor Auth', 'Two Factor Auth', 'read', 'two-factor-auth-user', 'tfaShowUserSettingsPage');
}


function addTwoFactorAuthAdminMenu()
{
	add_action( 'admin_init', 'tfaRegisterTwoFactorAuthSettings' );
	add_options_page('Two Factor Auth', 'Two Factor Auth', 'manage_options', 'two-factor-auth', 'tfaShowAdminSettingsPage');
}

function addPluginSettingsLink($links)
{
	$link = '<a href="options-general.php?page=two-factor-auth">'.__('Settings').'</a>';
	array_unshift($links, $link);
	return $links;
}

function tfaSaveSettings()
{
	global $current_user;
	if(@$_GET['tfa_change_to_email'] && @$_GET['tfa_user_id'])
	{
	
		if(is_admin())
			update_user_meta($_GET['tfa_user_id'], 'tfa_delivery_type', 'email');
	
		$goto = site_url().remove_query_arg(array('tfa_user_id', 'tfa_change_to_email'));
		wp_safe_redirect($goto);
		exit;
	}
	
	if(@$_GET['tfa_priv_key_reset'])
	{
		delete_user_meta($current_user->ID, 'tfa_priv_key');
		delete_user_meta($current_user->ID, 'tfa_panic_codes');
		wp_safe_redirect(site_url().remove_query_arg('tfa_priv_key_reset'));
		exit;
	}
}

function tfaAddJSToLogin()
{
	wp_enqueue_script( 'tfa-ajax-request', plugin_dir_url( __FILE__ ) . 'tfa.js', array( 'jquery' ) );
	wp_localize_script( 'tfa-ajax-request', 'tfaSettings', array(
		'ajaxurl' => admin_url('admin-ajax.php')
	));
}
add_action('login_enqueue_scripts', 'tfaAddJSToLogin');

if(is_admin())
{
	//Save settings
	add_action('admin_init', 'tfaSaveSettings');
	
	//Add to Settings menu
	add_action('admin_menu', 'addTwoFactorAuthAdminMenu');
	
	//Add settings link in plugin list
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_".$plugin, 'addPluginSettingsLink' );
}

?>