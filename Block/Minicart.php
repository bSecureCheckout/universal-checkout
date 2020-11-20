<?php

namespace Bsecure\UniversalCheckout\Block;

class Minicart extends \Magento\Framework\View\Element\Template
{
    protected $cartHelper;
    protected $bsecureHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context  $context,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        array $data = []
    ) {
       
        $this->cartHelper = $cartHelper;
        $this->bsecureHelper = $bsecureHelper;
        parent::__construct($context, $data);
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
