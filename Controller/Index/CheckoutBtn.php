<?php

namespace Bsecure\UniversalCheckout\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Session\SessionManager;
use \Magento\Framework\View\Result\PageFactory;
use \Bsecure\UniversalCheckout\Helper\Data as BsecureHelper;

class CheckoutBtn extends \Magento\Framework\App\Action\Action
{
    protected $_coreSession;

    public function __construct(
        Context $context,
        SessionManager $sessionManager,
        BsecureHelper $bsecureHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\App\Emulation $appEmulation
    ) {
        $this->_coreSession = $sessionManager;
        $this->bsecureHelper = $bsecureHelper;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->appEmulation = $appEmulation;
        parent::__construct($context);
    }

    public function execute()
    {
        
        $params = ['_secure' => $this->request->isSecure()];
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
        $showCheckoutBtn = $this->bsecureHelper->getConfig('universalcheckout/general/show_checkout_btn');

        if ($moduleEnabled == 1 && $showCheckoutBtn == $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH) {
            $this->_coreSession->setMessage(1);
        } else {
            $this->_coreSession->unsMessage();
        }

        $sessionData = ($this->_coreSession->getMessage()) ? true : false;
        $btnBuyWithBsecure = $this->bsecureHelper::BTN_BUY_WITH_BSECURE;
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(
            [
            'success' => true,
            'sessionData' => $sessionData,
            'show_checkout_btn' => $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH,
            'module_enabled' => $moduleEnabled,
            'btn_img' => $this->assetRepo->getUrlWithParams($btnBuyWithBsecure, $params) //phpcs:ignore
            ]
        );
    }
}
