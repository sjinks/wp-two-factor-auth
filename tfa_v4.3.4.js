jQuery(document).ready(function() {
	var tfa_cb = function(e)
	{
		e.preventDefault();
		var res = runGenerateOTPCall();
		
		jQuery('#wp-submit').parents('form:first').off();
		
		if(!res)
			return true;
		
		return false;
	};
	jQuery('#wp-submit').parents('form:first').on('submit', tfa_cb);
});

function runGenerateOTPCall()
{
	var username = jQuery('#user_login').val() || jQuery('[name="log"]').val();
	
	if(!username.length)
		return false;
		
	jQuery.ajax(
	{
		url: tfaSettings.ajaxurl,
		type: 'POST',
		data: {
			action : 'tfa-init-otp',
			user : username
		},
		dataType: 'json',
		success: function(response) {
			if(response.status === true)
				tfaShowOTPField();
			else
				jQuery('#wp-submit').parents('form:first').submit();
		}
	});
	return true;
}

function tfaShowOTPField()
{
	//Hide all elements in sa browser safe way
	jQuery('#wp-submit').parents('form:first').find('p').each(function(i)
	{
		jQuery(this).css('visibility','hidden').css('position', 'absolute');
	});
	jQuery('#wp-submit').attr('disabled', 'disabled');
	
	//Add new field and controls
	var html = '';
	html += '<label for="two_factor_auth">' + tfaSettings.otp + '<br><input type="text" name="two_factor_code" id="two_factor_auth" autocomplete="off"></label>';
	html += '<p class="forgetmenot" style="font-size:small; max-width: 60%">' + tfaSettings.otp_login_help + '</p>';
	html += '<p class="submit"><input id="tfa_login_btn" class="button button-primary button-large" type="submit" value="' + jQuery('#wp-submit').val() + '"></p>';
	
	jQuery('#wp-submit').parents('form:first').append(html);
	jQuery('#two_factor_auth').focus();
}
