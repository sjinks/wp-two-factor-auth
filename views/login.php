<?php defined('ABSPATH') || die(); ?>
<p id="tfa-block">
	<label for="two_factor_auth"><?php _e('One Time Password', 'two-factor-auth'); ?><br/>
		<input type="text" name="two_factor_code" id="two_factor_auth" autocomplete="off" disabled="disabled"/>
	</label>
	<span><?php _e('(check your email or OTP application to get the password)', 'two-factor-auth'); ?></span>
</p>