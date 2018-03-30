<?php defined('ABSPATH') || die(); ?>
<h3><?=__('Two Factor Authentication', 'two-facor-auth');?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th><?=__('Enabled for this user', 'two-factor-auth'); ?>
			<td><?=($options['enabled'] ? __('Yes', 'two-factor-auth') : __('No', 'two-factor-auth'));?>
		</tr>
<?php if ($options['enabled']) : ?>
		<tr>
			<th><?=__('OTP delivery method', 'two-factor-auth');?></th>
			<td>
				<span id="tfa-delivery-method"><?=$options['delivery'];?></span>
<?php if ('email' !== $options['method']) : ?>
				<span class="button-link hide-if-no-js" id="tfa-change-method"><?=__('(change to "By email")', 'two-factor-auth');?></span>
<?php endif; ?>
			</td>
		</tr>
<?php endif; ?>
	</tbody>
</table>
