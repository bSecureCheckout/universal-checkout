<?php

namespace Bsecure\UniversalCheckout\Block;

class Checkout extends \Magento\Framework\View\Element\Template
{

    protected $bsecureHelper;

    public function __construct(
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper
    ) {
        
        $this->bsecureHelper = $bsecureHelper;
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
