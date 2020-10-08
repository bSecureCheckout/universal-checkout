<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class Webhook extends \Magento\Framework\App\Action\Action
{
	
	public $orderHelper;


	public function __construct(
		\Magento\Framework\App\Action\Context $context,		
		\Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
		\Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,		
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\Message\ManagerInterface $messageManager
		)
	{
		
		
		$this->bsecureHelper 	= $bsecureHelper; 
		$this->orderHelper 		= $orderHelper;		
		$this->orderRepository 	= $orderRepository; 
		$this->messageManager 	= $messageManager; 

		return parent::__construct($context);
	}

	public function execute()
	{
		
		$this->manageMagentoOrder();
		
	}

	public function manageMagentoOrder()
	{
		$orderData = json_decode(file_get_contents('php://input'));

		$returnRersult = ['status' => false, 'msg' => __("Invalid Request")]; 

		$validateOrderData =  $this->orderHelper->validateOrderData($orderData);

		if(!empty($validateOrderData['status'])){

			$returnRersult = $validateOrderData;

		}else{
			

			$order_id = $this->orderHelper->createMagentoOrder($orderData); 

			if($order_id > 0){

				$order = $this->orderRepository->get($order_id);
				$payment = $order->getPayment();
				$additionalData = $payment->getAdditionalInformation();
				
				$bsecure_order_id = !empty($additionalData['_bsecure_order_id']) ? $additionalData['_bsecure_order_id'] : '';

				$returnRersult = ['status' => true, 'msg' => __("Order added successfully at magento."), 'bsecure_order_id' => $bsecure_order_id];
			}else{

				$returnRersult = ['status' => false, 'msg' => __("Unable to create order at magento. Please contact administrator or retry")];
			}

		}
		
		$header_status = 200;
		if(!$returnRersult['status']){
			$header_status = 422;
		}

		@header('Content-Type: application/json');
		http_response_code($header_status);
 		echo json_encode($returnRersult); exit;
	}

	
}





