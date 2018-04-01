<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('Two Factor Auth Settings', 'wwatfa'); ?></h1>

	<form action="<?=esc_attr(admin_url('options.php'));?>" method="post">
	<?php
	settings_fields('two-factor-auth');
	do_settings_sections('two-factor-auth');
	submit_button();
	?>
	</form>
</div>