<?php
namespace Bsecure\UniversalCheckout\Block\Adminhtml\Order\View;
class Custom extends \Magento\Backend\Block\Template
{
	private $_coreRegistry;
	
	public function __construct(
       \Magento\Backend\Block\Template\Context $context,
       \Magento\Framework\Registry $registry,
       array $data = []
   ) {
       $this->_coreRegistry = $registry;
       parent::__construct($context, $data);
   }

	/**
	* Retrieve order model instance
	* 
	* @return \Magento\Sales\Model\Order
	*/
   	public function getOrder()
   	{
       	return $this->_coreRegistry->registry('current_order');
   	}




}