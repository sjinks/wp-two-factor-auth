/** global: tfaSettings, XMLHttpRequest */
window.addEventListener('DOMContentLoaded', function() {
	var form = document.getElementById('loginform');
	form.addEventListener('submit', tfaCallback);

	function tfaCallback(e)
	{
		e.preventDefault();
		form.removeEventListener('submit', tfaCallback);

		var username = document.getElementById('user_login').value;
		if (!username) {
			return true;
		}

		var req = new XMLHttpRequest();
		req.addEventListener('readystatechange', function() {
			try {
				if (req.readyState === XMLHttpRequest.DONE) {
					var r = JSON.parse(req.responseText);
					if (r.status === true) {
						var tfa = document.getElementById('two_factor_auth');
						tfa.removeAttribute('disabled');
						document.getElementById('tfa-block').style.display = 'block';
						tfa.focus();
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
		req.send('action=tfa-init-otp&log=' + encodeURIComponent(username));
		return false;
	}
});
