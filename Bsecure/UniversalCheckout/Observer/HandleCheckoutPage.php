<?php

namespace Bsecure\UniversalCheckout\Observer;


class HandleCheckoutPage implements \Magento\Framework\Event\ObserverInterface
{
	public function __construct(
       \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
       \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
       \Magento\Checkout\Helper\Cart $cartHelper,
       \Magento\Framework\App\ResponseFactory $responseFactory,
       \Magento\Framework\UrlInterface $url
    ){
    	$this->bsecureHelper 	= $bsecureHelper;
    	$this->cartHelper 		= $cartHelper;
    	$this->orderHelper 		= $orderHelper;
    	$this->responseFactory 	= $responseFactory;
        $this->url 				= $url;

    }


	public function execute(\Magento\Framework\Event\Observer $observer)
	{		
		
		$module_enabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
		$show_checkout_btn = $this->bsecureHelper->getConfig('universalcheckout/general/show_checkout_btn');

		if($module_enabled && $this->cartHelper->getItemsCount() > 0 && $show_checkout_btn != $this->bsecureHelper::BTN_SHOW_BSECURE_BOTH){

			$this->handle_checkout_page();

		}
		

		return $this;
	}



	/*
	* Check if bSecure checkout is active then create order at bSecure and redirect to bSecure
	*/
	public function handle_checkout_page(){				

		$response = $this->bsecureHelper->bsecureGetOauthToken();	

		$validateResponse = $this->bsecureHelper->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			die('Response Error: '.$validateResponse['msg']);
			

		}else{


			// Create Order //
			$this->access_token = $response->access_token;

			$response = $this->orderHelper->bsecureCreateOrder($this->access_token);

			$validateResponse = $this->bsecureHelper->validateResponse($response);	

			if( $validateResponse['error'] ){			
				
				die('Response Error: '.$validateResponse['msg']);
				

			}else{


				if(!empty($response->body->order_reference)){				

					$redirect = !empty($response->body->checkout_url) ? $response->body->checkout_url : $this->url->getUrl('checkout/cart/index');	

					// Redirect to bSecure Server
        			$this->responseFactory->create()->setRedirect($redirect)->sendResponse();
					exit;					

				}else {	

					$complete_response =  __("No response from bSecure server, order_reference field not found.");

					$errorMsg = !empty($response->message) ? implode(',', $response->message) : $complete_response;
					
					echo __("Your request to bSecure server failed.").'<br>'.esc_html($errorMsg); 
					exit;
				}


			}					


		}
		
	}
}