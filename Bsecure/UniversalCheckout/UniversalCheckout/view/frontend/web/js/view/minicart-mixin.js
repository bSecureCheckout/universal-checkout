define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'sidebar',
    'mage/translate',
    'mage/dropdown'
], function (Component, customerData, $, ko, _) {
    'use strict';

    var mixin = {
        isButtonEnable: function () {
            console.log("isButtonEnable calling");

            var baseUrl = window.BASE_URL;
            var redirectUrl = baseUrl + 'bsecure/index/checkoutbtn/';
            var sessionData = false;
            

            $.ajax({
                url: redirectUrl,
                type: 'POST',
                async: false,
                dataType: 'json',
                success: function(data, status, xhr) {
                     
                    if(data.success){
                        sessionData = data.sessionData;
                        
                    }
                },
                error: function (xhr, status, errorThrown) {
                    console.log(errorThrown);
                }
            });

            /*You can add your condition here based on your requirements.*/
            //console.log(window.checkoutConfig.session_VIEUserData);
            //You can use 'sessionData' in your condition
            //console.log('sessionData out:',sessionData);
            var obj = {'isactive':true,'img':'http://google.com,'}
            if(sessionData){

                return true; //hide button
            }else {
                return false;  //show button
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});