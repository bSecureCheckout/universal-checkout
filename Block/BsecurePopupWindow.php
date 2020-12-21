<?php

namespace Bsecure\UniversalCheckout\Block;

use Magento\Framework\View\Element\Template;

class BsecurePopupWindow extends Template
{
    protected $cartHelper;
    protected $bsecureHelper;
    
   /**
    * Sidebar constructor.
    * @param Template\Context $context
    * @param array $data
    */
    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        array $data = []
    ) {

        $this->cartHelper = $cartHelper;
        $this->bsecureHelper = $bsecureHelper;
        parent::__construct($context, $data);
    }

    public function isCartEmpty()
    {

        $quote = $this->cartHelper->getQuote();
        $totalItems = count($quote->getAllItems());
 
        return ($totalItems == 0) ? true : false;
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
