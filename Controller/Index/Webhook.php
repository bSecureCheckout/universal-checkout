<?php

namespace Bsecure\UniversalCheckout\Controller\Index;

class Webhook extends \Magento\Framework\App\Action\Action
{
    
    public $orderHelper;
    protected $_resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\RequestInterface $request
    ) {
        
        $this->bsecureHelper        = $bsecureHelper;
        $this->orderHelper          = $orderHelper;
        $this->orderRepository      = $orderRepository;
        $this->messageManager       = $messageManager;
        $this->_resultJsonFactory   = $resultJsonFactory;
        $this->request              = $request;

        return parent::__construct($context);
    }

    public function execute()
    {
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
        if ($moduleEnabled == 1) {
            $this->manageMagentoOrder();
        }
    }

    public function manageMagentoOrder()
    {
        $orderData = json_decode($this->request->getPost());

        $returnRersult = ['status' => false, 'msg' => __("Invalid Request")];

        $validateOrderData =  $this->orderHelper->validateOrderData($orderData);

        if (!empty($validateOrderData['status'])) {
            $returnRersult = $validateOrderData;
        } else {
            $orderId = $this->orderHelper->createMagentoOrder($orderData);

            if ($orderId > 0) {
                $order = $this->orderRepository->get($orderId);
                $payment = $order->getPayment();

                $bsecureOrderId = $order->getData('bsecure_order_id');

                $returnRersult = [
                                    'status' => true,
                                    'msg' => __("Order added successfully at magento."),
                                    'bsecure_order_id' => $bsecureOrderId
                                ];

                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $returnRersult = [
                                        'status' => true,
                                        'msg' => __("Sorry! Your order has been ".$order->getStatus()),
                                        'bsecure_order_id' => $bsecureOrderId];//phpcs:ignore
                }
            } else {
                $msg = __("Unable to create order at magento. Please contact administrator or retry");
                $returnRersult = [
                                    'status' => false,
                                    'msg' => __($msg)
                                    ]; //phpcs:ignore
            }
        }
        
        $headerStatus = 200;
        if (!$returnRersult['status']) {
            $headerStatus = 422;
        }

        $resultJson = $this->_resultJsonFactory->create();

        http_response_code($headerStatus);
        $resultJson->setData($returnRersult);
        return $resultJson;
    }
}
