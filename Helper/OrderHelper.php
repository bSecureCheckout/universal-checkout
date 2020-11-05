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
use Magento\Sales\Api\Data\OrderExtensionFactory;




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
        \Magento\Framework\Data\Form\FormKey $formkey,
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
        OrderExtensionFactory $orderExtensionFactory



    ) {
        $this->storeManager        = $storeManager;
        $this->_product             = $product;
        $this->_formKey             = $formkey;
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
        
        parent::__construct($context);
    }
 
    /**
     * Create Order On Your Store
     * 
     * @param array $orderData
     * @return array
     * 
    */
    public function createMagentoOrder($orderData) {       

        $bsecure_order_ref  = $orderData->order_ref;
        $placement_status   = $orderData->placement_status;
        $payment_status     = $orderData->payment_status;
        $customer_details   = $orderData->customer;
        $payment_method     = $orderData->payment_method;
        $card_details       = $orderData->card_details;
        $delivery_address   = $orderData->delivery_address;
        $shipment_method    = $orderData->shipment_method;
        $order_type         = $orderData->order_type;
        $merchant_order_id  = $orderData->merchant_order_id;

        $product_counts = 0;
        $full_name      = "";
        $first_name     = "";
        $last_name      = "";
        $email          = "";
        $address_1      = "";
        $address_2      = "";
        $phone          = "";
        $gender         = "";
        $city           = "";
        $dob            = "";
        $postcode       = "67000";
        $country        = "";
        $country_code   = "";
        $state          = "";
        $lat            = "";
        $long           = "";
        $customer_id    = null;

        $orderExists = $this->getMagentoOrderByBsecureRefId($bsecure_order_ref);
       
        if(!empty($orderExists)){

            $orderState = $this->magentoOrderStatus($placement_status);       
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

        if(!empty($customer_details->email) && !empty($customer_details->name)){

            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($customer_details->email);// load customet by email address           

            $full_name      = $customer_details->name;
            $first_name     = $this->get_first_name_or_last_name($customer_details->name);
            $last_name      = $this->get_first_name_or_last_name($customer_details->name,'last_name');
            

            if(!empty($customer_details->phone_number)){
                $phone          = $customer_details->phone_number;
            }
            if(!empty($customer_details->country_code)){
                $country_code   = $customer_details->country_code;
            }
            if(!empty($customer_details->gender)){
                $gender         = $customer_details->gender;
            }
            if(!empty($customer_details->dob)){
                $dob            = $customer_details->dob;
            }


            if(!$customer->getEntityId()){

                // Check if its not a Guest Customer
                if(!empty($customer_details->email) && !empty($first_name)){

                    //If not avilable then create this customer 
                    $customer->setWebsiteId($websiteId)
                            ->setStore($store)
                            ->setFirstname($first_name)
                            ->setLastname($last_name)
                            ->setEmail($customer_details->email) 
                            ->setPassword($customer_details->email);
                    $customer->save();

                    $customerData = $customer->getDataModel();
                    $customerData->setCustomAttribute('country_code',$country_code);
                    $customer->updateData($customerData);
                    $customer->save();

                    $customer_id = $customer->getEntityId();

                    // if you have allready buyer id then you can load customer directly 
                    $customer = $this->customerRepository->getById($customer_id);

                    
                }            
                
            }else{

                $customer = $this->customerRepository->getById($customer->getEntityId());

            }
                        
            $quote->assignCustomer($customer); //Assign quote to customer

        }else{

            // Set Customer Data on Qoute, Do not create customer.
            $quote->setCustomerFirstname("Guest First Name");
            $quote->setCustomerLastname("Guest Last Name");
            $quote->setCustomerEmail("guest@example.com");
            $quote->setCustomerIsGuest(true);

        }        

        
        $quoteItem = $this->cartItemFactory->create();

        $product_id = 0;

        //add items in quote
        foreach($orderData->items as $key => $value){
           
           //We then need to use $forceReload = true the last param for multiple products to avoid cached products 

            if(!empty($value->product_id)){

                    $product =  $this->productRepository->getById($value->product_id);
                    $product_id = $product->getId();

                }else if($value->product_sku){

                    $product = $this->productRepository->get($value->product_sku, false, $storeId, true);
                    $product_id = $product->getId();

                }else{

                    return false;
                }

            if(!empty($product_id)){

                $product_counts++;
                $quote->addProduct($product, intval($value->product_qty));
            }
           
            
        }


        if($product_counts == 0){

            return false;
        }

        
        if(!empty($delivery_address)){           

            if(!empty($delivery_address->name)){

                $full_name     = empty($full_name) ? $this->get_first_name_or_last_name($delivery_address->name) : $full_name;
                $first_name     = empty($first_name) ? $this->get_first_name_or_last_name($delivery_address->name) : $first_name;
                $last_name      =  empty($first_name) ? $this->get_first_name_or_last_name($delivery_address->name, 'last_name') : $last_name;
                
            }            
            
            $country        = $delivery_address->country;
            $city           = $delivery_address->city;
            $state          = $delivery_address->province;
            $address_2      = $delivery_address->area;
            $address_1      = $delivery_address->address;
            $lat            = $delivery_address->lat;
            $long           = $delivery_address->long;
            $postcode        = !empty($delivery_address->postal_code) ? $delivery_address->postal_code : $postcode;

        }

        // return Pakistan to PK or United Kingdom to UK//
        $country_id = array_search($country, \Zend_Locale::getTranslationList('territory'));

        $addresses = isset($customer) ? $customer->getAddresses() : [];

        $saveInAddressBook = 1;

        if(!empty($addresses)){

            foreach ($addresses as $key => $value) {       

                // Check if Address already exists
                if($value->getFirstname() == $this->get_first_name_or_last_name( $full_name) && $value->getLastname() ==  $this->get_first_name_or_last_name( $full_name, 'last_name') &&  $value->getTelephone() == $this->bsecureHelper->phoneWithCountryCode($phone, $country_code) && $value->getCity() == $city && $value->getCountryId() == $country_id && implode(" ",$value->getStreet()) == $address_1 .' '. $address_2){
                     
                    $saveInAddressBook = 0;
                    continue;
                }
            }                                                

        }
       

        $shipping_address = array(
                            'firstname' => $this->get_first_name_or_last_name( $full_name), //address Details
                            'lastname'  => $this->get_first_name_or_last_name( $full_name, 'last_name'),
                            'street'    => $address_1 .' '. $address_2,
                            'city'      => $city,
                            'country_id'=> $country_id,
                            'region'    => $state,
                            'postcode'  => $postcode,
                            'telephone' => $this->bsecureHelper->phoneWithCountryCode($phone, $country_code),
                            'gender'    => $gender,
                            'dob'       => $dob,
                            'fax'       => '',
                            'country_code' => $country_code,
                            'lat'       => $lat,
                            'long'      => $long,
                            'save_in_address_book' => $saveInAddressBook,
                            
                        );    


        //Set Address to quote

        //if(!empty($customer_id)){

            $quote->getBillingAddress()->addData($shipping_address);
            $quote->getShippingAddress()->addData($shipping_address);
        //}
        
        $shippingPrice = 0;
        $shippingMethod = 'freeshipping_freeshipping';
        $shippingpTitle = '';

        
 
        // Collect Rates and Set Shipping & Payment Method

        if(!empty($shipment_method)){
            
            if(!empty($shipment_method->cost)){

                $shippingMethod = 'flatrate_flatrate';

                $shippingPrice = $shipment_method->cost;
                
            }

            if(!empty($shipment_method->name)){                

                $shippingpTitle = $shipment_method->name;                
            }
        }

        $allActiveShippings = $this->shippingConfig->getActiveCarriers();

        // Check if bSecure Shipping is active
        if(!empty($allActiveShippings['bsecureshipping'])){

            $shippingMethod = 'bsecureshipping_bsecureshipping';
        }
 
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        
                        ->setShippingMethod($shippingMethod); //shipping method freeshipping_freeshipping

        if(!empty($shippingpTitle)){
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

        if(!empty($shippingPrice)){
            
            $order->setShippingAmount($shippingPrice);        
            $order->setBaseShippingAmount($shippingPrice);
            if(!empty($shippingpTitle)){
                $order->setShippingDescription($shippingpTitle);
            }
            $order->setGrandTotal($order->getGrandTotal() + $shippingPrice); //adding shipping price to grand total
        }

        
        $order->save();        

        $orderState = $this->magentoOrderStatus($placement_status);        
       
        $order->setState($orderState);
        $order->setStatus($orderState);
        // if order type manual then get bSecure Order ID
        if(!empty($order_type)){
            if(strtolower($order_type) == 'manual'){
                $merchant_order_id = $this->getBsecureCustomOrderId();
            }            
        }
        $details =  array(
                        '_bsecure_order_ref' => $bsecure_order_ref,
                        '_bsecure_order_type' => strtolower($order_type),
                        '_bsecure_order_id' => $merchant_order_id,
                        
                    );

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalInformation();       
        $newAdditionalData = !empty($additionalData) ? array_merge($additionalData,$details) : $details;
        $payment->setAdditionalInformation($newAdditionalData);

        $order->setData('bsecure_order_ref',$bsecure_order_ref);
        $order->setData('bsecure_order_type',$order_type);
        $order->setData('bsecure_order_id',$merchant_order_id);

       

        if(!empty($payment_method->name)){

            $orderNotes = "Payment Method: ".$payment_method->name;

            //if('Credit Card' == $payment_method->name && 5 == $payment_method->id){
               
                if(!empty($card_details)){
                    
                    $orderNotes = "Card Type: ".$card_details->card_type.'<br>';
                    $orderNotes .= "Card Holder Name: ".$card_details->card_name.'<br>';
                    $orderNotes .= "Card Number: ".$card_details->card_number.'<br>';
                    $orderNotes .= "Card Expire: ".$card_details->card_expire;                  

                }           

            //}

            $order->addStatusHistoryComment($orderNotes)
            ->setIsCustomerNotified(false)
            ->setEntityName('order');        
        }        

        $order->save();
        
        //$order->setEmailSent(0);
        $increment_id = $order->getRealOrderId();      

        if($order->getEntityId()){

           return $order->getEntityId();

        }else{

            return false;
        }
        
        
    }
    


    /**
     * Validate order data before saving to store
     * 
     * @param array $orderData
     * @return array
     * 
    */
    public function validateOrderData($orderData){


        if (empty($orderData->customer) ){

            //return  ['status' => true, 'msg' => __("No customer returned from bSecure server. Please resubmit your order.")];
        }


        if (empty($orderData->items) ){

            return  ['status' => true, 'msg' => __("No cart items returned from bSecure server. Please resubmit your order.")];

        }else{

            $product_id = 0;

            foreach ($orderData->items as $key => $value) {                


                if(!empty($value->product_id)){                 

                    $product =  $this->productRepository->getById($value->product_id);
                    $product_id = $product->getId();

                    if(empty($product_id)){

                        $msg =  __("No product found in store against product_id") . $value->product_id;

                    }

                }else if(!empty($value->product_sku)){

                    $product_id = $this->_product->load($this->_product->getIdBySku($value->product_sku));

                    if(empty($product_id)){

                        $msg =  __("No product found in store against SKU: ") . $value->product_sku;
                    }                   

                }

                if(empty($product_id)){

                    return  ['status' => true, 'msg' => $msg];
                    
                }                
            }
        }

        return ['status' => false, 'msg' => __('Order data validated successfully.')];

    }


    /*
     * Extract first_name or last_name from fullName
     */
    public function get_first_name_or_last_name($fullName,$nameType = 'first_name'){

        $fullnameArray  = explode(' ', $fullName);
        $first_name     = !empty($fullnameArray[0]) ? $fullnameArray[0] : "Customer";
        $last_name      = !empty($fullnameArray[1]) ? end($fullnameArray) : $first_name;
        $first_name     = str_replace(' '.$last_name, '', $fullName);

        return $nameType == 'last_name' ? trim($last_name) : trim($first_name);
    }


    /*
    * Map bSecure statuses with magento default statuses
    */
    public function magentoOrderStatus($placement_status){

        /*"order_status": {
            'created'       => 1,
            'initiated'     => 2,
            'placed'        => 3,
            'awaiting-confirmation' => 4,
            'canceled' => 5,
            'expired' => 6,
            'failed' => 7
        }*/

        $order_status = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $placement_status = (int) $placement_status;        

        switch ($placement_status) {
            case 1:
            case 2:    
            case 3:         
                $order_status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;           
            case 4:
                $order_status = \Magento\Sales\Model\Order::STATE_HOLDED;
                break;
            case 5:
            case 6:
            case 7:
                $order_status = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            /*case 6:
                $order_status = \Magento\Sales\Model\Order::STATE_CLOSED;
                break; */             
            default:
                $order_status = \Magento\Sales\Model\Order::STATE_PROCESSING;;
                break;
        }

        

        return $order_status;
    }



    public function getMagentoOrderByBsecureRefId($bsecure_order_ref){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('sales_order'); // the table name in this example is 'mytest'
        $orderTable = $resource->getTableName('sales_order');
         
        $fields = array('entity_id');
        $sql = $connection->select()
                          //->from($tableName); // to select all fields
                          ->from($tableName, $fields) // to select some particular fields      
                          ->where('bsecure_order_ref = ?', $bsecure_order_ref) // adding WHERE condition with AND
                          ->where('bsecure_order_ref <> ?', 'NULL'); // adding WHERE condition with AND
                          $result = $connection->fetchRow($sql); 

        if(!empty($result['entity_id'])){

            $order = $this->orderRepository->get($result['entity_id']);
            return $order;
        }

        
       /* if(!empty($result)){

            foreach ($result as $key => $value) {
                $additional_information = (json_decode($value['additional_information']));

                if(!empty($additional_information->_bsecure_order_ref)){

                    $cisess_data = [
                                    'bsecure_order_ref' => $additional_information->_bsecure_order_ref,
                                    'bsecure_order_type' => $additional_information->_bsecure_order_type,
                                    'bsecure_order_id' => $additional_information->_bsecure_order_id,
                                    ];

                    $connection->update(
                                            $orderTable,
                                            $cisess_data,
                                            ['entity_id = ?' => (int)$value['parent_id']]
                                        );

                    if($additional_information->_bsecure_order_ref == $bsecure_order_ref){

                        $order_id = $value['parent_id'];
                        $order = $this->orderRepository->get($order_id);
                        return $order;
                    }
                }
            }
        }*/

        return false;
    }



    public function get_cart_data(){

        $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
        $cart               = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $customerSession    = $objectManager->get('Magento\Customer\Model\Session');
        $productRepository  = $objectManager->get('Magento\Catalog\Model\ProductRepository');
        $helperImport       = $objectManager->get('Magento\Catalog\Helper\Image');
        $storeManager       = $objectManager->get('Magento\Store\Model\StoreManagerInterface');


        $subTotal = $cart->getQuote()->getSubtotal();
        $grandTotal = $cart->getQuote()->getGrandTotal();         

        // retrieve quote items collection
        $itemsCollection = $cart->getQuote()->getItemsCollection();

        // get array of all items what can be display directly
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();

        // retrieve quote items array
        $items = $cart->getQuote()->getAllItems();

        $discount_amount = 0;       

        $cart_data['products'] = [];
        $configurables = [];        
        $qty = 1;
       
        if(!empty($items)){
           
            foreach($items as $index => $item) {              
                
                // discount amount must be included with configurable products
                $discount_amount += $item->getDiscountAmount();
                
                if($item->getProductType() == 'configurable')  //configurable products
                {
                    $qty = $item->getQty();
                    continue;
                }
                else    // non-configurable product
                {
                    if (!$item->getParentItem())  // product which has not parent product 
                    {
                        $qty = $item->getQty();
                    }
                }            
   
                
                if($item->getProductType() != 'configurable'){                    

                    $product = $productRepository->getById($item->getProductId());

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

                    $specialPrice = !empty(($specialPrice)) ? floatval($specialPrice) : floatval($regularPrice);

                    //$imageUrl = $helperImport->init($product, 'product_page_image_large')->keepAspectRatio(true)->resize(400)->getUrl();   

                    $imageUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();


                    
                    
                    $cart_data['products'][] = [

                                                    'id' => $product->getId(),
                                                    'name' => $product->getName(),
                                                    'sku' => $product->getSku(),
                                                    'quantity' =>  $qty,
                                                    'price' => floatval($regularPrice),
                                                    'discount' => 0,
                                                    'sale_price' => $specialPrice,
                                                    'sub_total' => $specialPrice * $qty,
                                                    'image' => $imageUrl,
                                                    'short_description' => $objectManager->create('Magento\Framework\Escaper')->escapeHtml($product->getShortDescription()),
                                                    'description' => $objectManager->create('Magento\Framework\Escaper')->escapeHtml($product->getDescription()),  
                                                    //'description' => $product->getShortDescription(),  

                                                ];

                    
                }
                
            }
        }        

        $cart_data['total_amount'] = floatval($grandTotal);
        $cart_data['sub_total_amount'] = floatval($subTotal);
        $cart_data['discount_amount'] = floatval($discount_amount);
        $cart_data['currency_code'] = $storeManager->getStore()->getCurrentCurrency()->getCode();  


        return $cart_data; 

    } 



    public function get_customer_data(){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
        $addressRepository = $objectManager->get('Magento\Customer\Api\AddressRepositoryInterface');
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        $customerData = ['country_code' => '', 'phone_number' => ''];

        if($customerSession->isLoggedIn()) {

            $customerId = $customerSession->getCustomer()->getId();

            $customer = $customerRepository->getById($customerId);
            $billingAddressId = $customer->getDefaultBilling();
            $shippingAddressId = $customer->getDefaultShipping();

            //get default billing address
            
            $billingAddress = $addressRepository->getById($billingAddressId);
            
            
            $country_code = $customer->getCustomAttribute('country_code')->getValue();
            $bsecure_auth_code = $customer->getCustomAttribute('bsecure_auth_code')->getValue();
            $telephone =  $this->bsecureHelper->phoneWithoutCountryCode($billingAddress->getTelephone(), $country_code) ;

            $customerData = [
                            'name' => $customerSession->getCustomer()->getName(),
                            'email' => $customerSession->getCustomer()->getEmail(),
                            'country_code' => !empty($country_code) ? $country_code : '92',
                            'phone_number' => $telephone,
                        ];
            // if auth_code found then send it with request
            if(!empty($bsecure_auth_code)){
                $customerData['auth_code'] = $bsecure_auth_code;
            }
            
          }

          return $customerData;

    }



    /**
     * Create order at server
     *
     * @return array server response .
     */
    public function bsecureCreateOrder($accessToken){

        if(!$accessToken){

            throw new \Magento\Framework\Exception\AlreadyExistsException(
                __("Access token not found while sending request at bSecure server")
            );               
           
        }

        $cart_data = $this->get_cart_data();

        $request_data = [
                            'customer' => $this->get_customer_data(),
                            'products' => $cart_data['products'],
                            'order_id' => $this->getBsecureCustomOrderId(),                           
                            'currency_code' => $cart_data['currency_code'],
                            'total_amount' => $cart_data['total_amount'],
                            'sub_total_amount' => $cart_data['sub_total_amount'],
                            'discount_amount' => $cart_data['discount_amount'],
                        ];
        

        $config = $this->bsecureHelper->getBsecureConfig();            
        $this->order_create_endpoint = !empty($config->orderCreate) ? $config->orderCreate : "";

        $order_url = $this->order_create_endpoint;      

        $headers =  ['Authorization' => 'Bearer '.$accessToken];                              

        $params =   [
                        'method' => 'POST',
                        'body' => $request_data,
                        'headers' => $headers,                  

                    ];      
                
        $response = $this->bsecureHelper->bsecureSendCurlRequest($order_url,$params);       
    
        return $response;
        
    }


    public function get_product_for_api($product){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $imageUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

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

        $product_info = [
                            'id' => $product->getId(),
                            'name' => $product->getName(),
                            'sku' => $product->getSku(),                                  
                            'price' => floatval($regularPrice),
                            'sale_price' => $specialPrice,                                  
                            'image' => $imageUrl,
                            'short_description' => $objectManager->create('Magento\Framework\Escaper')->escapeHtml($product->getShortDescription()),
                            'description' => $objectManager->create('Magento\Framework\Escaper')->escapeHtml($product->getDescription()),
                            'stock_quantity' => $productQty,
                            'is_in_stock' => $productIsInStock,
                            'product_type' => $product->getTypeId()
                        ];


        return  $product_info;
    }

    /*
    * Generate/get bSecure custom order id 
    */
    public function getBsecureCustomOrderId(){


        // using timestamp in magento for custom order id
        return substr(time(),2); 


        $previous_bsecure_merchant_order_id = 1;

        $merchant_order_id = (int) $this->bsecureHelper->getConfig('universalcheckout/general/bsecure_merchant_order_id');
        $merchant_order_id = !empty($merchant_order_id) ? $merchant_order_id+1 : $previous_bsecure_merchant_order_id; 

        $bsecure_leading_zero_in_order_number = $this->bsecureHelper->getConfig('universalcheckout/general/bsecure_leading_zero_in_order_number');

        if(empty($bsecure_leading_zero_in_order_number)){
            // Update with default value
            $bsecure_leading_zero_in_order_number = 8;
            $this->bsecureHelper->setConfig('universalcheckout/general/bsecure_leading_zero_in_order_number',$bsecure_leading_zero_in_order_number);
        }       

        $this->bsecureHelper->setConfig('universalcheckout/general/bsecure_merchant_order_id',$merchant_order_id);
                
        $id_with_leading_zero = (str_pad($merchant_order_id, $bsecure_leading_zero_in_order_number, '0', STR_PAD_LEFT)); 

        return $id_with_leading_zero;
    }


    public function enable_freeshipping(){

        $isFreeShippingEnabled = $this->bsecureHelper->getConfig('carriers/freeshipping/enable');

        if(empty($isFreeShippingEnabled)){ 

            $this->bsecureHelper->setConfig('carriers/freeshipping/enable', true);
            $this->bsecureHelper->setConfig('carriers/freeshipping/free_shipping_subtotal', NULL);
            $this->bsecureHelper->setConfig('carriers/freeshipping/specificcountry', NULL);
            $this->bsecureHelper->setConfig('carriers/freeshipping/showmethod', false);
            $this->bsecureHelper->setConfig('carriers/freeshipping/sort_order', NULL);

        }        

    }

}