<?php
namespace Bsecure\UniversalCheckout\Block\Adminhtml\Order;

class Fee extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Order
     */
    protected $_order;
    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }
    /**
     * Initialize all order totals relates with tax
     *
     * @return \Magento\Tax\Block\Sales\Order\Tax
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
       
        if (!empty($this->_order->getBsecureDiscount())) {
            if ($this->_order->getSubtotal() == $this->_order->getBsecureDiscount()) {

                $fee = new \Magento\Framework\DataObject(
                    [
                        'code' => 'bsecure_discount',
                        'strong' => true,
                        'value' => $this->_order->getBsecureDiscount(),
                        'label' => __('Discount (bSecure)'),
                    ]
                );

                $parent->addTotal($fee, 'bsecure_discount');
            }
        }
        
        if (!empty($this->_order->getBsecureServiceCharges())) {

            $fee = new \Magento\Framework\DataObject(
                [
                    'code' => 'bsecure_service_charges',
                    'strong' => true,
                    'value' => $this->_order->getBsecureServiceCharges(),
                    'label' => __('bSecure Service Charges'),
                ]
            );

            $parent->addTotal($fee, 'bsecure_service_charges');
        }
        
        return $this;
    }
}
