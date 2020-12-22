<?php

namespace Bsecure\UniversalCheckout\Block;

class Minicart extends \Magento\Framework\View\Element\Template
{
    protected $cartHelper;
    protected $bsecureHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context  $context,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $data = []
    ) {
       
        $this->cartHelper = $cartHelper;
        $this->bsecureHelper = $bsecureHelper;
        $this->assetRepo = $assetRepo;
        parent::__construct($context, $data);
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

        if ($showCheckoutBtn == $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH && $moduleEnabled) {
                         
            $bsecureCheckoutBtn = '<a href="javascript:;" class="minicart-area bsecure-checkout-button">';
            $bsecureCheckoutBtn .= '<img data-role="proceed-to-checkout" title="'.$title.'"';
            $bsecureCheckoutBtn .= 'alt="'.$title.'" class="primary checkout"';
            $bsecureCheckoutBtn .= 'src="'.
                                    $this->assetRepo->getUrl($this->bsecureHelper::BTN_BUY_WITH_BSECURE).
                                        '" /></a>';

        }

        return $bsecureCheckoutBtn;
    }
}
