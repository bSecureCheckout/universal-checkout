<?php

namespace Bsecure\UniversalCheckout\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Session\SessionManager;
use \Magento\Framework\View\Result\PageFactory;
use \Bsecure\UniversalCheckout\Helper\Data as BsecureHelper;




class CheckoutBtn extends \Magento\Framework\App\Action\Action
{
    protected $coreSession;

    public function __construct(
        Context $context,
        SessionManager $sessionManager,
        BsecureHelper $bsecureHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
	    \Magento\Framework\App\RequestInterface $request,
	    \Magento\Store\Model\App\Emulation $appEmulation
        ) {
        $this->coreSession = $sessionManager;
        $this->bsecureHelper = $bsecureHelper;
        $this->assetRepo = $assetRepo;
	    $this->request = $request;
	    $this->appEmulation = $appEmulation;
        parent::__construct($context);
    }

    public function execute()
    {
    	//$this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

	    $params = array('_secure' => $this->request->isSecure());
	    

	    //$this->appEmulation->stopEnvironmentEmulation();

    	$module_enabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
    	$show_checkout_btn = $this->bsecureHelper->getConfig('universalcheckout/general/show_checkout_btn'); 

    	if($module_enabled && $show_checkout_btn == $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH){
    		$this->coreSession->setMessage(1);

    	}else{
    		$this->coreSession->unsMessage();
    	}

        $sessionData = ($this->coreSession->getMessage()) ? true : false;
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData([
            'success' => true,
            'sessionData' => $sessionData,
            'show_checkout_btn' => $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH,
            'module_enabled' => $module_enabled,
            'btn_img' => $this->assetRepo->getUrlWithParams('Bsecure_UniversalCheckout::images/buy-with-bsecure-black.svg', $params)
        ]);
    }
}