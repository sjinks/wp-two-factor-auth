/** global: tfaSettings, XMLHttpRequest */
window.addEventListener('DOMContentLoaded', function() {
	document.getElementById('tfa-change-method').addEventListener('click', function() {
		var req = new XMLHttpRequest();
		req.addEventListener('readystatechange', function() {
			try {
				if (req.readyState === XMLHttpRequest.DONE) {
					document.getElementById('tfa-delivery-method').innerHTML = req.responseText;
					document.getElementById('tfa-change-method').setAttribute('hidden', 'hidden');
				}
			}
			catch (e) {
				if (console) {
					console.error(e);
				}
			}
		});

		document.getElementById('tfa-delivery-method').innerHTML = '...';
		req.open('POST', tfaSettings.ajaxurl);
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		req.send('action=tfa-reset-method&_ajax_nonce=' + tfaSettings.nonce + '&uid=' + tfaSettings.uid);
	});
});
