define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function (
        Component,
        rendererList,
        ko
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'bsecurepayment',
                component: 'Bsecure_UniversalCheckout/js/view/payment/method-renderer/bsecurepayment'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({
            afterPlaceOrder: function () {
                window.location.replace(url.build('mymodule/standard/redirect/'));
            }
        });
    }
);