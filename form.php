<?php
login_header('Enter Two Factor Login Code', '<p style="'.($is_activated_for_user ? '' : 'display:none;').'margin: 20px 0 20px 10px"><strong>Two Factor Login is activated.</strong><br>Check your email for your code.</p>');

$html = '
<form action="" method="post">
	<input type="hidden" name="log" value="'.$params['log'].'">
	<input type="hidden" name="rememberme" value="'.$params['rememberme'].'">
	<input type="hidden" name="two_factor_code_submitted" value="1">
	<input type="hidden" name="redirect_to" value="'.$params['redirect_to'].'">
	<input type="hidden" name="testcookie" value="'.$params['testcookie'].'">
	<p>
		<label for="user_pass">Password<br>
			<input type="password" name="pwd" id="user_pass" class="input" value="" size="20">
		</label>
	</p>
	<p style="'.($is_activated_for_user ? '' : 'display:none').'">
		<label for"two_factor_code">One time code (check your email)<br>
			<input type="text" autocomplete="off" class="input" style="text-transform: uppercase; background: white url('.plugin_dir_url(__FILE__).'/email_16.gif) no-repeat 4px 10px; color:green; text-indent:20px;" name="two_factor_code">
		</label>
	</p>
	<br>
	<input type="submit" class="button button-primary button-large" value="Log In">
</form>';

print $html;

login_footer();
?>
