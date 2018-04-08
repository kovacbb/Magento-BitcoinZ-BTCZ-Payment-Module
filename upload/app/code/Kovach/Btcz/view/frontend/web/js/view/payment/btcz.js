define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'btcz',
                component: 'Kovach_Btcz/js/view/payment/method-renderer/btcz-method'
            }
        );
        return Component.extend({});
    }
);