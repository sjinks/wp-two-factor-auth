<?php defined('ABSPATH') || die(); ?>
<h3><?=__('Two Factor Authentication', 'wwatfa');?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th><?=__('Enabled for this user', 'wwatfa'); ?>
			<td><?=($options['enabled'] ? __('Yes', 'wwatfa') : __('No', 'wwatfa'));?>
		</tr>
<?php if ($options['enabled']) : ?>
		<tr>
			<th><?=__('OTP delivery method', 'wwatfa');?></th>
			<td>
				<span id="tfa-delivery-method"><?=$options['delivery'];?></span>
<?php if ('email' !== $options['method']) : ?>
				<span class="button-link hide-if-no-js" id="tfa-change-method"><?=__('(change to "By email")', 'wwatfa');?></span>
<?php endif; ?>
			</td>
		</tr>
<?php endif; ?>
	</tbody>
</table>
