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
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper
    ) {

        $this->_resultJsonFactory = $resultJsonFactory;        
        $this->bsecureHelper = $bsecureHelper;       
        $this->_orderHelper = $orderHelper;       
        return parent::__construct($context);
    }


    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            $returnRersult = Array
            (
                
            );

            $config = $this->bsecureHelper->getBsecureConfig();

            $response = $this->bsecureHelper->bsecureGetOauthToken();

            $validateResponse = $this->bsecureHelper->validateResponse($response, 'token_request');     

            if ($validateResponse['error'] ) {
                $returnRersult = array('status' => false, 'msg' => $validateResponse['msg']);
            } else {
                // @codingStandardsIgnoreStart
                $this->accessToken = $response->access_token;
                // @codingStandardsIgnoreEnd

                $response = $this->_orderHelper->bsecureCreateOrder($this->accessToken);

                $validateResponse = $this->bsecureHelper->validateResponse($response); 

                if ($validateResponse['error'] ) {
                    $returnRersult = array('status' => false, 'msg' => $validateResponse['msg']);       
                } else {
                    if (!empty($response->body->order_reference)) {               
                        $redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : "";

                        $returnRersult = array('status' => true, 'msg' => __("Request Success", 'wc-bsecure'), 'redirect' => $redirect); //phpcs:ignore
                    } else {
                        $completeResponse =  __("No response from bSecure server, order_reference field not found.", 'wc-bsecure'); //phpcs:ignore
                        
                        $errorMsg = !empty($response->message) ? implode(',', $response->message) : $completeResponse;
                        $returnRersult = array('status' => false, 'msg' => __("Your request to bSecure server failed.", 'wc-bsecure') .'<br>'.esc_html($errorMsg), 'redirect' => ''); //phpcs:ignore
                    }
                }
            } 
            

            return $result->setData($returnRersult);
        }
    }


    



}
