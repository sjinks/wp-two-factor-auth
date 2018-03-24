window.addEventListener('DOMContentLoaded', function() {
	var form = document.getElementById('loginform');
	form.addEventListener('submit', tfaCallback);

	function tfaCallback(e)
	{
		e.preventDefault();
		var res = runGenerateOTPCall();
		form.removeEventListener('submit', tfaCallback);
		return !res;
	}

	function runGenerateOTPCall()
	{
		var username = document.getElementById('user_login').value;
		if (!username) {
			return false;
		}

		var req = new XMLHttpRequest();
		req.addEventListener('readystatechange', function() {
			try {
				if (req.readyState === XMLHttpRequest.DONE) {
					var r = JSON.parse(req.responseText);
					if (r.status === true) {
						tfaShowOTPField();
					}
					else {
						form.submit();
					}
				}
			}
			catch (e) {
				if (console) {
					console.error(e);
				}
			}
		});

		req.open('POST', tfaSettings.ajaxurl);
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		req.send('action=tfa-init-otp&user=' + encodeURIComponent(username));
		return true;
	}

	function tfaShowOTPField()
	{
		var items = form.querySelectorAll('p');
		var len   = items.length;
		for (var i=0; i<len; ++i) {
			var item = items[i];
			item.style.visibility = 'hidden';
			item.style.position   = 'absolute';
		}

		var submit = document.getElementById('wp-submit');
		submit.setAttribute('disabled', 'disabled');

		var html =
			  '<label for="two_factor_auth">' + tfaSettings.otp + '<br/><input type="text" name="two_factor_code" id="two_factor_auth" autocomplete="off"></label>'
			+ '<p class="forgetmenot" style="font-size: small; max-width: 60%">' + tfaSettings.otp_login_help + '</p>'
			+ '<p class="submit"><input id="tfa_login_btn" class="button button-primary button-large" type="submit" value="' + submit.value + '"/></p>'
		;

		form.insertAdjacentHTML('afterbegin', html);
		document.getElementById('two_factor_auth').focus();
	}
});
