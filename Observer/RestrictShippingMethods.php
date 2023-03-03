<?php

namespace Bsecure\UniversalCheckout\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Backend\Model\Session\Quote as adminQuoteSession;

class RestrictShippingMethods implements ObserverInterface
{

    protected $_state;
    protected $_session;
    protected $_quote;
    const BSECURE_SHIPPING_METHOD = \Bsecure\UniversalCheckout\Model\Carrier\Shipping::SHIPPING_METHOD_CODE;

    public function __construct(
        \Magento\Framework\App\State $state,
        Session $checkoutSession,
        adminQuoteSession $adminQuoteSession
    ) {
        $this->_state = $state;
        if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->_session = $adminQuoteSession;
        } else {
            $this->_session = $checkoutSession;
        }

        $this->_quote = $this->_session->getQuote();
    }

    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */

    public function execute(EventObserver $observer)
    {
    
        //Code of Current Payment Method--
        $code = $observer->getEvent()->getMethodInstance()->getCode();
        
        /*
        * Now, you can check if current method code is as same as the code of payment method which
        * you want to disable then apply the following condition
        */

        if ($code == self::BSECURE_SHIPPING_METHOD &&
            $this->_state->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML
        ) {
            $checkResult = $observer->getEvent()->getResult();
            
            //$checkResult->setData('is_available', false);
        }
    }
}
