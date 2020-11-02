<?php
namespace Bsecure\UniversalCheckout\Controller\Index;


class BsecureAjax extends \Magento\Framework\App\Action\Action
{
	protected $_resultJsonFactory;
    public $bsecureHelper;    
    public $access_token;    

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,      
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper
    ) {

        $this->_resultJsonFactory = $resultJsonFactory;        
        $this->bsecureHelper = $bsecureHelper;       
        $this->orderHelper = $orderHelper;       
        return parent::__construct($context);
    }


    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            
            $returnRersult = Array
            (
                
            );

            $config = $this->bsecureHelper->getBsecureConfig();

            $response = $this->bsecureHelper->bsecureGetOauthToken();

            $validateResponse = $this->bsecureHelper->validateResponse($response,'token_request');     

            if( $validateResponse['error'] ){

                $returnRersult = ['status' => false, 'msg' => $validateResponse['msg']];

            }else{

                $this->access_token = $response->access_token;

                $response = $this->orderHelper->bsecureCreateOrder($this->access_token);

                $validateResponse = $this->bsecureHelper->validateResponse($response); 

                if( $validateResponse['error'] ){
                    
                    $returnRersult = ['status' => false, 'msg' => $validateResponse['msg']];       

                }else{


                    if(!empty($response->body->order_reference)){               

                        $redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : "";

                        $returnRersult = ['status' => true, 'msg' => __("Request Success", 'wc-bsecure'), 'redirect' => $redirect];

                    }else{

                        $complete_response =  __("No response from bSecure server, order_reference field not found.",'wc-bsecure');
                        
                        $errorMsg = !empty($response->message) ? implode(',', $response->message) : $complete_response;
                        $returnRersult = ['status' => false, 'msg' => __("Your request to bSecure server failed.", 'wc-bsecure') .'<br>'.esc_html($errorMsg), 'redirect' => ''];
                    }


                }
            } 
            

            return $result->setData($returnRersult);
        }
    }


    



}