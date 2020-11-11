<?php
namespace Bsecure\UniversalCheckout\Block;

class Thankyou extends \Magento\Sales\Block\Order\Totals
{
    protected $_checkoutSession;
    protected $_customerSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = array()
    ) {
        parent::__construct($context, $registry, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
    }

    public function getOrder()
    {
        return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }
}
