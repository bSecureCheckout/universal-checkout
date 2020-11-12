<?php 

namespace Bsecure\UniversalCheckout\Observer;

class HandleMiniCart implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Magento\Checkout\Helper\Cart $cartHelper
    ){
        $this->bsecureHelper  = $bsecureHelper;
        $this->cartHelper     = $cartHelper;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');        

        if ($moduleEnabled == 1 && $this->cartHelper->getItemsCount() === 0 && (!$observer)) {            
            return false;
        }

        return $this;
    }


}
