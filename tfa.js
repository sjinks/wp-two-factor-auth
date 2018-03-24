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
		var tfa = document.getElementById('two_factor_auth');
		tfa.removeAttribute('disabled');
		document.getElementById('tfa').style.display = 'block';
		tfa.focus();
	}
});
