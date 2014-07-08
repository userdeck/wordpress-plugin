var UserDeck = {
	
	connected: false,
	
	guides_key: null,
	
	showConnect : function (type) {
		var wrapper = jQuery('#userdeck-wrapper');

		var iframe = jQuery('<iframe id="iframe-guides" src="http://app.userdeck.com/' + type + '?redir=install/guides" width="400" height="600" frameborder="0" ALLOWTRANSPARENCY="true"></iframe>')

		wrapper.append(iframe);

		iframe.on('load', function () {
			iframe.show();
		});

		UserDeck.disableConnect();
	},
	
	hideConnect : function () {
		jQuery('#iframe-guides').remove();
	},
	
	disableConnect : function () {
		jQuery('#button-connect').hide();
		//.removeClass('button-primary').addClass('button-secondary')
	},
	
	_receiveMessage : function (event) {
		if (event.data && 'string' === typeof event.data && 'ud:' == event.data.substr(0, 3)) {
			var msg = jQuery.parseJSON(event.data.substr(3));
			
			if ('guideKeyDetected' == msg.event) {
				var guides_key = msg.message;
				
				UserDeck.connected = true;
				
				UserDeck.guides_key = guides_key;
				
				UserDeck.disableConnect();
				
				UserDeck.hideConnect();
				
				jQuery.post(plugin_url, {'userdeck-submit': 1, guides_key: guides_key, _wpnonce: plugin_settings_nonce}, function () {
					window.location.reload();
				});
			}
		}
	},
	
};

jQuery(function() {
	
	if (window.addEventListener) {
		window.addEventListener('message', UserDeck._receiveMessage, false);
	}
	else if (window.attachEvent) {
		window.attachEvent('onmessage', UserDeck._receiveMessage, false);
	}
	
});