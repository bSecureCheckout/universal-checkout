<?php

namespace Bsecure\UniversalCheckout\Plugin;
 
class ApplyShipping
{
 
    public function __construct()
    {
        
    }
 
    public function aroundCollectCarrierRates(
        \Magento\Shipping\Model\Shipping $subject,
        \Closure $proceed,
        $carrierCode,
        $request
    )
    {
       
            // Enter Shipping Code here instead of 'freeshipping'
        if ($carrierCode == 'bsecureshipping') {
            //var_dump($carrierCode); die;
           // To disable the shipping method return false
            return false;
        } 
           // To enable the shipping method
            return $proceed($carrierCode, $request);
    }
}