<?php

if(!is_admin())
	exit;

if(@$_GET['upgrade_done'] == 'true')
{
	?>
		<div id="setting-error-settings_updated" class="updated settings-error"> 
			<p>
				<strong>Your upgrade was successful, Two Factor Auth is now enabled.</strong>
			</p>
		</div>
	<?php
}

?><div class="wrap">
	<?php screen_icon(); ?>
	<h2>Two Factor Auth Settings</h2>
	<div>
		<img style="margin-top: 10px" src="<?php print plugin_dir_url(__FILE__); ?>img/tfa_header.png">
	</div>
	<form method="post" action="options.php" style="margin-top: 40px">
	<?php
		settings_fields('tfa_user_roles_group');
	?>
		<h2>User Roles</h2>
		Choose which User Roles that will have <em>Two Factor Auth</em> activated.
		<br>
		The users will default to have their One Time Codes delivered by email since they need to add their private 
		key to third party apps themselves.
		<p>
	<?php
		tfaListUserRolesCheckboxes();
	?></p>
	<?php submit_button(); ?>
	</form>
	<hr>
	<h2>Change User Settings</h2>
	<p>
		If some of your users lose their phone and don't have access to their panic codes, you can reset their 
		delivery type here and change it to email so they can login again and add a new phone.
		<br>
		Click on the "Change to email" button to change the delivery settings for that user.
		<br>
		Users can change their own settings on Users -> Two Factor Auth when they're logged in.
	<p>
		<?php
		
		//List users and type of tfa
		foreach($wp_roles->role_names as $id => $name)
		{	
			$setting = get_option('tfa_'.$id);
			$setting = $setting === false || $setting ? 1 : 0;
			if(!$setting)
				continue;
			
			$users_q = new WP_User_Query( array(
			  'role' => $name
			));
			$users = $users_q->get_results();
			
			if(!$users)
				continue;
			
			print '<h3>'.$name.'s</h3>';
			
			foreach( $users as $user )
			{
				$userdata = get_userdata( $user->ID );
				$tfa_type = get_user_meta($user->ID, 'tfa_delivery_type', true);
				print '<span style="font-size: 1.2em">'.esc_attr( $userdata->user_nicename ).'</span>';
				if(!$tfa_type || $tfa_type == 'email')
					print ' - Email';
				else
					print ' - <a class="button" href="'.add_query_arg(array('tfa_change_to_email' => 1, 'tfa_user_id' => $user->ID)).'">Change to email</a>';
				print '<br>';
			}
		}
		
		?>
	</p>
	<hr>
	<h2>Rate it if you like it!</h2>
	<p>
		Please, rate this plugin with ✭✭✭✭✭ if you like it so more users can take advantage of a more secure login 
		without having third party dependencies.
		<br>
		<br>
		<a href="http://wordpress.org/plugins/two-factor-auth/" target="_blank">Two Factor Auth on Wordpress.org >></a>
	</p>
	<p>
		Feel free to contact me at any time. 
		<br>
		<a href="http://oskarhane.com" target="_blank">http://oskarhane.com</a>
	</p>
</div>