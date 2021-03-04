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
                type: 'bsecurepayment',
                component: 'Bsecure_UniversalCheckout/js/view/payment/method-renderer/bsecurepayment'
            }
        );
        return Component.extend({});
    }
);