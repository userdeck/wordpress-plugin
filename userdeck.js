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
			
			if ('installDetected' == msg.event) {
				var account_key = msg.message.account_key;
				var mailbox_id = msg.message.mailbox_id;
				var guides_key = msg.message.guide_key;
				var data = {
					account_key: account_key,
					mailbox_id: mailbox_id,
					guides_key: guides_key
				};
				
				UserDeck.connected = true;
				
				UserDeck.account_key = account_key;
				UserDeck.mailbox_id = mailbox_id;
				UserDeck.guides_key = guides_key;
				
				UserDeck.disableConnect();
				UserDeck.hideConnect();
				
				UserDeck.updateSettings(data);
			}
			else if ('conversationKeysDetected' == msg.event) {
				var account_key = msg.message.account_key;
				var mailbox_id = msg.message.mailbox_id;
				var data = {
					account_key: account_key,
					mailbox_id: mailbox_id
				};
				
				UserDeck.connected = true;
				
				UserDeck.account_key = account_key;
				UserDeck.mailbox_id = mailbox_id;
				
				UserDeck.disableConnect();
				UserDeck.hideConnect();
				
				UserDeck.updateSettings(data);
			}
			else if ('guideKeyDetected' == msg.event) {
				var guides_key = msg.message;
				
				UserDeck.connected = true;
				
				UserDeck.guides_key = guides_key;
				
				UserDeck.disableConnect();
				UserDeck.hideConnect();
				
				UserDeck.updateSettings({guides_key: guides_key});
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