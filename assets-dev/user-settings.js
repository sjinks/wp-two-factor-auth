/** global: tfaSettings, XMLHttpRequest */
(function() {
	function callback()
	{
		document.getElementById('tfa-reset-form').addEventListener('submit', function(e) {
			if (!confirm(tfaSettings.confirm)) {
				e.preventDefault();
			}
		});

		function makeRequest(params, callback)
		{
			var req = new XMLHttpRequest();
			req.addEventListener('load', function() {
				try {
					callback(req.responseText);
				}
				catch (e) {
					console && console.error(e);
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
					otp.innerText = t;
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
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback);
	}
	else {
		callback();
	}
}());
