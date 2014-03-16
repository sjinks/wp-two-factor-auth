<?php

if(@$_POST['tfa_delivery_type'] && @$_GET['settings-updated'] == 'true')
{
	$tfa->changeUserDeliveryTypeTo($current_user->ID, $_POST['tfa_delivery_type']);
}
elseif(@$_POST['tfa_algorithm_type'] && @$_GET['settings-updated'] == 'true')
{
	$old_algorithm = $tfa->getUserAlgorithm($current_user->ID);
	
	if($old_algorithm != $_POST['tfa_algorithm_type'])
		$tfa->changeUserAlgorithmTo($current_user->ID, $_POST['tfa_algorithm_type']);
}
if(isset($_GET['warning_button_clicked']) && $_GET['warning_button_clicked'] == 1)
{
	delete_user_meta($current_user->ID, 'tfa_hotp_off_sync');
}
?>
<style>
	#icon-tfa-plugin {
    	background: transparent url('<?php print plugin_dir_url(__FILE__); ?>img/tfa_admin_icon_32x32.png' ) no-repeat;
	}
	.inside > h3, .normal {
		cursor: default;
		margin-top: 20px;
	}
</style>
<div class="wrap">

			

	<?php screen_icon('tfa-plugin'); ?>
	<h2>Two Factor Auth <?php _e('Settings', TFA_TEXT_DOMAIN); ?></h2>
	
		<div id="sm_rebuild" class="postbox">
		<h3 class="hndle" style="padding: 10px; cursor: default;"><span style="cursor: default;"><?php _e('Important Notes', TFA_TEXT_DOMAIN); ?></span></h3>
		<div class="inside">
			<p>
				<?php _e('This is your personal settings for the Two Factor Auth. Nothing you change here will have any effect on other users.', TFA_TEXT_DOMAIN); ?>
			</p>
			<p>
				<span style="color:red"><?php _e('IMPORTANT', TFA_TEXT_DOMAIN); ?></span>: 
				<?php _e('If you choose the third-party-apps version (which is more safe and real Two Factor Auth) you have to make sure you scan the QR-code (or enter your private key manually).', TFA_TEXT_DOMAIN); ?>
				<br><?php _e('Verify with the One Time Password at the bottom of this page', TFA_TEXT_DOMAIN); ?>.<br>
				<?php _e('If the code in your app and the one at the bottom of this page do not match, deactivate Third Party Apps and enable email again and contact your site administrator.', TFA_TEXT_DOMAIN); ?>
				<br>
				<strong><?php _e("If the One Time Passwords do not match, you can't log in to this site any more.", TFA_TEXT_DOMAIN); ?></strong>
			</p>
		</div>
	</div>

	
	
	
	
	<form method="post" action="<?php print add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']); ?>">
		<h2><?php _e('Delivery type', TFA_TEXT_DOMAIN); ?></h2>
		<?php _e('Choose how you want your', TFA_TEXT_DOMAIN); ?> <em><?php _e('One Time Passwords', TFA_TEXT_DOMAIN); ?></em> <?php _e('delivered', TFA_TEXT_DOMAIN); ?>.
		<p>
		<?php
			tfaListDeliveryRadios($current_user->ID);
		?></p>
		<?php submit_button(); ?>
	</form>
	<?php
	
	$setting = get_user_meta($current_user->ID, 'tfa_delivery_type', true);
	if($setting == 'third-party-apps')
	{
		$url = preg_replace('/^https?:\/\//', '', site_url());
		
		$tfa_priv_key_64 = get_user_meta($current_user->ID, 'tfa_priv_key_64', true);
		
		if(!$tfa_priv_key_64)
			$tfa_priv_key_64 = $tfa->addPrivateKey($current_user->ID);

		$tfa_priv_key = trim($tfa->getPrivateKeyPlain($tfa_priv_key_64, $current_user->ID));
			
		$panics = get_user_meta($current_user->ID, 'tfa_panic_codes_64');
		$panic_str = $tfa->getPanicCodesString($panics[0], $current_user->ID);
		
		$algorithm_type = $tfa->getUserAlgorithm($current_user->ID);
		
		?>
		<h2><?php _e('Third Party Apps Set Up', TFA_TEXT_DOMAIN); ?></h2>
		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;"><?php _e('Third Party App QR-Code', TFA_TEXT_DOMAIN); ?></span>
			</h3>
			<div class="inside">
				<p>
					<?php _e('Scan this code with', TFA_TEXT_DOMAIN); ?> Duo Mobile, Google Authenticator <?php _e("or other app that supports 6 digit OTP's", TFA_TEXT_DOMAIN); ?>.
					<br>
					<?php _e('You are currently using', TFA_TEXT_DOMAIN); ?> <?php print strtoupper($algorithm_type).' ('.($algorithm_type == 'totp' ? __('a time based', TFA_TEXT_DOMAIN) : __('an event based', TFA_TEXT_DOMAIN)).')'; ?> <?php _e('algorithm, if the app asks for that info', TFA_TEXT_DOMAIN); ?>.
				</p>
				<p>
					<img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://<?php print $algorithm_type; ?>/<?php print $url; ?>:%2520<?php print urlencode($current_user->user_login); ?>%3Fsecret%3D<?php print Base32::encode($tfa_priv_key); ?>%26issuer=<?php print $url; ?>%26counter=<?php print $tfa->getUserCounter($current_user->ID); ?>">
				</p>
			</div>
		</div>

		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;"><?php _e('Panic Codes', TFA_TEXT_DOMAIN); ?></span>
			</h3>
			<div class="inside">
				<p>
					<?php _e("You have three panic codes that can be used if you lose your phone and can't get your One Time Passwords. These can only be used one time and you cannot generate new ones.", TFA_TEXT_DOMAIN); ?>
					<br><?php _e('Keep them in a safe place.', TFA_TEXT_DOMAIN); ?>
					<br><br>
					<strong><?php _e('Your panic codes are', TFA_TEXT_DOMAIN); ?></strong>: <?php print $panic_str; ?>
				</p>
			</div>
		</div>
		
		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;"><?php _e('Current One Time Password', TFA_TEXT_DOMAIN); ?></span>
			</h3>
			<div class="inside">
				<p><?php _e('Reload every now and then and double check with you third party app.', TFA_TEXT_DOMAIN); ?></p>
				<p><?php _e('The current One Time Password is', TFA_TEXT_DOMAIN); ?>: 
					<br><br>
					<strong style="font-size: 3em;"><?php print $tfa->generateOTP($current_user->ID, $tfa_priv_key_64); ?></strong>
				</p>
			</div>
		</div>
		
		<h2><?php _e('Advanced', TFA_TEXT_DOMAIN); ?></h2>
		<a href="javascript:void(0)" onclick="jQuery('#tfa_advanced_box').slideToggle()" class="button">
			&darr; <?php _e('Show advanced info', TFA_TEXT_DOMAIN); ?> &darr;
		</a>
		<div id="tfa_advanced_box" class="postbox" style="margin-top: 20px; display:none">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;"><?php _E('Private Key Info', TFA_TEXT_DOMAIN); ?></span>
			</h3>
			<div class="inside">
				<h3 class="normal" style="cursor: default"><?php _e('Private Key in Plain Text', TFA_TEXT_DOMAIN); ?></h3>
				<p>
					<?php _e('This key is your secret. Never give it to anyone.', TFA_TEXT_DOMAIN); ?><br>
					<strong><?php _e('Your private key is', TFA_TEXT_DOMAIN); ?></strong>: <?php print $tfa_priv_key; ?> (
						<a href="javascript:if(confirm('<?php _e('WARNING: If you reset this key you will have to update your apps with the new one. Are you sure you want this?', TFA_TEXT_DOMAIN); ?>')){ window.location = '<?php print add_query_arg(array('tfa_priv_key_reset' => 1,'settings-updated' => 'true')) ?>'; }">reset</a>
					)
				</p>
				<h3 class="normal" style="cursor: default">Base32</h3>
				<p><?php _e('Base32 is used by some third party apps like Google Authenticator. This is just as secret as the key in plain text.', TFA_TEXT_DOMAIN); ?>
				</p>
				<p><strong><?php _e('Your private key in base32 is', TFA_TEXT_DOMAIN); ?></strong>: <?php print Base32::encode($tfa_priv_key); ?></p>
				<h3 class="normal" style="cursor: default"><?php _e('Algorithm Used', TFA_TEXT_DOMAIN); ?></h3>
				
				<form method="post" action="<?php print add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']); ?>">
					<h2><?php _e('Choose Algorithm', TFA_TEXT_DOMAIN); ?></h2>
					<?php _e('Choose which algorithm for', TFA_TEXT_DOMAIN); ?> <em><?php _e('One Time Passwords', TFA_TEXT_DOMAIN); ?></em> <?php _e('you want', TFA_TEXT_DOMAIN); ?>.
					<p>
					<?php
						tfaListAlgorithmRadios($current_user->ID);
						if($algorithm_type == 'hotp')
						{
							$counter = $tfa->getUserCounter($current_user->ID);
							print '<br>'.__('Your counter on the server is currently on', TFA_TEXT_DOMAIN).': '.$counter;
						}
					?>
					
					</p>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>

		
		<?php
	}
	
	?>
</div>