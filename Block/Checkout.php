<?php

namespace Bsecure\UniversalCheckout\Block;

class Checkout extends \Magento\Framework\View\Element\Template
{

    protected $bsecureHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context  $context,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Magento\Checkout\Block\Onepage\Link $onePageLink,
        array $data = []
    ) {
        
        $this->bsecureHelper = $bsecureHelper;
        $this->onePageLink = $onePageLink;
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

    public function getOnePageLink()
    {
        return $this->onePageLink;
    }

    public function getOnePageLinkPath()
    {
        return '\Magento\Checkout\Block\Onepage\Link';
    }
}
