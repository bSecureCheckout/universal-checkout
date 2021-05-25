/*
* bSecure
*/


var config = {
    paths: {
        "intlTelInput": 'Bsecure_UniversalCheckout/js/intlTelInput',
        "intlTelInputUtils": 'Bsecure_UniversalCheckout/js/utils',
        "internationalTelephoneInput": 'Bsecure_UniversalCheckout/js/internationalTelephoneInput'
    },

    shim: {
        'intlTelInput': {
            'deps':['jquery', 'knockout']
        },
        'internationalTelephoneInput': {
            'deps':['jquery', 'intlTelInput']
        }
    }
    
    
};