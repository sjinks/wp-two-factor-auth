<?php defined('ABSPATH') || die(); ?>
<p id="tfa-block">
	<label for="two_factor_auth"><?php _e('One Time Password', 'wwtfa'); ?><br/>
		<input type="text" name="two_factor_code" id="two_factor_auth" autocomplete="off" spellcheck="false"/>
	</label>
	<span><?php _e('(check your email or OTP application to get the password)', 'wwtfa'); ?></span>
</p>
