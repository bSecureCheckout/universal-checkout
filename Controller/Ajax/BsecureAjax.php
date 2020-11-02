<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class BsecureAjax extends \Magento\Framework\App\Action\Action
{
	protected $_resultJsonFactory;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {

        $this->_resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }


    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $test=Array
            (
                'Firstname' => 'What is your firstname',
                'Email' => 'What is your emailId',
                'Lastname' => 'What is your lastname',
                'Country' => 'Your Country'
            );
            return $result->setData($test);
        }
    }
}