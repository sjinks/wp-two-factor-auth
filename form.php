<?php
login_header('Enter Two Factor Login Code', '<p style="margin: 20px 0 20px 10px"><strong>Two Factor Login is activated.</strong><br>Check your email for your code.</p>');

$html = '
<form action="" method="post">
	<input type="hidden" name="log" value="'.$params['log'].'">
	<input type="hidden" name="pwd" value="'.$params['pwd'].'">
	<input type="hidden" name="rememberme" value="'.$params['rememberme'].'">
	<input type="hidden" name="redirect_to" value="'.$params['redirect_to'].'">
	<input type="hidden" name="testcookie" value="'.$params['testcookie'].'">
	<input type="text" class="input" style="text-transform: uppercase; background: white url('.plugin_dir_url(__FILE__).'/email_16.gif) no-repeat 4px 10px; color:green; text-indent:20px;" name="two_factor_code">
	<br>
	<input type="submit" class="button button-primary button-large" value="Submit Code">
</form>';

print $html;

login_footer();
?>
