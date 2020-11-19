<?php

namespace Bsecure\UniversalCheckout\Block;

class Login extends \Magento\Framework\View\Element\Template
{
 
    protected $cartHelper;
     protected $bsecureHelper;

    public function __construct(
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper
    ) {
       
        $this->cartHelper = $cartHelper;
        $this->bsecureHelper = $bsecureHelper;
    }

    public function getCartHelper()
    {
        return $this->cartHelper;
    }

    public function getBsecureHelper()
    {
        return $this->bsecureHelper;
    }

    public function getBsecureSettings($key)
    {
        return $this->bsecureHelper->getConfig('universalcheckout/general/'.$key);
    }
}
