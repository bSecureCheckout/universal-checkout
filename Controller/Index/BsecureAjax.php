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
        \Magento\Framework\App\Request\Http $request
    ) {

        $this->_resultJsonFactory = $resultJsonFactory;
        $this->bsecureHelper = $bsecureHelper;
        $this->_orderHelper = $orderHelper;
        $this->request = $request;
        
        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->_resultJsonFactory->create();

        $returnRersult = [
            'status' => false,
            'msg' => __("Request Unsuccessful"),
            'redirect' => ''
        ];
        
        if ($this->getRequest()->isAjax()) {

            $action = filter_var($this->request->getParam('action'), FILTER_SANITIZE_STRING);

            if ($action == 'bsecure_send') {

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
            }
        }

        return $result->setData($returnRersult);
    }
}
