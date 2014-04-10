<?php
/*
Plugin Name: Two Factor Auth
Plugin URI: http://oskarhane.com/plugin-two-factor-auth-for-wordpress
Description: Secure your WordPress login with two factor auth. Users will be prompted with a page to enter a One Time Password when they login.
Author: Oskar Hane
Author URI: http://oskarhane.com
Version: 4.3.4
License: GPLv2 or later
*/
//error_reporting(E_ALL);
//ini_set("display_errors", true);
define('TFA_TEXT_DOMAIN', 'two-factor-auth');
$my_translator_is_setup = 0;
define('TFA_MAIN_PLUGIN_PATH', dirname( __FILE__ ));

function getTFAClass()
{
	include_once TFA_MAIN_PLUGIN_PATH.'/hotp-php-master/hotp.php';
	include_once TFA_MAIN_PLUGIN_PATH.'/Base32/Base32.php';
	include_once TFA_MAIN_PLUGIN_PATH.'/class.TFA.php';
	
	$tfa = new TFA(new Base32(), new HOTP());
	
	return $tfa;
}

function tfaInitLogin()
{			
	$tfa = getTFAClass();
	$res = $tfa->preAuth(array('log' => $_POST['user']));

	print json_encode(array('status' => $res));
	exit;
}
add_action( 'wp_ajax_nopriv_tfa-init-otp', 'tfaInitLogin');



function tfaVerifyCodeAndUser($user, $username, $password)
{
	
	
	$installed_version = get_option('tfa_version');
	if($installed_version < 4)
		return $user;
		
	$tfa = getTFAClass();
	
	if(is_wp_error($user))
		return $user;

	$params = $_POST;
	$params['log'] = $username;
	$params['caller'] = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
	
	$code_ok = $tfa->authUserFromLogin($params);

	
	if(!$code_ok)
		return new WP_Error('authentication_failed', __('<strong>ERROR</strong>: The Two Factor Code you entered was incorrect.', TFA_TEXT_DOMAIN));
	
	if($user)
		return $user;
		
	return wp_authenticate_username_password(null, $username, $password);
}
add_filter('authenticate', 'tfaVerifyCodeAndUser', 99999999999, 3);//We want to be the last filter that runs.



/**
* For Admin menu and settings
*/

function tfaPushForUpgrade() {
	if(!current_user_can('install_plugins'))
		return;
		
	$installed_version = get_option('tfa_version');
	if($installed_version >= 4)
		return;
	
    ?>
    <div class="updated">
    	<h3>Database changes needed!</h3>
        <p>
        	You need to initialize changes to the database for <strong>Two Factor Auth</strong> to work with the current version.
        	<br>
        	This is safe and will only have effect on values added by the <strong>Two Factor Auth</strong> plugin.
        	<br><br>
        	<a href="options-general.php?page=two-factor-auth&tfa_upgrade_script=yes" class="button">Click here to upgrade</a>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'tfaPushForUpgrade' );


function tfaRegisterTwoFactorAuthSettings()
{
	global $wp_roles;
	if (!isset($wp_roles))
		$wp_roles = new WP_Roles();
	
	foreach($wp_roles->role_names as $id => $name)
	{
		register_setting('tfa_user_roles_group', 'tfa_'.$id);
	}
	
	register_setting('tfa_default_hmac_group', 'tfa_default_hmac');
	register_setting('tfa_xmlrpc_status_group', 'tfa_xmlrpc_on');
}


function tfaListDeliveryRadios($user_id)
{
	if(!$user_id)
		return;
	
			
	$types = array('email' => __('Email', TFA_TEXT_DOMAIN), 'third-party-apps' => __('Third party apps', TFA_TEXT_DOMAIN).' (Duo Mobile, Google Authenticator etc)'); 
	
	$setting = get_user_meta($user_id, 'tfa_delivery_type', true);
	$setting = $setting === false || !$setting ? 'email' : $setting;
		
	foreach($types as $id => $name)
		print '<input type="radio" name="tfa_delivery_type" value="'.$id.'" '.($setting == $id ? 'checked="checked"' :'').'> - '.$name."<br>\n";
	
}

function tfaListAlgorithmRadios($user_id)
{
	if(!$user_id)
		return;
	
			
	$types = array('totp' => __('TOTP (time based)', TFA_TEXT_DOMAIN), 'hotp' => __('HOTP (event based)', TFA_TEXT_DOMAIN)); 
	
	$setting = get_user_meta($user_id, 'tfa_algorithm_type', true);
	$setting = $setting === false || !$setting ? 'totp' : $setting;

	foreach($types as $id => $name)
		print '<input type="radio" name="tfa_algorithm_type" value="'.$id.'" '.($setting == $id ? 'checked="checked"' :'').'> - '.$name."<br>\n";
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

function tfaListDefaultHMACRadios()
{
	$tfa = getTFAClass();
	$setting = get_option('tfa_default_hmac');
	$setting = $setting === false || !$setting ? $tfa->default_hmac : $setting;
	
	$types = array('totp' => __('TOTP (time based)', TFA_TEXT_DOMAIN), 'hotp' => __('HOTP (event based)', TFA_TEXT_DOMAIN));
	
	foreach($types as $id => $name)
		print '<input type="radio" name="tfa_default_hmac" value="'.$id.'" '.($setting == $id ? 'checked="checked"' :'').'> - '.$name."<br>\n";
}


function tfaListXMLRPCStatusRadios()
{
	$tfa = getTFAClass();
	$setting = get_option('tfa_xmlrpc_on');
	$setting = $setting === false || !$setting ? 0 : 1;
	
	$types = array('0' => __('OFF', TFA_TEXT_DOMAIN), '1' => __('ON', TFA_TEXT_DOMAIN));
	
	foreach($types as $id => $name)
		print '<input type="radio" name="tfa_xmlrpc_on" value="'.$id.'" '.($setting == $id ? 'checked="checked"' :'').'> - '.$name."<br>\n";
}


function tfaShowAdminSettingsPage()
{
	$tfa = getTFAClass();
	global $wp_roles;
	include TFA_MAIN_PLUGIN_PATH.'/admin_settings.php';
}

function tfaShowUserSettingsPage()
{
	$tfa = getTFAClass();
	global $current_user;
	include TFA_MAIN_PLUGIN_PATH.'/user_settings.php';
}


function tfaAddUserSettingsMenu() 
{
	global $current_user;
	$tfa = getTFAClass();
	
	if(!$tfa->isActivatedForUser($current_user->ID))
		return;
	
	add_menu_page('Two Factor Auth', 'Two Factor Auth', 'read', 'two-factor-auth-user', 'tfaShowUserSettingsPage', plugin_dir_url(__FILE__).'img/tfa_admin_icon_16x16.png', 72);
}
add_action('admin_menu', 'tfaAddUserSettingsMenu');

function addTwoFactorAuthAdminMenu()
{
	add_action( 'admin_init', 'tfaRegisterTwoFactorAuthSettings' );
	add_options_page('Two Factor Auth', 'Two Factor Auth', 'manage_options', 'two-factor-auth', 'tfaShowAdminSettingsPage');
}

function addPluginSettingsLink($links)
{
	
	$link = '<a href="options-general.php?page=two-factor-auth">'.__('Settings', TFA_TEXT_DOMAIN).'</a>';
	array_unshift($links, $link);
	return $links;
}

function tfaSaveSettings()
{
	global $current_user;
	if(@$_GET['tfa_change_to_email'] && @$_GET['tfa_user_id'])
	{
		$tfa = getTFAClass();
		
		if(is_admin())
			$tfa->changeUserDeliveryTypeTo($_GET['tfa_user_id'], 'email');
	
		$goto = site_url().remove_query_arg(array('tfa_user_id', 'tfa_change_to_email'));
		wp_safe_redirect($goto);
		exit;
	}
	
	if(@$_GET['tfa_priv_key_reset'])
	{
		delete_user_meta($current_user->ID, 'tfa_priv_key_64');
		delete_user_meta($current_user->ID, 'tfa_panic_codes_64');
		wp_safe_redirect(site_url().remove_query_arg('tfa_priv_key_reset'));
		exit;
	}
	
	if(@$_GET['tfa_upgrade_script'])
	{
		$tfa = getTFAClass();
		$tfa->upgrade();
		wp_safe_redirect(site_url().remove_query_arg('tfa_upgrade_script').'&upgrade_done=true');
		exit;
	}
}

function tfaAddJSToLogin()
{
	
	$installed_version = get_option('tfa_version');
	if($installed_version < 4)
		return;
	
	if(isset($_GET['action']) && $_GET['action'] != 'logout' && $_GET['action'] != 'login')
		return;
	
	wp_enqueue_script( 'tfa-ajax-request', plugin_dir_url( __FILE__ ) . 'tfa_v4.3.4.js', array( 'jquery' ) );
	wp_localize_script( 'tfa-ajax-request', 'tfaSettings', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'click_to_enter_otp' => __("Click to enter One Time Password", TFA_TEXT_DOMAIN),
		'enter_username_first' => __('You have to enter a username first.', TFA_TEXT_DOMAIN),
		'otp' => __("One Time Password", TFA_TEXT_DOMAIN),
		'otp_login_help' => __('(check your email or OTP-app to get this password)', TFA_TEXT_DOMAIN)
	));
}
add_action('login_enqueue_scripts', 'tfaAddJSToLogin');

function tfaShowHOTPOffSyncMessage()
{
	global $current_user;
	$is_off_sync = get_user_meta($current_user->ID, 'tfa_hotp_off_sync', true);
	if(!$is_off_sync)
		return;
	
	?>
	<div class="error">
    	<h3>Two Factor Auth re-sync needed</h3>
        <p>
        	You need to resync your mobile app for <strong>Two Factor Auth</strong> since the OTP you last used is many steps ahead 
        	of the server.
        	<br>
        	Please re-sync or you might not be able to log in if you generate more OTP:s without logging in.
        	<br><br>
        	<a href="admin.php?page=two-factor-auth-user&warning_button_clicked=1" class="button">Click here and re-scan the QR-Code</a>
        </p>
    </div>
	
	<?php
	
}
//Show off sync message for hotp
add_action('admin_notices', 'tfaShowHOTPOffSyncMessage');

	
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

function installTFA()
{
	$error = false;
	if (version_compare(PHP_VERSION, '5.3', '<' ))
	{
		$error = true;
		$flag = 'PHP version 5.3 or higher.';
	}
	elseif(!function_exists('mcrypt_get_iv_size'))
	{
		$error = true;
		$flag = 'that PHP mcrypt installed. See <a href="http://www.php.net/manual/en/mcrypt.installation.php" target="_blank">PHP.net mcrypt >></a> for more info.';
	}
	
	if($error)
	{
		deactivate_plugins( basename( __FILE__ ) );
		die('<p>The <strong>Two Factor Auth</strong> plugin requires '.$flag.'</p>');
	}
	
	$tfa = getTFAClass();
	$tfa->upgrade();
}
register_activation_hook(__FILE__, 'installTFA');


function tfaSetLanguages()
{
	global $my_translator_is_setup;
	
	if($my_translator_is_setup)
		return;
	
	load_plugin_textdomain(
		TFA_TEXT_DOMAIN,
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
	$my_translator_is_setup = true;
}
tfaSetLanguages();

?>