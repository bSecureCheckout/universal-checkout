<?php

namespace Bsecure\UniversalCheckout\Observer;

use Magento\Sales\Model\Order;

class OrderComplete implements \Magento\Framework\Event\ObserverInterface
{

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Framework\App\Response\Http $httpResponse
    ) {

        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->bsecureHelper = $bsecureHelper;
        $this->orderHelper = $orderHelper;
        $this->messageManager = $messageManager;
        $this->quoteFactory  = $quoteFactory;
        $this->orderModel  = $orderModel;
        $this->httpResponse  = $httpResponse;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $orderId = $this->checkoutSession->getLastOrderId();
        //var_dump('orderId',$orderId); die;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $this->orderModel->load($orderId);

        $quoteId = $order->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $payment = $quote->getPayment();

        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        $methodCode = $method->getCode();
       
        $status = $this->checkoutSession->getLastOrderStatus();
        $additionalData = $payment->getAdditionalInformation();

        $bsecureOrderType = !empty($additionalData['_bsecure_order_type']) ?
                            $additionalData['_bsecure_order_type'] : "";
        
        $bsecureOrderCheckoutUrl = !empty($additionalData['_bsecure_order_checkout_url']) ?
                                    $additionalData['_bsecure_order_checkout_url'] : "";
        
        if (!empty($bsecureOrderCheckoutUrl)
            && $methodCode == 'bsecurepayment'
            && $bsecureOrderType == 'before_payment_gateway'
        ) {
            $additionalData['_bsecure_order_ref'] = '';
            $additionalData['_bsecure_order_type'] = '';
            $additionalData['_bsecure_order_id'] = '';
            $additionalData['_bsecure_order_checkout_url'] = '';

            $payment->setAdditionalInformation($additionalData);
            $payment->save();
            $this->httpResponse->setRedirect($bsecureOrderCheckoutUrl);
            return;
        }

        return true;
    }
}
