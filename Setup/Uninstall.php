<?php

namespace Bsecure\UniversalCheckout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Bsecure\UniversalCheckout\Helper\Data;

class Uninstall implements UninstallInterface
{
    /**
     * @param Bsecure\UniversalCheckout\Helper\Data $bsecureHelper
     *
     */
    public function __construct(
        Data $bsecureHelper
    ) {
        $this->bsecureHelper = $bsecureHelper;
    }

    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
       
        $setup->startSetup();

        $notifyData = [
                        'status' => 0,
                        'reason' => __('Module Uninstalled'),
                        'reason_message' => '',
                    ];

        $this->bsecureHelper->sendNotificationToBsecure($notifyData);

        $setup->endSetup();
    }
}
