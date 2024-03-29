<?php

namespace Bsecure\UniversalCheckout\Observer;

class HandleCheckoutPage implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->bsecureHelper     = $bsecureHelper;
        $this->cartHelper        = $cartHelper;
        $this->orderHelper       = $orderHelper;
        $this->responseFactory   = $responseFactory;
        $this->url               = $url;
        $this->messageManager    = $messageManager;
        $this->redirect          = $redirect;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');
        $showCheckoutBtn = $this->bsecureHelper->getConfig('universalcheckout/general/show_checkout_btn');

        if ($moduleEnabled
            && $this->cartHelper->getItemsCount() > 0
            && $showCheckoutBtn == $this->bsecureHelper::BTN_SHOW_BSECURE_ONLY) { //phpcs:ignore
            $this->handleCheckoutPage($observer);
        }
        
        return $this;
    }

    /*
    * Check if bSecure checkout is active then create order at bSecure and redirect to bSecure
    */
    public function handleCheckoutPage($observer)
    {

        $controller = $observer->getControllerAction();        
        
        $response = $this->orderHelper->bsecureCreateOrder();

        $validateResponse = $this->bsecureHelper->validateResponse($response);

        if ($validateResponse['error']) {
            $this->messageManager->addError(__('Response Error: ' . $validateResponse['msg']));
            $this->redirect->redirect($controller->getResponse(), 'checkout/cart');
        } else {
            if (!empty($response->body->order_reference)) {
                $redirect = !empty($response->body->checkout_url) ?
                            $response->body->checkout_url :
                            $this->url->getUrl('checkout/cart');//phpcs:ignore

                // Redirect to bSecure Server
                $this->redirect->redirect($controller->getResponse(), $redirect);
            } else {
                $completeResponse =  __("No response from bSecure server, order_reference field not found.");
                $errorMsg = !empty($response->message) ?
                implode(',', $response->message) :
                $completeResponse;//phpcs:ignore
                $msg = __("Your request to bSecure server failed.");
                $this->messageManager->addError($msg.' '.($errorMsg));//phpcs:ignore
                $this->redirect->redirect($controller->getResponse(), 'checkout/cart');
            }
        }
        
    }
}
