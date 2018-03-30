<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('Two Factor Auth Settings', 'two-factor-auth'); ?></h1>

	<?php settings_errors(); ?>

	<noscript>
		<div class="error">
			<p><?=__('Please turn JavaScript on for this page to function properly.', 'two-factor-auth'); ?></p>
		</div>
	</noscript>

	<section>
		<h2><?=__('Delivery Type', 'two-factor-auth');?></h2>
		<p><?=__('Please choose how you want your one time passwords delivered:', 'two-factor-auth'); ?></p>

		<form action="<?=admin_url('admin-post.php'); ?>" method="post">
			<input type="hidden" name="action" value="tfa_save_user_settings"/>
			<input type="hidden" name="uid" value="<?=$options['uid'];?>"/>
			<?=wp_nonce_field("save-tfauser_" . $options['uid']);?>

			<label><input type="radio" name="tfa[delivery]" value="email"<?php checked('email', $options['delivery_type']); ?>/> <?=__('By email', 'two-factor-auth');?></label><br/>
			<label><input type="radio" name="tfa[delivery]" value="third-party-apps"<?php checked('third-party-apps', $options['delivery_type']); ?>/> <?=__('Via third party applications (Duo Mobile, Google Authenticator, etc)', 'two-factor-auth');?></label><br/>

			<?php submit_button(); ?>
		</form>

		<p><?=__('If you choose "third party applications" delivery type (which is more safe and is the real two factor authentication), you have to make sure you scan the QR code (or enter your private key manually).', 'two-factor-auth'); ?></p>
	</section>

<?php if ('third-party-apps' === $options['delivery_type']) : ?>
	<section>
		<h2><?=__('Third Party Application Set Up', 'two-factor-auth');?></h2>

		<img src="<?=$options['qrimg'];?>" alt="<?=__('QR Code', 'two-factor-auth');?>" class="alignleft qrcode"/>
		<p><?=__('Scan this code with Duo Mobile, Google Authenticator, or any other application whcih supports 6 digit OTPs.', 'two-factor-auth');?></p>
		<p>
			<?=sprintf(__('You are currently using <strong>%1$s</strong> (%2$s) algorithm, in case the application asks for that information'), strtoupper($options['algo']), ('totp' == $options['algo'] ? __('time based', 'two-factor-auth') : __('event based', 'two-factor-auth')));?>
			<input type="submit" form="tfa-change-algo" value="<?=sprintf(__('(change to %1$s&nbsp;&mdash;&nbsp;%2$s)'), ('totp' == $options['algo'] ? 'HOTP' : 'TOTP'), ('hotp' == $options['algo'] ? __('time based', 'two-factor-auth') : __('event based', 'two-factor-auth'))); ?>" class="button-link"/>).
			<?php if ($options['counter']) : ?>
			<br/>
			<?=sprintf(__('The counter value on this server is currently <code>%s</code>.', 'two-factor-auth'), $options['counter']); ?>
			<?php endif; ?>
		</p>
		<p>
			<?=sprintf(__('The current one time password is:', 'two-factor-auth')); ?><br/>
			<code class="otp" id="otp"><?=esc_html($options['otp']);?></code>
			<span class="button-link hide-if-no-js" id="refresh-code"><?=__('(refresh code)', 'two-factor-auth'); ?></span>
		</p>
		<p>
			<?=sprintf(__('Your private key is <code>%s</code>', 'two-factor-auth'), $options['privkey']); ?>
			<input type="submit" form="tfa-reset-form" value="<?=__('(Reset)', 'two-factor-auth');?>" class="button-link button-link-delete"/>
		</p>
		<p><?=sprintf(__('Your private key in base32 is <code>%s</code>', 'two-factor-auth'), $options['privkey32']); ?></p>

		<br class="clear"/>
	</section>

	<section class="hide-if-no-js">
		<h2><?=__('Verify OTP Configuration', 'two-factor-auth');?></h2>

		<p><?=__('Please use this form to make sure that you have configured your application correctly.', 'two-factor-auth'); ?></p>
		<p><?=__('Please enter the 6 digit code shown by your application into the field below and then click Verify button.', 'two-factor-auth'); ?></p>
		<p>
			<?=__('If code verification fails, please activate code delivery by email and contact your site administrator.', 'two-factor-auth'); ?>
			<span class="wp-ui-text-notification"><?=__('Otherwise, you will probably be unable to log into this site anymore.', 'two-factor-auth'); ?></span>
		</p>
		<p>
			<input type="text" value="" id="otpcode" placeholder="<?=esc_attr__('Enter OTP value', 'two-factor-auth');?>" autocomplete="off"/><button type="button" class="button button-primary" id="verify"><?=__('Verify', 'two-factor-auth') ?></button>
			<br/>
			<span id="verify-result"></span>
		</p>
	</section>

	<form action="<?=admin_url('admin-post.php'); ?>" method="post" id="tfa-reset-form">
		<input type="hidden" name="action" value="tfa_reset_key"/>
		<input type="hidden" name="uid" value="<?=$options['uid'];?>"/>
		<?=wp_nonce_field("reset-tfauser_" . $options['uid']);?>
	</form>

	<form action="<?=admin_url('admin-post.php'); ?>" method="post" id="tfa-change-algo">
		<input type="hidden" name="action" value="tfa_save_user_method"/>
		<input type="hidden" name="uid" value="<?=$options['uid'];?>"/>
		<input type="hidden" name="tfa[method]" value="<?=('totp' == $options['algo'] ? 'hotp' : 'totp') ?>"/>
		<?=wp_nonce_field("save-tfamethod_" . $options['uid']);?>
	</form>

	<section>
		<h2><?=__('Panic Codes', 'two-factor-auth'); ?></h2>
		<p><?=__("You have panic codes that can be used if you lose your phone and cannot get your one time passwords. Each code can only be used once.", 'two-factor-auth'); ?></p>
		<p><?=__('Keep them in a safe place.', 'two-factor-auth'); ?></p>
		<p><strong><?=__('Your panic codes are:', 'two-factor-auth'); ?></strong></p>
<?php if ($options['panic']) : ?>
		<ol>
<?php foreach ($options['panic'] as $code) : ?>
			<li><code><?=esc_html($code);?></code></li>
<?php endforeach; ?>
		</ol>
<?php endif; ?>
	</section>
<?php endif; ?>
</div>
