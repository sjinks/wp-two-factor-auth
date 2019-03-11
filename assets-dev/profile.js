/** global: tfaSettings, XMLHttpRequest */
window.addEventListener('DOMContentLoaded', function() {
	var tcm = document.getElementById('tfa-change-method');
	if (tcm) {
		tcm.addEventListener('click', function() {
			var req = new XMLHttpRequest();
			req.addEventListener('readystatechange', function() {
				try {
					if (req.readyState === XMLHttpRequest.DONE) {
						document.getElementById('tfa-delivery-method').innerHTML = req.responseText;
						document.getElementById('tfa-change-method').setAttribute('hidden', 'hidden');
					}
				}
				catch (e) {
					console && console.error(e);
				}
			})
		});

		document.getElementById('tfa-delivery-method').innerHTML = '...';
		req.open('POST', tfaSettings.ajaxurl);
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		req.send('action=tfa-reset-method&_ajax_nonce=' + tfaSettings.nonce + '&uid=' + tfaSettings.uid);
	}
});
