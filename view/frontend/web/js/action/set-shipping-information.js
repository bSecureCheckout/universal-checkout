define([
 'jquery',
 'mage/utils/wrapper',
 'Magento_Checkout/js/model/quote',
 'Magento_Checkout/js/model/shipping-service',
 'Magento_Checkout/js/model/shipping-rate-registry',
 'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
 'Magento_Checkout/js/model/shipping-rate-processor/new-address',
 ], function ($, wrapper, quote, shippingService, rateRegistry, customerAddressProcessor, newAddressProcessor) {
    'use strict';


     $(document).on('change',"[name='country_id']",function () {
        //for country
        var country_id = $(this).val();
        var baseUrl = window.BASE_URL + 'bsecure/index/bsecureajax';
        country_id = country_id.toLowerCase();
        jQuery("input[name='telephone']").intlTelInput("setCountry", country_id);
        
     });

     
 });