<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class BsecureAjax extends \Magento\Framework\App\Action\Action
{
    protected $_resultJsonFactory;
    public $bsecureHelper;
    public $accessToken;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {

        $this->_resultJsonFactory = $resultJsonFactory;
        $this->bsecureHelper = $bsecureHelper;
        $this->_orderHelper = $orderHelper;
        $this->request = $request;
        $this->cartHelper = $cartHelper;
        $this->assetRepo = $assetRepo;
        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        
        if ($this->getRequest()->isAjax()) {

            $action = filter_var($this->_request->getParam('action'), FILTER_SANITIZE_STRING);

            if ($action == 'bsecure_checkout_btn_minicart') {

                return $result->setData($this->getCartStatus());
            }

            if ($action == 'bsecure_send') {

                $returnRersult =
                [
                    
                ];

                $config = $this->bsecureHelper->getBsecureConfig();

                $response = $this->bsecureHelper->bsecureGetOauthToken();

                $validateResponse = $this->bsecureHelper->validateResponse($response, 'token_request');

                if ($validateResponse['error']) {
                    $returnRersult = ['status' => false, 'msg' => $validateResponse['msg']];
                } else {
                    // @codingStandardsIgnoreStart
                    $this->accessToken = $response->access_token;
                    // @codingStandardsIgnoreEnd

                    $response = $this->_orderHelper->bsecureCreateOrder($this->accessToken);

                    $validateResponse = $this->bsecureHelper->validateResponse($response);

                    if ($validateResponse['error']) {
                        $returnRersult = ['status' => false, 'msg' => $validateResponse['msg']];
                    } else {
                        if (!empty($response->body->order_reference)) {
                            $redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : "";
                            $returnRersult = [
                                'status' => true,
                                'msg' => __("Request Success"),
                                'redirect' => $redirect];
                        } else {
                            $completeResponse =  __("No response from bSecure server, 
                            order_reference field not found."); //phpcs:ignore
                            
                            $errorMsg = !empty($response->message) ?
                                        implode(',', $response->message) :
                                        $completeResponse;
                            $returnRersult = [
                                'status' => false,
                                 'msg' => __("Your request to bSecure server failed.")
                                 .'<br>'.esc_html($errorMsg),
                                 'redirect' => ''];
                        }
                    }
                }

                return $result->setData($returnRersult);
            }
        }
    }

    public function getCartStatus()
    {

        if ($this->cartHelper->getSummaryCount() > 0) {

            $returnRersult = [
                                'status' => true,
                                'msg' => __("Cart is not empty!"),
                                'bsecure_checkout_btn' =>  $this->getCheckoutBtn()
                            ];

        } else {

            $returnRersult = [
                                'status' => false,
                                'msg' => __("Cart is empty!"),
                                'bsecure_checkout_btn' =>  $this->getCheckoutBtn()
                            ];
        }

        return $returnRersult;
    }

    public function getCheckoutBtn()
    {

        $bsecureCheckoutBtn = "";

        $title = $this->bsecureHelper->getConfig(
            'universalcheckout/general/bsecure_title'
        );
        $moduleEnabled = $this->bsecureHelper->getConfig(
            'universalcheckout/general/enable'
        );
        $showCheckoutBtn = $this->bsecureHelper->getConfig(
            'universalcheckout/general/show_checkout_btn'
        );

        if ($showCheckoutBtn == $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH && $moduleEnabled) {
                         
            $bsecureCheckoutBtn = '
                <a href="javascript:;" class="minicart-area bsecure-checkout-button">
                  <img data-role="proceed-to-checkout"
                    title="'.$title.'"
                    alt="'.$title.'"            
                    class="primary checkout"
                    src="'.$this->assetRepo->getUrl($this->bsecureHelper::BTN_BUY_WITH_BSECURE).'"
                     />
                </a>';

        }

        return $bsecureCheckoutBtn;
    }
}
