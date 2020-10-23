<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class Checkout extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $request;
	public $bsecureHelper;
	public $orderHelper;


	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Magento\Framework\App\Request\Http $request,
		\Bsecure\UniversalCheckout\Helper\Data $data,
		\Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
		\Magento\Framework\Controller\ResultFactory $resultFactory,
		\Magento\Checkout\Model\Session $session,
		\Magento\Quote\Model\QuoteFactory $quote,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Framework\UrlInterface $urlInterface
		)
	{
		$this->_pageFactory 	= $pageFactory;
		$this->request 			= $request;
		$this->bsecureHelper 	= $data; 
		$this->orderHelper 		= $orderHelper; 
		$this->resultFactory 	= $resultFactory; 
		$this->session 			= $session; 
		$this->quote 			= $quote; 
		$this->cart 			= $cart; 
		$this->orderRepository 	= $orderRepository; 
		$this->messageManager 	= $messageManager; 
		$this->urlInterface 	= $urlInterface; 

		return parent::__construct($context);
	}

	public function execute()
	{
		
		$bsecure_order_ref = filter_var($this->request->getParam('order_ref'), FILTER_SANITIZE_STRING);

		if(!empty($bsecure_order_ref)){

			$this->manageMagentoOrder($bsecure_order_ref);

		}
		
	}



	/*
	*  Manage order at magento
	*/
	public function manageMagentoOrder($bsecure_order_ref){		
		
		
		$order_data = [];
		
		$response = $this->bsecureHelper->bsecureGetOauthToken();	
		
		$validateResponse = $this->bsecureHelper->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){				

			return $this->getResponse()->setBody(__('Response Error: '.$validateResponse['msg']));			

		}else{

			// Get Order //
			$this->access_token = $response->access_token;

			$headers =	['Authorization' => 'Bearer '.$this->access_token];

			$request_data['order_ref'] = $bsecure_order_ref;						   			

			$params = 	[
							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];	

			$config = $this->bsecureHelper->getBsecureConfig();  

	    	$this->order_status_endpoint = !empty($config->orderStatus) ? $config->orderStatus : "";		 

			$response = $this->bsecureHelper->bsecureSendCurlRequest( $this->order_status_endpoint,$params);

			$validateResponse = $this->bsecureHelper->validateResponse($response);	

			if($validateResponse['error']){				
				
				return $this->getResponse()->setBody(__('Response Error: '.$validateResponse['msg']));

			}else{

				$orderData = $response->body;
				
				$validateOrderData = $this->orderHelper->validateOrderData($orderData);

				if(!empty($validateOrderData['status'])){
					
					return $this->getResponse()->setBody(__('Error: '.$validateOrderData['msg']));

				}else{

					$orderId = $this->orderHelper->createMagentoOrder($orderData);									

					if( !empty($orderId) ){

						$order = $this->orderRepository->get($orderId);
						$quoteId = $order->getQuoteId(); 					
						$getRealOrderId = $order->getRealOrderId(); 				

						
	                    $this->session->setLastSuccessQuoteId($quoteId);
	                    $this->session->setLastQuoteId($quoteId);
	                    $this->session->setLastOrderId($orderId); //123
	                    $this->session->setLastRealOrderId($getRealOrderId); // 000000123

	                    $this->_clearQuote();

						$this->_redirect('checkout/onepage/success');

						if($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED){

							$this->messageManager->addError(__("Sorry! Your order has been ".$order->getStatus()));
							$this->_redirect('checkout/cart');

						}
				    	
					}else{

						$this->messageManager->addError(__("Unable to create order at this moment please try again."));
						$this->_redirect('checkout/cart');
						
					}
					

				}
				
				
			}


		}
		

	}

	// Clear Cart //
	protected function _clearQuote()
    {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
    	$cart = $objectManager->create("Magento\Checkout\Model\Cart");
	    $cart->truncate();
	    $cart->getQuote()->setTotalsCollectedFlag(false);
	    $cart->save();      

    }
}





