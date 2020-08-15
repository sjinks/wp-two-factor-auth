/** global: tfaSettings, XMLHttpRequest */
(function() {
	function callback()
	{
		var tcm = document.getElementById('tfa-change-method');
		if (tcm) {
			tcm.addEventListener('click', function() {
				var req = new XMLHttpRequest();
				req.addEventListener('load', function() {
					try {
						document.getElementById('tfa-delivery-method').innerHTML = req.responseText;
						document.getElementById('tfa-change-method').setAttribute('hidden', '');
					}
					catch (e) {
						console.error(e);
					}
				});
			});

			document.getElementById('tfa-delivery-method').innerHTML = '...';
			req.open('POST', tfaSettings.ajaxurl);
			req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			req.send('action=tfa-reset-method&_ajax_nonce=' + encodeURIComponent(tfaSettings.nonce) + '&uid=' + encodeURIComponent(tfaSettings.uid));
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback);
	}
	else {
		callback();
	}
})();
