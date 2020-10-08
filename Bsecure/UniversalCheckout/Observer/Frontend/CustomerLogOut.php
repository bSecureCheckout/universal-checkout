<?php

namespace Bsecure\UniversalCheckout\Observer\Frontend;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CustomerLogOut implements ObserverInterface
{
	public function __construct(
		
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
		
		)
	{	
		
		$this->customerSession 	= $customerSession; 
		$this->customerFactory 	= $customerFactory; 		
		$this->customerRepository 	= $customerRepository; 			
	}

    public function execute(EventObserver $observer)
    {
    	// clear customer bSeucre auth code
        $customerData = $this->customerRepository->getById($this->customerSession->getId());
        $customerData->setCustomAttribute('bsecure_auth_code',NULL);        
       
    }
}