<?php

namespace Bsecure\UniversalCheckout\Block\Adminhtml\Order\View;

class Custom extends \Magento\Backend\Block\Template
{
        
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->bsecureHelper = $bsecureHelper;
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

    /**
     * Add a custom link at order detail page to bSecure
     */
    public function getBsecureOrderLink($order)
    {
        
        $bSecureOrderViewUrl = "";
        $configPath = 'universalcheckout/general/';
        $bsecureBaseUrlKey = 'bsecure_base_url';
        $bsecureBaseUrl = $this->bsecureHelper->getConfig($configPath.$bsecureBaseUrlKey);
        if (!empty($order->getData("bsecure_order_ref"))) {

            switch ($bsecureBaseUrl) {

                case 'https://api-dev.bsecure.app/v1':
                    $bSecureOrderViewUrl = $this->bsecureHelper::BSECURE_DEV_VIEW_ORDER_URL;
                    break;

                case 'https://api-stage.bsecure.app/v1':
                    $bSecureOrderViewUrl = $this->bsecureHelper::BSECURE_STAGE_VIEW_ORDER_URL;
                    break;

                default:
                    $bSecureOrderViewUrl = $this->bsecureHelper::BSECURE_LIVE_VIEW_ORDER_URL;
                    break;
            }
            
            $bSecureOrderViewUrl = $bSecureOrderViewUrl.$order->getData("bsecure_order_ref");
        }

        return $bSecureOrderViewUrl;
    }
}
