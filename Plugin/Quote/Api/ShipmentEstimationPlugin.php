<?php 

namespace Bsecure\UniversalCheckout\Plugin\Quote\Api;

use Magento\Quote\Api\ShipmentEstimationInterface;

class ShipmentEstimationPlugin
{
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    public function aroundEstimateByExtendedAddress(
        ShipmentEstimationInterface $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {
        $subject = !empty($subject) ? $subject : '';
        
        $shippingMethods = $proceed($cartId, $address);
        
        foreach ($shippingMethods as $key => $shippingMethod) {
            //Replace 'bsecureshipping' with your shipping method which you want to hide
            if ($shippingMethod->getMethodCode() == 'bsecureshipping') {
                unset($shippingMethods[$key]);
            }
        }

        return $shippingMethods;        
    }
}
