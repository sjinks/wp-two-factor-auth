<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('Two Factor Auth Settings', 'wwatfa'); ?></h1>

	<?php settings_errors(); ?>

	<noscript>
		<div class="error">
			<p><?=__('Please turn JavaScript on for this page to function properly.', 'wwatfa'); ?></p>
		</div>
	</noscript>

	<section>
		<h2><?=__('Delivery Type', 'wwatfa');?></h2>
		<p><?=__('Please choose how you want your one time passwords delivered:', 'wwatfa'); ?></p>

		<form action="<?=admin_url('admin-post.php'); ?>" method="post">
			<input type="hidden" name="action" value="tfa_save_user_settings"/>
			<input type="hidden" name="uid" value="<?=$options['uid'];?>"/>
			<?=wp_nonce_field("save-tfauser_" . $options['uid']);?>

<?php if (!$options['forced']) : ?>
			<label><input type="radio" name="tfa[delivery]" value=""<?php checked('', $options['delivery_type']); ?>/> <?=__('Disabled', 'wwatfa');?></label><br/>
<?php endif; ?>
			<label><input type="radio" name="tfa[delivery]" value="email"<?php checked('email', $options['delivery_type']); ?>/> <?=__('By email', 'wwatfa');?></label><br/>
			<label><input type="radio" name="tfa[delivery]" value="third-party-apps"<?php checked('third-party-apps', $options['delivery_type']); ?>/> <?=__('Via third party applications (Duo Mobile, Google Authenticator, etc)', 'wwatfa');?></label><br/>

			<?php submit_button(); ?>
		</form>

		<p><?=__('If you choose "third party applications" delivery type, you have to make sure you scan the QR code (or enter your private key manually).', 'wwatfa'); ?></p>
	</section>

<?php if ('third-party-apps' === $options['delivery_type']) : ?>
	<section>
		<h2><?=__('Third Party Application Set Up', 'wwatfa');?></h2>

		<img src="<?=$options['qrimg'];?>" alt="<?=__('QR Code', 'wwatfa');?>" class="alignleft qrcode"/>
		<p><?=__('Scan this code with Duo Mobile, Google Authenticator, or any other application whcih supports 6 digit OTPs.', 'wwatfa');?></p>
		<p>
			<?=sprintf(__('You are currently using <strong>%1$s</strong> (%2$s) algorithm, in case the application asks for that information'), strtoupper($options['algo']), ('totp' == $options['algo'] ? __('time based', 'wwatfa') : __('event based', 'wwatfa')));?>
			<input type="submit" form="tfa-change-algo" value="<?=sprintf(__('(change to %1$s&nbsp;&mdash;&nbsp;%2$s)'), ('totp' == $options['algo'] ? 'HOTP' : 'TOTP'), ('hotp' == $options['algo'] ? __('time based', 'wwatfa') : __('event based', 'wwatfa'))); ?>" class="button-link"/>).
			<?php if ($options['counter']) : ?>
			<br/>
			<?=sprintf(__('The counter value on this server is currently <code>%s</code>.', 'wwatfa'), $options['counter']); ?>
			<?php endif; ?>
		</p>
		<p<?php if ('hotp' == $options['algo']) : ?> hidden="hidden"<?php endif; ?>>
			<?=sprintf(__('The current one time password is:', 'wwatfa')); ?><br/>
			<code class="otp" id="otp"><?=esc_html($options['otp']);?></code>
			<span class="button-link hide-if-no-js" id="refresh-code"><?=__('(refresh code)', 'wwatfa'); ?></span>
		</p>
		<p>
			<?=sprintf(__('Your private key is <code>%s</code>', 'wwatfa'), $options['privkey']); ?>
			<input type="submit" form="tfa-reset-form" value="<?=__('(Reset)', 'wwatfa');?>" class="button-link button-link-delete"/>
		</p>
		<p><?=sprintf(__('Your private key in base32 is <code>%s</code>', 'wwatfa'), $options['privkey32']); ?></p>

		<br class="clear"/>
	</section>

	<section class="hide-if-no-js">
		<h2><?=__('Verify OTP Configuration', 'wwatfa');?></h2>

		<p><?=__('Please use this form to make sure that you have configured your application correctly.', 'wwatfa'); ?></p>
		<p><?=__('Please enter the 6 digit code shown by your application into the field below and then click Verify button.', 'wwatfa'); ?></p>
		<p>
			<?=__('If code verification fails, please activate code delivery by email and contact your site administrator.', 'wwatfa'); ?>
			<span class="wp-ui-text-notification"><?=__('Otherwise, you will probably be unable to log into this site anymore.', 'wwatfa'); ?></span>
		</p>
		<p>
			<input type="text" value="" id="otpcode" placeholder="<?=esc_attr__('Enter OTP value', 'wwatfa');?>" autocomplete="off"/><button type="button" class="button button-primary" id="verify"><?=__('Verify', 'wwatfa') ?></button>
			<br/>
			<span id="verify-result"></span>
		</p>
	</section>

	<form action="<?=admin_url('admin-post.php'); ?>" method="post" id="tfa-reset-form">
		<input type="hidden" name="action" value="tfa_reset_key"/>
		<?=wp_nonce_field("reset-tfauser_" . $options['uid']);?>
	</form>

	<form action="<?=admin_url('admin-post.php'); ?>" method="post" id="tfa-change-algo">
		<input type="hidden" name="action" value="tfa_save_user_method"/>
		<input type="hidden" name="tfa[method]" value="<?=('totp' == $options['algo'] ? 'hotp' : 'totp') ?>"/>
		<?=wp_nonce_field("save-tfamethod_" . $options['uid']);?>
	</form>

	<section>
		<h2><?=__('Backup Codes', 'wwatfa'); ?></h2>
		<p>
			<?=__("You have backup codes which can be used if you do not have access to your phone or unable to get your one time passwords. Each code can be used only once.", 'wwatfa'); ?>
			<?=__('Please keep them in a safe place, they are just as secret as your private key is.', 'wwatfa'); ?>
		</p>
		<p><strong><?=__('Your backup codes are:', 'wwatfa'); ?></strong></p>

		<form action="<?=admin_url('admin-post.php'); ?>" method="post" id="tfa-reset-panic">
<?php if ($options['panic']) : ?>
			<ol>
<?php foreach ($options['panic'] as $code) : ?>
				<li><code><?=esc_html($code);?></code></li>
<?php endforeach; ?>
			</ol>
<?php endif; ?>
			<input type="submit" value="<?=__('Generate new codes', 'wwatfa');?>" class="button button-primary"/>
			<input type="hidden" name="action" value="tfa_reset_panic"/>
			<?=wp_nonce_field("reset-tfapanic_" . $options['uid']);?>
		</form>
	</section>
<?php endif; ?>
</div>
