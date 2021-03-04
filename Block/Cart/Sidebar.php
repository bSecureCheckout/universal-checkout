<?php

namespace Bsecure\UniversalCheckout\Block\Cart;

use Magento\Framework\View\Element\Template;

class Sidebar extends Template
{
    protected $cartHelper;
    protected $bsecureHelper;
    
   /**
    * Sidebar constructor.
    * @param Template\Context $context
    * @param array $data
    */
    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Cart $carModel,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $data = []
    ) {

        $this->cartHelper = $cartHelper;
        $this->bsecureHelper = $bsecureHelper;
        $this->carModel = $carModel;
        $this->assetRepo = $assetRepo;
        parent::__construct($context, $data);
    }

    public function isCartEmpty()
    {

        $quote = $this->cartHelper->getQuote();
        $totalItems = count($quote->getAllItems());
 
        return ($totalItems == 0) ? true : false;
    }

    public function getCartHelper()
    {
        return $this->cartHelper;
    }

    public function getBsecureHelper()
    {
        return $this->bsecureHelper;
    }

    public function getBsecureSettings($key)
    {
        return $this->bsecureHelper->getConfig('universalcheckout/general/'.$key);
    }

    public function getCartCount()
    {
           
        return $this->cartHelper->getSummaryCount();
    }

    public function getCheckoutBtn()
    {

        $bsecureCheckoutBtn = "";

        $title = $this->getBsecureSettings(
            'bsecure_title'
        );
        $moduleEnabled = $this->getBsecureSettings(
            'enable'
        );
        $showCheckoutBtn = $this->getBsecureSettings(
            'show_checkout_btn'
        );
        $checkoutBtnUrl = $this->getBsecureSettings(
            'bsecure_checkout_btn_url'
        );

        $checkoutBtnUrl = !empty($checkoutBtnUrl) ? $checkoutBtnUrl.'?v='.random_int(0, 100000000) :
                          $this->assetRepo->getUrl($this->bsecureHelper::BTN_BUY_WITH_BSECURE);

        if ($showCheckoutBtn == $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH && $moduleEnabled) {
                         
            $bsecureCheckoutBtn = '<a href="javascript:;" class="minicart-area bsecure-checkout-button">';
            $bsecureCheckoutBtn .= '<img data-role="proceed-to-checkout" title="'.$title.'"';
            $bsecureCheckoutBtn .= 'alt="'.$title.'" class="primary checkout"';
            $bsecureCheckoutBtn .= 'src="'.$checkoutBtnUrl.'" /></a>';

        }

        return $bsecureCheckoutBtn;
    }
}
