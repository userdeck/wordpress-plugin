var UserDeck = {
    
    showConnect : function (type, start) {
        var wrapper = jQuery('#connect-frame');
        
        if (!start) {
            start = 'install';
        }
        else {
            start = 'install/' + start;
        }
        
        var url = 'https://app.userdeck.com/' + start + '?type=' + type;
        var iframe = jQuery('<iframe id="iframe-connect" src="' + url + '" width="400" height="600" frameborder="0" ALLOWTRANSPARENCY="true"></iframe>');

        wrapper.append(iframe);

        iframe.on('load', function () {
            iframe.show();
        });

        UserDeck.disableConnect();
    },
    
    hideConnect : function () {
        jQuery('#iframe-connect').remove();
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
            
            if ('installDetected' == msg.event) {
                data.account_key = msg.message.account_key;
                data.mailboxes = msg.message.mailboxes;
                data.guides = msg.message.guides;
            }
            else if ('conversationKeysDetected' == msg.event) {
                data.account_key = msg.message.account_key;
                data.mailboxes = msg.message.mailboxes;
            }
            else if ('guideKeyDetected' == msg.event) {
                data.guides = msg.message.guides;
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
