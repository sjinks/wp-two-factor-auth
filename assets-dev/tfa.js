/** global: tfaSettings, XMLHttpRequest */
window.addEventListener('DOMContentLoaded', function() {
	var submit    = document.getElementById('wp-submit');
	var login     = document.getElementById('user_login');
	var container = document.getElementById('tfa-block');
	var input     = document.getElementById('two_factor_auth');

	submit.addEventListener('click', tfaCallback);
	container.setAttribute('hidden', '');
	input.setAttribute('disabled', '');

	function loginChangeCallback(e)
	{
		submit.addEventListener('click', tfaCallback);
		login.removeEventListener('change', loginChangeCallback);
	}

	function tfaCallback(e)
	{
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();

		var username = login.value;
		if (!username) {
			return;
		}

		submit.removeEventListener('click', tfaCallback);
		login.addEventListener('change', loginChangeCallback);

		var req = new XMLHttpRequest();
		req.addEventListener('load', function() {
			var r = (typeof this.response === "string") ? JSON.parse(this.response) : this.response;
			if (null === r || this.status !== 200 || !r.status) {
				input.setAttribute('disabled', '');
				container.setAttribute('hidden', '');
				submit.click();
				return;
			}

			input.removeAttribute('disabled');
			container.removeAttribute('hidden');
			input.focus();
		});

		req.open('POST', tfaSettings.ajaxurl);
		req.responseType = 'json';
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		req.send('action=tfa-init-otp&log=' + encodeURIComponent(username));
	}
});
