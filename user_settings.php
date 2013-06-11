<?php

if(@$_POST['tfa_delivery_type'] && @$_GET['settings-updated'] == 'true')
{
	update_user_meta($current_user->ID, 'tfa_delivery_type', $_POST['tfa_delivery_type']);
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
	<h2>Two Factor Auth Settings</h2>
	
		<div id="sm_rebuild" class="postbox">
		<h3 class="hndle" style="padding: 10px; cursor: default;"><span style="cursor: default;">Important Notes</span></h3>
		<div class="inside">
			<p>
				This is your personal settings for the Two Factor Auth. Nothing you change here will have any effect on other 
				users.
			</p>
			<p>
				<span style="color:red">IMPORTANT</span>: If you choose the third-party-apps version (which is more safe and real Two Factor Auth) you 
				have to make sure you scan the QR-code (or enter your private key manually). 
				<br>Verify with the One Time Password at the bottom of this page.<br>
				If the code in your app and the one at the bottom of this page do not match, deactivate Third Party Apps and enable 
				email again and contact your site administrator.
				<br>
				<strong>If the One Time Passwords do not match, you can't log in to this site any more.</strong>
			</p>
		</div>
	</div>

	
	
	
	
	<form method="post" action="<?php print add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']); ?>">
		<h2>Delivery type</h2>
		Choose how you want your <em>One Time Passwords</em> delivered.
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
		$url = site_url();
		$tfa_priv_key_64 = get_user_meta($current_user->ID, 'tfa_priv_key_64', true);
		
		if(!$tfa_priv_key_64)
			$tfa_priv_key_64 = $tfa->addPrivateKey($current_user->ID);

		$tfa_priv_key = trim($tfa->getPrivateKeyPlain($tfa_priv_key_64, $current_user->ID));
			
		$panics = get_user_meta($current_user->ID, 'tfa_panic_codes_64');
		$panic_str = $tfa->getPanicCodesString($panics[0], $current_user->ID);
		
		
		?>
		<h2>Third Party Apps Set Up</h2>
		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;">Third Party App QR-Code</span>
			</h3>
			<div class="inside">
				<p>
					Scan this code with Duo Mobile, Google Authenticator or other app that supports 6 digit OTP's.
				</p>
				<p>
					<img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/<?php print urlencode($current_user->user_login).'@'.$url; ?>%3Fsecret%3D<?php print Base32::encode($tfa_priv_key); ?>">
				</p>
			</div>
		</div>

		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;">Panic Codes</span>
			</h3>
			<div class="inside">
				<p>
					You have three panic codes that can be used if you lose your phone and can't get your One Time Passwords. These 
					can only be used one time and you cannot generate new ones.
					<br>Keep them in a safe place.
					<br><br>
					<strong>Your panic codes are</strong>: <?php print $panic_str; ?>
				</p>
			</div>
		</div>
		
		<div id="sm_rebuild" class="postbox">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;">Current One Time Password</span>
			</h3>
			<div class="inside">
				<p>Reload every now and then and double check with you third party app.</p>
				<p>The current One Time Password is: 
					<br><br>
					<strong style="font-size: 3em;"><?php print $tfa->generateOTP($current_user->ID, $tfa_priv_key_64); ?></strong>
				</p>
			</div>
		</div>
		
		<h2>Advanced</h2>
		<a href="javascript:void(0)" onclick="jQuery('#tfa_advanced_box').slideToggle()" class="button">
			&darr; Show advanced info &darr;
		</a>
		<div id="tfa_advanced_box" class="postbox" style="margin-top: 20px; display:none">
			<h3 class="hndle" style="padding: 10px; cursor: default;">
				<span style="cursor: default;">Private Key Info</span>
			</h3>
			<div class="inside">
				<h3 class="normal" style="cursor: default">Private Key in Plain Text</h3>
				<p>
					This key is your secret. Never give it to anyone.<br>
					<strong>Your private key is</strong>: <?php print $tfa_priv_key; ?> (
						<a href="javascript:if(confirm('WARNING: If you reset this key you will have to update\nyour apps with the new one.\n\nAre you sure you want this?')){ window.location = '<?php print add_query_arg(array('tfa_priv_key_reset' => 1,'settings-updated' => 'true')) ?>'; }">reset</a>
					)
				</p>
				<h3 class="normal" style="cursor: default">Base32</h3>
				<p>Base32 is used by some third party apps like Google Authenticator.
					This is just as secret as the key in plain text.
				</p>
				<p><strong>Your private key in base32 is</strong>: <?php print Base32::encode($tfa_priv_key); ?></p>
				<h3 class="normal" style="cursor: default">Algorithm Used</h3>
				<p>
					The Algorithm used is TOTP / Time based.
				</p>
			</div>
		</div>

		
		<?php
	}
	
	?>
</div>