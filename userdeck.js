var UserDeck = {
	
	connected: false,
	
	guides_key: null,
	
	showConnect : function () {
		jQuery('#guides-iframe').show();
	},
	
	hideConnect : function () {
		jQuery('#guides-iframe').hide();
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
				
				window.location.replace(window.location.href + '&guides_key=' + guides_key);
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