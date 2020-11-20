<?php

namespace Bsecure\UniversalCheckout\Block;

class Checkout extends \Magento\Framework\View\Element\Template
{

    protected $bsecureHelper;

    public function __construct(
    	\Magento\Framework\View\Element\Template\Context  $context,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        array $data = []
    ) {
        
        $this->bsecureHelper = $bsecureHelper;
        parent::__construct($context, $data);
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
