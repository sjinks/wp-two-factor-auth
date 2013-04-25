<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Two Factor Auth Settings</h2>
	
	<form method="post" action="options.php" style="margin-top: 80px">
	<?php
		settings_fields('tfa_user_roles_group');
	?>
		<h3>User Roles</h3>
		Choose which User Roles that will have <em>Two Factor Auth</em> activated.
		<p>
	<?php
		tfaListUserRolesCheckboxes();
	?></p>
	<?php submit_button(); ?>
	</form>
</div>