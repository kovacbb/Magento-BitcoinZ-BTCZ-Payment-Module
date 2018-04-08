define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component,url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Kovach_Btcz/payment/btcz'
            },
			getIcon: function () {
                return url.build('pub/media/btcz/btcz.png');
            },
        });
    }
);
