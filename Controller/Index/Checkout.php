<?php

namespace Bsecure\UniversalCheckout\Controller\Index;

class Checkout extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_request;
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
    ) {
        $this->_pageFactory     = $pageFactory;
        $this->_request         = $request;
        $this->bsecureHelper    = $data;
        $this->orderHelper      = $orderHelper;
        $this->resultFactory    = $resultFactory;
        $this->session          = $session;
        $this->quote            = $quote;
        $this->cart             = $cart;
        $this->orderRepository  = $orderRepository;
        $this->messageManager   = $messageManager;
        $this->urlInterface     = $urlInterface;

        return parent::__construct($context);
    }

    public function execute()
    {
        
        $bsecureOrderRef = filter_var($this->_request->getParam('order_ref'), FILTER_SANITIZE_STRING);
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

        if (!empty($bsecureOrderRef) && $moduleEnabled == 1) {
            $this->manageMagentoOrder($bsecureOrderRef);
        }
    }

    /*
    *  Manage order at magento
    */
    public function manageMagentoOrder($bsecureOrderRef)
    {
        
        $response = $this->bsecureHelper->bsecureGetOauthToken();
        
        $validateResponse = $this->bsecureHelper->validateResponse($response, 'token_request');

        if ($validateResponse['error']) {
            return $this->getResponse()->setBody(__('Response Error: ' . $validateResponse['msg']));
        } else {
            // Get Order //
            // @codingStandardsIgnoreStart
            $this->accessToken = $response->access_token;
            // @codingStandardsIgnoreEnd

            $headers =    ['Authorization' => 'Bearer ' . $this->accessToken];

            $requestData['order_ref'] = $bsecureOrderRef;

            $params =     [
                            'method' => 'POST',
                            'body' => $requestData,
                            'headers' => $headers,
                        ];

            $config = $this->bsecureHelper->getBsecureConfig();

            $this->orderStatusEndpoint = !empty($config->orderStatus) ? $config->orderStatus : "";

            $response = $this->bsecureHelper->bsecureSendCurlRequest($this->orderStatusEndpoint, $params);

            $validateResponse = $this->bsecureHelper->validateResponse($response);

            if ($validateResponse['error']) {
                return $this->getResponse()->setBody(__('Response Error: ' . $validateResponse['msg']));
            } else {
                $orderData = $response->body;
                
                $validateOrderData = $this->orderHelper->validateOrderData($orderData);

                if (!empty($validateOrderData['status'])) {
                    return $this->getResponse()->setBody(__('Error: ' . $validateOrderData['msg']));
                } else {
                    if (!empty($orderData->placement_status)) {
                        if ($orderData->placement_status == 2 || $orderData->placement_status == 1) {
                            $this->messageManager->addError(__("Sorry! Your order has not been proccessed."));
                            $this->_redirect('checkout/cart');
                        }
                    }

                    $orderId = $this->orderHelper->createMagentoOrder($orderData);

                    if (!empty($orderId)) {
                        $order = $this->orderRepository->get($orderId);
                        $quoteId = $order->getQuoteId();
                        $getRealOrderId = $order->getRealOrderId();
                        
                        $this->session->setLastSuccessQuoteId($quoteId);
                        $this->session->setLastQuoteId($quoteId);
                        $this->session->setLastOrderId($orderId); //123
                        $this->session->setLastRealOrderId($getRealOrderId); // 000000123

                        $this->_clearQuote();

                        if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                            $this->messageManager->addError(__("Sorry! Your order has been " . $order->getStatus()));
                            $this->_redirect('checkout/cart');
                        }

                        $this->_redirect('checkout/onepage/success');
                    } else {
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
        $this->cart->truncate();
        $this->cart->getQuote()->setTotalsCollectedFlag(false);
         $this->cart->getQuote()->setIsActive(0);
        $this->cart->save();
    }
}
