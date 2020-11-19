<?php

/*
* Copyright @ 2020 bSecure. All rights reserved.
*/
namespace Bsecure\UniversalCheckout\Helper;

use Magento\Framework\HTTP\Client\Curl;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Api\OrderPaymentRepositoryInterface;
use \Magento\Payment\Model\Info as OrderPaymentInfo;
use \Bsecure\UniversalCheckout\Helper\Data;
use \Magento\CatalogInventory\Api\StockRegistryInterface;
use \Magento\Quote\Api\CartManagementInterface;
use \Magento\Sales\Api\ShipmentRepositoryInterface;
use \Magento\Shipping\Model\Config;
use \Bsecure\UniversalCheckout\Model\CustomOrderModel;

class OrderHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param Magento\Framework\App\Helper\Context $context
     * @param Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Magento\Catalog\Model\Product $product
     * @param Magento\Framework\Data\Form\FormKey $formKey $formkey,
     * @param Magento\Quote\Model\Quote $quote,
     * @param Magento\Customer\Model\CustomerFactory $customerFactory,
     * @param Magento\Sales\Model\Service\OrderService $orderService,
     */
    public $rateMethodFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderPaymentInfo $orderPayment,
        Data $bsecureHelper,
        StockRegistryInterface $stockRegistry,
        CartManagementInterface $cartManagementInterface,
        ShipmentRepositoryInterface $shipmentRepository,
        Config $shippingConfig,
        CustomOrderModel $customOrderModel,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Helper\Image $image,
        \Magento\Framework\Escaper $escaper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->storeManager         = $storeManager;
        $this->product              = $product;
        $this->quote                = $quote;
        $this->quoteManagement      = $quoteManagement;
        $this->customerFactory      = $customerFactory;
        $this->customerRepository   = $customerRepository;
        $this->orderService         = $orderService;
        $this->rateResultFactory    = $rateResultFactory;
        $this->rateMethodFactory    = $rateMethodFactory;
        $this->orderRepository      = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderPayment         = $orderPayment;
        $this->bsecureHelper        = $bsecureHelper;
        $this->stockRegistry        = $stockRegistry;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->shipmentRepository   = $shipmentRepository;
        $this->shippingConfig       = $shippingConfig;
        $this->cartItemFactory       = $cartItemFactory;
        $this->productFactory       = $productFactory;
        $this->productRepository    = $productRepository;
        $this->customOrderModel     = $customOrderModel;
        $this->cart                 = $cart;
        $this->customerSession      = $customerSession;
        $this->image                = $image;
        $this->escaper              = $escaper;
        $this->addressRepository    = $addressRepository;
        
        parent::__construct($context);
    }

    // @codingStandardsIgnoreStart
 
    /**
     * Create Order On Your Store
     *
     * @param array $orderData
     * @return array
     *
     */
    public function createMagentoOrder($orderData)
    {

        $bsecureOrderRef  = $orderData->order_ref;
        $placementStatus   = $orderData->placement_status;
        $paymentStatus     = $orderData->payment_status;
        $customerDetails   = $orderData->customer;
        $paymentMethod     = $orderData->payment_method;
        $cardDetails       = $orderData->card_details;
        $deliveryAddress   = $orderData->delivery_address;
        $shipmentMethod    = $orderData->shipment_method;
        $orderType         = $orderData->order_type;
        $merchantOrderId  = $orderData->merchant_order_id;

        $productCounts = 0;
        $fullName      = "";
        $firstName     = "";
        $lastName      = "";
        $email          = "";
        $address_1      = "";
        $address_2      = "";
        $phone          = "";
        $gender         = "";
        $city           = "";
        $dob            = "";
        $postcode       = "67000";
        $country        = "";
        $countryCode   = "";
        $state          = "";
        $lat            = "";
        $long           = "";
        $customerId    = null;

        $orderExists = $this->getMagentoOrderByBsecureRefId($bsecureOrderRef);
       
        if (!empty($orderExists)) {
            $orderState = $this->magentoOrderStatus($placementStatus);
            $orderExists->setState($orderState);
            $orderExists->setStatus($orderState);
            $orderExists->save();

            return $orderExists->getId();
        }
        
        // if store id not found then set 1 as default //
        $storeId    = $this->storeManager->getStore()->getId();
        $storeId    = $storeId > 0 ? $storeId : 1;
        $store      = $this->storeManager->getStore($storeId);
        $websiteId  = $this->storeManager->getStore($storeId)->getWebsiteId();
        $quote = $this->quote->create(); //Create object of quote
        $quote->setStore($store); //set store for which you create quote
        $quote->setCurrency();

        if (!empty($customerDetails->email) && !empty($customerDetails->name)) {
            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($customerDetails->email);// load customet by email address

            $fullName      = $customerDetails->name;
            $firstName     = $this->getFirstNameLastName($customerDetails->name);
            $lastName      = $this->getFirstNameLastName($customerDetails->name, 'last_name');

            if (!empty($customerDetails->phone_number)) {
                $phone          = $customerDetails->phone_number;
            }

            if (!empty($customerDetails->country_code)) {
                $countryCode   = $customerDetails->country_code;
            }

            if (!empty($customerDetails->gender)) {
                $gender         = $customerDetails->gender;
            }

            if (!empty($customerDetails->dob)) {
                $dob            = $customerDetails->dob;
            }

            if (!$customer->getEntityId()) {
                // Check if its not a Guest Customer
                if (!empty($customerDetails->email) && !empty($firstName)) {
                    //If not avilable then create this customer
                    $customer->setWebsiteId($websiteId)
                            ->setStore($store)
                            ->setFirstname($firstName)
                            ->setLastname($lastName)
                            ->setEmail($customerDetails->email)
                            ->setPassword($customerDetails->email);
                    $customer->save();

                    $customerData = $customer->getDataModel();
                    $customerData->setCustomAttribute('country_code', $countryCode);
                    $customer->updateData($customerData);
                    $customer->save();

                    $customerId = $customer->getEntityId();
                    // if you have allready buyer id then you can load customer directly
                    $customer = $this->customerRepository->getById($customerId);
                }
            } else {
                $customer = $this->customerRepository->getById($customer->getEntityId());
            }
                        
            $quote->assignCustomer($customer); //Assign quote to customer
        } else {
            // Set Customer Data on Qoute, Do not create customer.
            $quote->setCustomerFirstname("Guest First Name");
            $quote->setCustomerLastname("Guest Last Name");
            $quote->setCustomerEmail("guest@example.com");
            $quote->setCustomerIsGuest(true);
        }
        
        $quoteItem = $this->cartItemFactory->create();
        $productId = 0;

        //add items in quote
        foreach ($orderData->items as $key => $value) {
           //We then need to use $forceReload = true the last param for multiple products to avoid cached products
            if (!empty($value->product_id)) {
                    $product =  $this->productRepository->getById($value->product_id);
                    $productId = $product->getId();
            } elseif ($value->product_sku) {
                    $product = $this->productRepository->get($value->product_sku, false, $storeId, true);
                    $productId = $product->getId();
            } else {
                    return false;
            }

            if (!empty($productId)) {
                $productCounts++;
                $productQty = (int) $value->product_qty;
                $quote->addProduct($product, $productQty);
            }
        }

        if ($productCounts == 0) {
            return false;
        }
        
        if (!empty($deliveryAddress)) {

            if (!empty($deliveryAddress->name)) {

                $fName = $this->getFirstNameLastName($deliveryAddress->name);
                $lName = $this->getFirstNameLastName($deliveryAddress->name, 'last_name');

                $fullName = empty($fullName) ? $fName : $fullName;
                $firstName = empty($firstName) ? $fName : $firstName;
                $lastName  =  empty($firstName) ? $lName : $lastName;
            }
            
            $country        = $deliveryAddress->country;
            $city           = $deliveryAddress->city;
            $state          = $deliveryAddress->province;
            $address_2      = $deliveryAddress->area;
            $address_1      = $deliveryAddress->address;
            $lat            = $deliveryAddress->lat;
            $long           = $deliveryAddress->long;
            $postcode        = !empty($deliveryAddress->postal_code) ? $deliveryAddress->postal_code : $postcode;
        }

        // return Pakistan to PK or United Kingdom to UK//
        $country_id = array_search($country, \Zend_Locale::getTranslationList('territory'));

        $addresses = isset($customer) ? $customer->getAddresses() : [];

        $saveInAddressBook = 1;

        if (!empty($addresses)) {
            foreach ($addresses as $key => $value) {

                $fName = $this->getFirstNameLastName($fullName);
                $lName = $this->getFirstNameLastName($fullName, 'last_name');
                $phoneNum = $this->bsecureHelper->phoneWithCountryCode($phone, $countryCode);
                // Check if Address already exists
                if ($value->getFirstname() == $fName
                    && $value->getLastname() ==  $lName
                     && $value->getTelephone() == $phoneNum
                     && $value->getCity() == $city
                     && $value->getCountryId() == $country_id
                     && implode(" ", $value->getStreet()) == $address_1 .' '. $address_2) {
                    $saveInAddressBook = 0;
                    continue;
                }
            }
        }

        $shipping_address = [
                            'firstname' => $this->getFirstNameLastName($fullName), //address Details
                            'lastname'  => $this->getFirstNameLastName($fullName, 'last_name'),
                            'street'    => $address_1 .' '. $address_2,
                            'city'      => $city,
                            'country_id'=> $country_id,
                            'region'    => $state,
                            'postcode'  => $postcode,
                            'telephone' => $this->bsecureHelper->phoneWithCountryCode($phone, $countryCode),
                            'gender'    => $gender,
                            'dob'       => $dob,
                            'fax'       => '',
                            'country_code' => $countryCode,
                            'lat'       => $lat,
                            'long'      => $long,
                            'save_in_address_book' => $saveInAddressBook,
                            
                        ];

        //Set Address to quote
        $quote->getBillingAddress()->addData($shipping_address);
        $quote->getShippingAddress()->addData($shipping_address);
        
        $shippingPrice = 0;
        $shippingMethod = 'freeshipping_freeshipping';
        $shippingpTitle = '';

        // Collect Rates and Set Shipping & Payment Method
        if (!empty($shipmentMethod)) {
            if (!empty($shipmentMethod->cost)) {
                $shippingMethod = 'flatrate_flatrate';

                $shippingPrice = $shipmentMethod->cost;
            }

            if (!empty($shipmentMethod->name)) {
                $shippingpTitle = $shipmentMethod->name;
            }
        }

        $allActiveShippings = $this->shippingConfig->getActiveCarriers();

        // Check if bSecure Shipping is active
        if (!empty($allActiveShippings['bsecureshipping'])) {
            $shippingMethod = 'bsecureshipping_bsecureshipping';
        }
 
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($shippingMethod); //shipping method freeshipping_freeshipping

        if (!empty($shippingpTitle)) {
                $shippingAddress->setShippingDescription($shippingpTitle);
        }

        $quote->setPaymentMethod('bsecurepayment'); //payment method
        $quote->setInventoryProcessed(false); //not effetc inventory
        $quote->save(); //Now Save quote and your quote is ready

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'bsecurepayment']);
 
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $order = $this->quoteManagement->submit($quote);

        if (!empty($shippingPrice)) {
            $order->setShippingAmount($shippingPrice);
            $order->setBaseShippingAmount($shippingPrice);
            if (!empty($shippingpTitle)) {
                $order->setShippingDescription($shippingpTitle);
            }

            $order->setGrandTotal($order->getGrandTotal() + $shippingPrice); //adding shipping price to grand total
        }
        
        $order->save();

        $orderState = $this->magentoOrderStatus($placementStatus);
       
        $order->setState($orderState);
        $order->setStatus($orderState);
        // if order type manual then get bSecure Order ID
        if (!empty($orderType)) {
            if (strtolower($orderType) == 'manual') {
                $merchantOrderId = $this->getBsecureCustomOrderId();
            }
        }

        $details =  [
                        '_bsecure_order_ref' => $bsecureOrderRef,
                        '_bsecure_order_type' => strtolower($orderType),
                        '_bsecure_order_id' => $merchantOrderId,
                        
                    ];

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalInformation();
        $newAdditionalData = !empty($additionalData) ? array_merge($additionalData, $details) : $details;
        $payment->setAdditionalInformation($newAdditionalData);

        $order->setData('bsecure_order_ref', $bsecureOrderRef);
        $order->setData('bsecure_order_type', $orderType);
        $order->setData('bsecure_order_id', $merchantOrderId);

        if (!empty($paymentMethod->name)) {
            $orderNotes = "Payment Method: ".$paymentMethod->name;

            if (!empty($cardDetails)) {
                $orderNotes = "Card Type: ".$cardDetails->card_type.'<br>';
                $orderNotes .= "Card Holder Name: ".$cardDetails->card_name.'<br>';
                $orderNotes .= "Card Number: ".$cardDetails->card_number.'<br>';
                $orderNotes .= "Card Expire: ".$cardDetails->card_expire;
            }

            $order->addStatusHistoryComment($orderNotes)
            ->setIsCustomerNotified(false)
            ->setEntityName('order');
        }

        $order->save();

        $increment_id = $order->getRealOrderId();

        if ($order->getEntityId()) {
            return $order->getEntityId();
        } else {
            return false;
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Validate order data before saving to store
     *
     * @param array $orderData
     * @return array
     *
     */
    public function validateOrderData($orderData)
    {
        if (empty($orderData->items)) {
            return  [
                        'status' => true,
                        'msg' => __("No cart items returned from bSecure server. Please resubmit your order.")
                    ]; //phpcs:ignore
        } else {
            $productId = 0;
            foreach ($orderData->items as $key => $value) {
                // @codingStandardsIgnoreStart
                if (!empty($value->product_id)) {
                    $product =  $this->productRepository->getById($value->product_id);
                    $productId = $product->getId();

                    if (empty($productId)) {
                        $msg =  __("No product found in store against product_id") . $value->product_id;
                    }
                } elseif (!empty($value->product_sku)) {
                    $productId = $this->product->load($this->product->getIdBySku($value->product_sku));

                    if (empty($productId)) {
                        $msg =  __("No product found in store against SKU: ") . $value->product_sku;
                    }
                }

                if (empty($productId)) {
                    return  ['status' => true, 'msg' => $msg];
                }
                // @codingStandardsIgnoreEnd
            }
        }

        return ['status' => false, 'msg' => __('Order data validated successfully.')];
    }

    /*
     * Extract first_name or last_name from fullName
     */
    public function getFirstNameLastName($fullName, $nameType = 'first_name')
    {

        $fullnameArray  = explode(' ', $fullName);
        $firstName     = !empty($fullnameArray[0]) ? $fullnameArray[0] : "Customer";
        $lastName      = !empty($fullnameArray[1]) ? end($fullnameArray) : $firstName;
        $firstName     = str_replace(' '.$lastName, '', $fullName);

        return $nameType == 'last_name' ? trim($lastName) : trim($firstName);
    }

    /*
    * Map bSecure statuses with magento default statuses
    */
    public function magentoOrderStatus($placementStatus)
    {
        $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $placementStatus = (int) $placementStatus;

        switch ($placementStatus) {
            case 1:
            case 2:
            case 3:
                $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            case 4:
                $orderStatus = \Magento\Sales\Model\Order::STATE_HOLDED;
                break;
            case 5:
            case 6:
            case 7:
                $orderStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            default:
                $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                ;
                break;
        }

        return $orderStatus;
    }

    public function getMagentoOrderByBsecureRefId($bsecureOrderRef)
    {

        return $this->customOrderModel->getOrderCollection($bsecureOrderRef);
    }

    // @codingStandardsIgnoreStart
    public function getCartData()
    {
        $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
        $subTotal = $this->cart->getQuote()->getSubtotal();
        $grandTotal = $this->cart->getQuote()->getGrandTotal();

        // retrieve quote items collection
        $itemsCollection = $this->cart->getQuote()->getItemsCollection();

        // get array of all items what can be display directly
        $itemsVisible = $this->cart->getQuote()->getAllVisibleItems();

        // retrieve quote items array
        $items = $this->cart->getQuote()->getAllItems();

        $discountAmount = 0;

        $cartData['products'] = [];
        $configurables = [];
        $qty = 1;
       
        if (!empty($items)) {
            foreach ($items as $index => $item) {
                // discount amount must be included with configurable products
                $discountAmount += $item->getDiscountAmount();
                
                if ($item->getProductType() == 'configurable') {  //configurable products
                    $qty = $item->getQty();
                    continue;
                } else {
                    // non-configurable product
                    if (!$item->getParentItem()) {  // product which has not parent product
                        $qty = $item->getQty();
                    }
                }

                if ($item->getProductType() != 'configurable') {
                    $product = $this->productRepository->getById($item->getProductId());

                    $prices = $this->getPrices($product);

                    $specialPrice = $prices['specialPrice'];
                    $regularPrice = $prices['regularPrice'];

                    $specialPrice = !empty(($specialPrice)) ? floatval($specialPrice) : floatval($regularPrice);

                    $imageUrl = $this->storeManager->getStore()
                                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                                . 'catalog/product' . $product->getImage();
                    
                    $cartData['products'][] = [

                                                    'id' => $product->getId(),
                                                    'name' => $product->getName(),
                                                    'sku' => $product->getSku(),
                                                    'quantity' =>  $qty,
                                                    'price' => floatval($regularPrice),
                                                    'discount' => 0,
                                                    'sale_price' => $specialPrice,
                                                    'sub_total' => $specialPrice * $qty,
                                                    'image' => $imageUrl,
                                                    'short_description' => $this->escaper
                                                                    ->escapeHtml($product
                                                                    ->getShortDescription()),
                                                    'description' => $this->escaper
                                                                    ->escapeHtml($product
                                                                    ->getDescription())
                                                ];
                }
            }
        }

        $cartData['total_amount'] = floatval($grandTotal);
        $cartData['sub_total_amount'] = floatval($subTotal);
        $cartData['discount_amount'] = floatval($discountAmount);
        $cartData['currency_code'] = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        return $cartData;
    }
    // @codingStandardsIgnoreEnd

    public function getPrices($product)
    {

        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();

        if ($product->getTypeId() == 'configurable') {
            $basePrice = $product->getPriceInfo()->getPrice('regular_price');

            $regularPrice = $basePrice->getMinRegularAmount()->getValue();
            $specialPrice = $product->getFinalPrice();
        }

        if ($product->getTypeId() == 'bundle') {
            $regularPrice = $product->getPriceInfo()
                            ->getPrice('regular_price')
                            ->getMinimalPrice()->getValue();

            $specialPrice = $product->getPriceInfo()
                            ->getPrice('final_price')
                            ->getMinimalPrice()
                            ->getValue();
        }

        if ($product->getTypeId() == 'grouped') {
            $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
            foreach ($usedProds as $child) {
                if ($child->getId() != $product->getId()) {
                        $regularPrice += $child->getPrice();
                        $specialPrice += $child->getFinalPrice();
                }
            }
        }

        return [
                'regularPrice' => $regularPrice,
                'specialPrice' => $specialPrice
            ];
    }

    public function getCustomerData()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerData = ['country_code' => '', 'phone_number' => ''];

        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomer()->getId();

            $customer = $this->customerRepository->getById($customerId);
            $billingAddressId = $customer->getDefaultBilling();
            $shippingAddressId = $customer->getDefaultShipping();

            //get default billing address
            $billingAddress = $this->addressRepository->getById($billingAddressId);
            
            $countryCode = $customer->getCustomAttribute('country_code')->getValue();
            $bsecureAuthCode = $customer->getCustomAttribute('bsecure_auth_code')->getValue();
            $telephone =  $this->bsecureHelper->phoneWithoutCountryCode($billingAddress->getTelephone());
            $customerData = [
                            'name' => $this->customerSession->getCustomer()->getName(),
                            'email' => $this->customerSession->getCustomer()->getEmail(),
                            'country_code' => !empty($countryCode) ? $countryCode : '92',
                            'phone_number' => $telephone,
                        ];
            // if auth_code found then send it with request
            if (!empty($bsecureAuthCode)) {
                $customerData['auth_code'] = $bsecureAuthCode;
            }
        }

          return $customerData;
    }

    /**
     * Create order at server
     *
     * @return array server response .
     */
    public function bsecureCreateOrder($accessToken)
    {
        if (!$accessToken) {
            throw new \Magento\Framework\Exception\AlreadyExistsException(
                __("Access token not found while sending request at bSecure server")
            );
        }

        $cartData = $this->getCartData();

        $requestData = [
                            'customer' => $this->getCustomerData(),
                            'products' => $cartData['products'],
                            'order_id' => $this->getBsecureCustomOrderId(),
                            'currency_code' => $cartData['currency_code'],
                            'total_amount' => $cartData['total_amount'],
                            'sub_total_amount' => $cartData['sub_total_amount'],
                            'discount_amount' => $cartData['discount_amount'],
                        ];

        $config = $this->bsecureHelper->getBsecureConfig();
        $orderCreateEndpoint = !empty($config->orderCreate) ? $config->orderCreate : "";

        $headers =  ['Authorization' => 'Bearer '.$accessToken];

        $params =   [
                        'method' => 'POST',
                        'body' => $requestData,
                        'headers' => $headers,

                    ];
                
        $response = $this->bsecureHelper->bsecureSendCurlRequest($orderCreateEndpoint, $params);

        return $response;
    }

    public function getProductForApi($product)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $imageUrl = $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/product' . $product->getImage(); //phpcs:ignore

        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();

        if ($product->getTypeId() == 'configurable') {
            $basePrice = $product->getPriceInfo()->getPrice('regular_price');

            $regularPrice = $basePrice->getMinRegularAmount()->getValue();
            $specialPrice = $product->getFinalPrice();
        }

        if ($product->getTypeId() == 'bundle') {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
            $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
        }

        if ($product->getTypeId() == 'grouped') {
            $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
            foreach ($usedProds as $child) {
                if ($child->getId() != $product->getId()) {
                        $regularPrice += $child->getPrice();
                        $specialPrice += $child->getFinalPrice();
                }
            }
        }

        $productStock = $this->stockRegistry->getStockItem($product->getId());
        // Get quantity of product.
        $productQty = $productStock->getQty();
        $productIsInStock = $productStock->getIsInStock();
        $specialPrice = !empty(($specialPrice)) ? floatval($specialPrice) : floatval($regularPrice);
        $shortDescription = $product->getShortDescription();
        $productDescription = $product->getDescription();

        $productInfo = [
                            'id' => $product->getId(),
                            'name' => $product->getName(),
                            'sku' => $product->getSku(),
                            'price' => floatval($regularPrice),
                            'sale_price' => $specialPrice,
                            'image' => $imageUrl,
                            'short_description' => $this->escaper->escapeHtml($shortDescription), //phpcs:ignore
                            'description' => $this->escaper->escapeHtml($productDescription), //phpcs:ignore
                            'stock_quantity' => $productQty,
                            'is_in_stock' => $productIsInStock,
                            'product_type' => $product->getTypeId()
                        ];

        return  $productInfo;
    }

    /*
    * Generate/get bSecure custom order id
    */
    public function getBsecureCustomOrderId($useTimeStamp = true)
    {
        $configPath = 'universalcheckout/general/';
        $merchantOrderId = 'bsecure_merchant_order_id';
        $leadingZeroOrderNum = 'bsecure_leading_zero_in_order_number';

        if ($useTimeStamp) {
            // @codingStandardsIgnoreStart
            // using timestamp in magento for custom order id
            return substr(time(), 2);
            // @codingStandardsIgnoreEnd
        } else {
            $lastMerchantOrderId = 1;

            $merchantOrderId = (int) $this->bsecureHelper->getConfig($configPath.$merchantOrderId); //phpcs:ignore
            $merchantOrderId = !empty($merchantOrderId) ? $merchantOrderId+1 : $lastMerchantOrderId;//phpcs:ignore

            $leadingZero = $this->bsecureHelper->getConfig($configPath.$leadingZeroOrderNum);//phpcs:ignore

            if (empty($leadingZero)) {
                // Update with default value
                $leadingZero = 8;
                $this->bsecureHelper->setConfig($configPath.$leadingZeroOrderNum, $leadingZero); //phpcs:ignore
            }

            $this->bsecureHelper->setConfig($configPath.$merchantOrderId, $merchantOrderId);
                    
            $idWithLeadingZero = (str_pad($merchantOrderId, $leadingZero, '0', STR_PAD_LEFT));

            return $idWithLeadingZero;
        }
    }
}
