<?php
namespace Bsecure\UniversalCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Bsecure\UniversalCheckout\Helper\Data as BsecureHelper;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $_logger;
    protected $_bsecureHelper;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        BsecureHelper $bsecureHelper
    ) {
        $this->_logger = $logger;
        $this->_bsecureHelper = $bsecureHelper;
    }

    public function execute(EventObserver $observer)
    {
        
        $installed = $this->_bsecureHelper->getConfig('universalcheckout/general/bsecure_installed');

        if ($installed == 1) {

            $this->_bsecureHelper->setConfig('universalcheckout/general/bsecure_installed', 0);

            $storeId = $this->_bsecureHelper->getConfig('universalcheckout/general/bsecure_store_id');

            $notifyData = [
                        'status' => 1,
                        'reason' => __('Module Installed'),
                        'reason_message' => __('Module Installed'),
                    ];

            $this->_bsecureHelper->sendNotificationToBsecure($notifyData);
             $this->_logger->debug("-----------ConfigObserver-------------bsecureStoreId:".$storeId);
        }
    }
}
