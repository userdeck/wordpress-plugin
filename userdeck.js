var UserDeck = {
	
	connected: false,

	account_key: null,

	mailbox_id: null,
	
	guides_key: null,
	
	showConnect : function (type, start) {
		var wrapper = jQuery('#connect-frame');
		
		if (!start) {
			start = 'install';
		}
		else {
			start = 'install/' + start;
		}

		var iframe = jQuery('<iframe id="iframe-guides" src="https://app.userdeck.com/' + type + '?redir=' + start + '" width="400" height="600" frameborder="0" ALLOWTRANSPARENCY="true"></iframe>')

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
		jQuery('#feature-wrapper').hide();
	},
	
	updateSettings : function (options) {
		options['userdeck-submit'] = 1;
		options['_wpnonce'] = plugin_settings_nonce;
		
		jQuery.post(plugin_url, options, function () {
			window.location.reload();
		});
	},
	
	_receiveMessage : function (event) {
		if (event.data && 'string' === typeof event.data && 'ud:' == event.data.substr(0, 3)) {
			var msg = jQuery.parseJSON(event.data.substr(3));
			var data = {};
			
			UserDeck.connected = true;
			
			if ('installDetected' == msg.event) {
				data.account_key = msg.message.account_key;
				data.mailbox_id = msg.message.mailbox_id;
				data.guides_key = msg.message.guide_key;
				
				UserDeck.account_key = account_key;
				UserDeck.mailbox_id = mailbox_id;
				UserDeck.guides_key = guides_key;
			}
			else if ('conversationKeysDetected' == msg.event) {
				data.account_key = msg.message.account_key;
				data.mailbox_id = msg.message.mailbox_id;
				
				UserDeck.account_key = account_key;
				UserDeck.mailbox_id = mailbox_id;
			}
			else if ('guideKeyDetected' == msg.event) {
				data.guides_key = msg.message;
				
				UserDeck.guides_key = guides_key;
			}
			else {
				return;
			}
			
			UserDeck.disableConnect();
			UserDeck.hideConnect();
			
			UserDeck.updateSettings(data);
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