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

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        BsecureHelper $bsecureHelper
    ) {
        $this->_logger = $logger;
        $this->bsecureHelper = $bsecureHelper;
    }

    public function execute(EventObserver $observer)
    {
        $installed = $this->bsecureHelper->getConfig('universalcheckout/general/bsecure_installed');

        if ($installed == 1) {
            $this->bsecureHelper->installNotification();
        }
    }
}
