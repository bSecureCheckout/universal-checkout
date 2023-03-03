<?php

namespace Bsecure\UniversalCheckout\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Exception\InputException;

class BeforeOrderComplete implements \Magento\Framework\Event\ObserverInterface
{
    protected $_request;
    protected $_state;

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
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\State $state
        
    ) {

        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->bsecureHelper = $bsecureHelper;
        $this->orderHelper = $orderHelper;
        $this->messageManager = $messageManager;
        $this->_request  = $request;
        $this->_state = $state;
        
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // if request form admin end then do nothing
        if($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML){

            return true;
        }

        $orderData = json_decode($this->_request->getContent());
        /*
        Make sure order is not from bsecure server this observer
        is only for bsecure payment gateway feature at checkout
        */

        if (!isset($orderData->order_type)) {
            $order = $observer->getEvent()->getOrder();

            $orderIncrementId = $order->getIncrementId();

            $quote = $this->checkoutSession->getQuote();

            $orderId = $this->checkoutSession->getLastOrderId();
          
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $payment = $quote->getPayment();

            $additionalData = $payment->getAdditionalInformation();

            $isFastCheckout = !empty($additionalData['_is_fast_checkout'])
            ? $additionalData['_is_fast_checkout'] : false;

            if (!$this->_request->getParam('order_ref')) {
                $method = $payment->getMethodInstance();

                $methodCode = $method->getCode();

                $status = $this->checkoutSession->getLastOrderStatus();

                if ($methodCode == 'bsecurepayment'
                    && !$isFastCheckout
                ) {
                    $requestData = $this->getOrderPayLoad($quote, $orderIncrementId);

                    $response = $this->sendPaymentRequestBsecure($requestData);

                    //var_dump(' $response:', $response); die;

                    if (!empty($response->order_reference)) {
                        $details =  [
                                    '_bsecure_order_ref' => $response->order_reference,
                                    '_bsecure_order_type' => 'before_payment_gateway',
                                    '_bsecure_order_id' => $orderIncrementId,
                                    '_bsecure_order_checkout_url' => $response->checkout_url
                                    
                                ];
                        
                        $newAdditionalData = !empty($additionalData) ?
                        array_merge($additionalData, $details) : $details;
                        $payment->setAdditionalInformation($newAdditionalData);
                        $payment->save();
                    }
                }
            }
        }
       
    }

    private function getOrderPayLoad($quote, $orderIncrementId)
    {

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $billingFirstName = $billingAddress->getFirstname();
        $billingLastName = $billingAddress->getLastname();
        $billingEmail = $billingAddress->getEmail();
        $billingPhone = $billingAddress->getTelephone();
        $billingCountry = $billingAddress->getCountryId();
        $billingCity = $billingAddress->getCity();
        $billingState = $billingAddress->getState();
        $billingAdress = $billingAddress->getStreet();
       
        $customerName = trim($billingFirstName . ' ' . $billingLastName);
        $authCode = "";
        $countryCode = "";

        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $customerData = $this->customerRepository->getById($customerId);
           
            if (!empty($customerData->getCustomAttribute('country_code'))) {
                 $countryCode = $customerData->getCustomAttribute('country_code')
                              ->getValue();
            }

            if (!empty($customerData->getCustomAttribute('bsecure_auth_code'))) {
                 $authCode = $customerData->getCustomAttribute('bsecure_auth_code')
                              ->getValue();
            }
           
            $userPhone = $this->customerSession->getCustomer()->getPhone();
            $billingPhone = !empty($billingPhone) ? $billingPhone : $userPhone;

            $billingPhone = !empty($countryCode) ?
            $this->bsecureHelper->phoneWithoutCountryCode($billingPhone, $countryCode) :
            $billingPhone;
        }

        $cartData = $this->orderHelper->getCartData();

        $orderData = [
            "order_id" => $orderIncrementId,
            "currency" => $quote->getQuoteCurrencyCode(),
            "sub_total_amount" => floatval($quote->getSubtotal()),
            "discount_amount" => floatval($quote->getDiscountAmount()),
            "total_amount" => floatval($quote->getGrandTotal()),
            "customer" => [

                "auth_code" => $authCode,
                "name" => $customerName,
                "email" => $billingEmail,
                "country_code" => $countryCode,
                "phone_number" =>  $billingPhone
            ],
            "customer_address" => [
                "country" => $billingCountry,
                "city" => $billingCity,
                "address" => implode(",", $billingAdress),
                "province" => $billingState,
                "area" => '',
            ],
            "customer_address_id" => 0,
            "products" => !empty($cartData['products']) ? $cartData['products'] : []
        ];
        
        return  $orderData;
    }

    private function sendPaymentRequestBsecure($requestData)
    {

        $response = $this->bsecureHelper->bsecureGetOauthToken();
    
        $validateResponse = $this->bsecureHelper->validateResponse($response, 'token_request');

        if ($validateResponse['error']) {
            throw new InputException(__($validateResponse['msg']));
            $this->messageManager->addErrorMessage($validateResponse['msg']);
            return false;
        } else {
            $headers =  ['Authorization' => 'Bearer ' . $response->access_token];

            $params =   [
                            'method' => 'POST',
                            'body' => $requestData,
                            'headers' => $headers,

                        ];

            $config =  $this->bsecureHelper->getBsecureConfig();
            $createPaymentGatewayOrder = !empty($config->createPaymentGatewayOrder) ?
                                         $config->createPaymentGatewayOrder : "";

            $response = $this->bsecureHelper->bsecureSendCurlRequest($createPaymentGatewayOrder, $params);

            $validateResponse = $this->bsecureHelper->validateResponse($response);

            if ($validateResponse['error']) {
                //$this->checkoutSession->setErrorMessage($validateResponse['msg']);
                throw new InputException(__($validateResponse['msg']));
                $this->messageManager->addErrorMessage($validateResponse['msg']);
                return false;
            } else {
                if (!empty($response->body)) {
                    return $response->body;
                }
            }
        }

        return false;
    }
}
