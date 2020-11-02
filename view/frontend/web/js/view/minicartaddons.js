define([
       'ko',
       'uiComponent',
       'Magento_Customer/js/customer-data',
   ], function (ko, Component, customerData) {
       'use strict';      
      
       return Component.extend({          
           /**
            * @override
            */
           initialize: function () {
               this._super();
               this.cart = customerData.get('cart');
           },
           getTotalCartItems: function () {

               return customerData.get('cart')().summary_count;
           }                    

            
       });
   });