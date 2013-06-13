jQuery(document).ready(function() {
    var user_field = document.getElementById('user_login');
	var pw_field = document.getElementById('user_pass');
	var submit_btn = document.getElementById('wp-submit');
	var remember_me = document.getElementById('rememberme');
	submit_btn.disabled = true;
	
	var otp_btn = document.createElement('button');
	otp_btn.id = 'otp-button';
	otp_btn.className = 'button button-large button-primary';
	otp_btn.onclick = function(){ return tfaChangeToInput(); };
	otp_btn.style.float = 'none';
	
	var btn_text = document.createTextNode(tfaSettings.click_to_enter_otp);
	otp_btn.appendChild(btn_text);
	otp_btn.style.width = '100%';
	
	var p = document.createElement('p');
	p.id = 'tfa_holder';
	p.style.marginBottom = '15px';
	
	p.appendChild(otp_btn);
	tfaAddToForm(p);
	
	function tfaChangeToInput()
	{
		//Check so a username is entered.
		if(jQuery('#user_login').val().length < 1)
		{
			alert(tfaSettings.enter_username_first);
			return false;
		}
		
		jQuery.post(
			tfaSettings.ajaxurl,
			{
				action : 'tfa-init-otp',
				user : jQuery('#user_login').val()
			},
			function( response ) {
			}
		);
		
		var p = document.getElementById('tfa_holder');
		var lbl = document.createElement('label');
		lbl.for = 'two_factor_auth';
		var lbl_text = document.createTextNode(tfaSettings.otp);
		lbl.appendChild(lbl_text);
		
		var tfa_field = document.createElement('input');
		tfa_field.type = 'text';
		tfa_field.id = 'two_factor_auth';
		tfa_field.name = 'two_factor_code';
		lbl.appendChild(tfa_field);
		
		//Remove button
		p.removeChild(document.getElementById('otp-button'));
		
		//Add text and input field
		p.appendChild(lbl);
		tfa_field.focus();
		
		//Enable regular submit button
		document.getElementById('wp-submit').disabled = false;
	}
	
	function tfaAddToForm(p)
	{
		document.getElementById('loginform').insertBefore(p, document.getElementById('rememberme').parentNode.parentNode);
	}

});