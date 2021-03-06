<?php

namespace Bsecure\UniversalCheckout\Observer\Frontend;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CustomerLogOut implements ObserverInterface
{
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper
    ) {
        
        $this->customerSession      = $customerSession;
        $this->customerFactory      = $customerFactory;
        $this->customerRepository   = $customerRepository;
        $this->bsecureHelper        = $bsecureHelper;
    }

    public function execute(EventObserver $observer)
    {
        $btnShowLogin = 'universalcheckout/general/bsecure_button_show_on_login';
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
        $bsecureBtnShowLogin = $this->bsecureHelper->getConfig($btnShowLogin);
       
        if ($bsecureBtnShowLogin == 1 && $moduleEnabled == 1 && $observer) {
            // clear customer bSeucre auth code
            $customerData = $this->customerRepository->getById($this->customerSession->getId());
            $customerData->setCustomAttribute('bsecure_auth_code', null);
        }
    }
}
