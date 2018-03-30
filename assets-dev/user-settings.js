window.addEventListener('DOMContentLoaded', function() {
	document.getElementById('tfa-reset-form').addEventListener('submit', function(e) {
		if (!confirm(tfaSettings.confirm)) {
			e.preventDefault();
		}
	});

	function makeRequest(params, callback)
	{
		var req = new XMLHttpRequest();
		req.addEventListener('readystatechange', function() {
			try {
				if (req.readyState === XMLHttpRequest.DONE) {
					callback(req.responseText);
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
		req.send(params);
	}

	function refreshCode()
	{
		var otp = document.getElementById('otp');
		otp.innerHTML = tfaSettings.refreshing;
		makeRequest(
			'action=tfa-refresh-code&_ajax_nonce=' + tfaSettings.nonce,
			function(t)
			{
				otp.innerHTML = t;
			}
		);
	}

	document.getElementById('refresh-code').addEventListener('click', refreshCode);

	document.getElementById('verify').addEventListener('click', function() {
		var vr   = document.getElementById('verify-result');
		var code = document.getElementById('otpcode').value;
		refreshCode();
		vr.innerHTML = '';
		makeRequest(
			'action=tfa-verify-code&_ajax_nonce=' + tfaSettings.vnonce + '&code=' + encodeURIComponent(code),
			function(t)
			{
				vr.innerHTML = t;
			}
		);
	});
});
