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

        $.ajax({
            url: baseUrl,
            type: 'POST',
            async: false,
            dataType: 'json',
            data: {"action":"get_country_calling_code","country_id":country_id},
            success: function (data, status, xhr) {
                 
                //console.log(data);
            },
            error: function (xhr, status, errorThrown) {
                //console.log(errorThrown);
            }
        });
        
     });

     
 });