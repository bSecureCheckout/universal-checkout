<?php

namespace Bsecure\UniversalCheckout\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as MageLayoutProcessor;

class LayoutProcessor
{
    
    public function __construct(
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper
    ) {
        
        $this->bsecureHelper = $bsecureHelper;
    }

    /**
     * @param MageLayoutProcessor $subject
     * @param $jsLayout
     * @return mixed
     */
    public function afterProcess(MageLayoutProcessor $subject, $jsLayout)
    {

        if ($this->bsecureHelper->getConfig('universalcheckout/general/enable') == 0) {

            return $jsLayout;
           
        }

        if ($this->bsecureHelper->getConfig('universalcheckout2/general2/auto_append_country_code') == 0) {
            
            return $jsLayout;
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
            ['telephone'] = $this->bsecureHelper->telephoneFieldConfig("shippingAddress");
        }

        /* config: checkout/options/display_billing_address_on = payment_method */
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'])) {

            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                     ['payment']['children']['payments-list']['children'] as $key => $payment) {

                $method = substr($key, 0, -5);

                /* telephone */
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                ['telephone'] = $this->bsecureHelper->telephoneFieldConfig("billingAddress", $method);
            }
        }

        /* config: checkout/options/display_billing_address_on = payment_page */
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['afterMethods']['children']['billing-address-form'])) {

            $method = 'shared';

            /* telephone */
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
            ['telephone'] = $this->bsecureHelper->telephoneFieldConfig("billingAddress", $method);
        }

        return $jsLayout;
    }
}
