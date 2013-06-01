<?php

if(@$_POST['tfa_delivery_type'] && @$_GET['settings-updated'] == 'true')
{
	update_user_meta($current_user->ID, 'tfa_delivery_type', $_POST['tfa_delivery_type']);
}


?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Two Factor Auth Settings</h2>
	<div>
		<img style="margin-top: 10px" src="<?php print plugin_dir_url(__FILE__); ?>tfa_header.png">
	</div>
	<h2 style="margin-top: 20px">Important Notes</h2>
	<p>
		Here you can choose how you want your One Time Codes delivered.
		<br>
		<strong style="color:red">IMPORTANT</strong>: If you choose the third-party-apps version (which is more safe and real Two Factor Auth) you 
		have to make sure you enter your private key (or scan the QR-code). Verify with the One Time Password 
		at the <strong>bottom of this page</strong>.<br>
		<strong>If you do not, you can't log in to this site any more.</strong>
		<br><br>
		If the code in your app and the one at the bottom of this page do not match, deactivate Third Party Apps and enable 
		email again and contact your site administrator.
	</p>
	<p>
		Some third party apps want's the code on base32 encoding (like Google Authenticator) and some wants it 
		in plain text, thats why both are listed. The QR-Code has the key in base32.
	</p>
	<hr>
	<form method="post" action="<?php print add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']); ?>">
		<h2>Delivery type</h2>
		Choose how you want your <em>One Time Codes</em> delivered.
		<p>
		<?php
			tfaListDeliveryRadios($current_user->ID);
		?></p>
		<?php submit_button(); ?>
		<em>After you save, scroll down to see your settings and how to set up your apps.</em>	
	</form>
	<hr>
	<?php
	
	$setting = get_user_meta($current_user->ID, 'tfa_delivery_type', true);
	if($setting == 'third-party-apps' && @$_GET['settings-updated'] == 'true')
	{
		$url = site_url();
		$tfa_priv_key = get_user_meta($current_user->ID, 'tfa_priv_key', true);
		if(!$tfa_priv_key)
			$tfa_priv_key = addTFAPrivKey($current_user->ID);
		
		$panic = get_user_meta($current_user->ID, 'tfa_panic_codes');
		$panic_str = $panic[0] ? implode(", ", $panic[0]) : '<em>No panic codes left. Sorry.</em>';
		?>
		<h2>Panic Codes</h2>
		<p>
			You have three panic codes that can be used if you loose your phone and can't get your One Time Codes. These 
			can only be used one time and you cannot generate new ones.
			<br>Keep them in a safe place.
			<br><br>
			<strong>Your panic codes are</strong>: <?php print $panic_str; ?>
		</p>
		<hr>
		<h2>Private Key Info</h2>
		<h3>Algorithm Used</h3>
		<p>
			The Algorithm used is TOTP / Time based.
		</p>
		<br>
		<h3>Plain</h3>
		<p>
			<strong>Your private key is</strong>: <?php print $tfa_priv_key; ?> (
				<a href="javascript:if(confirm('WARNING: If you reset this key you will have to update\nyour apps with the new one.\n\nAre you sure you want this?')){ window.location = '<?php print add_query_arg(array('tfa_priv_key_reset' => 1,'settings-updated' => 'true')) ?>'; }">reset</a>
			)
		</p>
		<br>
		<h3>Base32</h3>
		<p>Base32 is used by some third party apps like Google Authenticator.</p>
		<p><strong>Your private key in base32 is</strong>: <?php print Base32\Base32::encode($tfa_priv_key); ?></p>
		<hr>
		<h2>Google Authenticator QR-Code</h2>
		<p>
			Scan this code with Google Authenticator or other app.
		</p>
		<p>
			<img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/<?php print urlencode($current_user->user_login).'@'.$url; ?>%3Fsecret%3D<?php print Base32\Base32::encode($tfa_priv_key); ?>">
		</p>
		<hr>
		<h2>Current One Time Password</h2>
		<p>Reload every now and then and double check with you third party app.</p>
		<p>The current code is: 
			<br><br>
			<strong style="font-size: 3em;"><?php print generateTwoFactorCode($tfa_priv_key); ?></strong>
		</p>
		<?php
	}
	
	?>
</div>