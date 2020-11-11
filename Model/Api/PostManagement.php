<?php 

namespace Bsecure\UniversalCheckout\Model\Api;
 
class PostManagement
{

    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->product              = $product;        
        $this->storeManager         = $storeManager;
        $this->resultJsonFactory    = $resultJsonFactory;        
        $this->productRepository    = $productRepository;        
        $this->orderHelper          = $orderHelper;        
        $this->stockRegistry        = $stockRegistry;        
        $this->orderRepository      = $orderRepository;        
        $this->json                 = $json;        
        $this->request              = $request;        
    }

    // @codingStandardsIgnoreStart
    /**
     * {@inheritdoc}
     */
    public function getPost($sku)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
        $returnRersult = array();

        if (!filter_var($sku, FILTER_SANITIZE_STRING)) {
            $returnRersult = array('status' => false, "msg" => __("Invalid sku provided!"));
        } else {
            $product = $this->productRepository->get($sku);                        

            if (empty($product->getId())) {
                $returnRersult =  array('status' => false, "msg" => __("No product found for provided sku!"));              
            } else {
                $productStock = $this->stockRegistry->getStockItem($product->getId());
                $productIsInStock = $productStock->getIsInStock();

                if ($productIsInStock) {
                    $inStockLabel = "in stock";
                    $product_info = $this->orderHelper->get_product_for_api($product);

                    if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'configurable') {
                        $children  = $product->getTypeInstance()->getUsedProductIds($product);;

                        if (!empty($children)) {
                            foreach ($children as $key => $value) {
                                $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($value);

                                $product_info['children_products'][] = $this->orderHelper->get_product_for_api($_product);
                            }
                        }
                    }


                    $returnRersult = array('status' => true, "msg" => __("Product is ".$inStockLabel),'product_details' => $product_info); 
                } else {
                    $returnRersult = array('status' => false, "msg" => __("Product is out of stock"),'product_details' => array()); 
                }
            }
        }

        $headerStatus = 200;
        if (!$returnRersult['status']) {
            $headerStatus = 422;
        }

        @header('Content-Type: application/json');
        http_response_code($headerStatus);
         echo json_encode($returnRersult); exit;         
        
    }
    // @codingStandardsIgnoreEnd

    public function manageOrder()
    {
        $orderData = json_decode($this->request->getPost());
        $returnRersult = array('status' => false, 'msg' => __("Invalid Request")); 

        $validateOrderData =  $this->orderHelper->validateOrderData($orderData);

        if (!empty($validateOrderData['status'])) {
            $returnRersult = $validateOrderData;
        } else {
            $orderId = $this->orderHelper->createMagentoOrder($orderData); 

            if ($orderId > 0) {
                $order = $this->orderRepository->get($orderId);

                $bsecureOrderId = $order->getData('bsecure_order_id');
                
                $returnRersult = array('status' => true, 'msg' => __("Order added successfully at magento."), 'bsecure_order_id' => $bsecureOrderId); //phpcs:ignore

                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $returnRersult = array('status' => true, 'msg' => __("Sorry! Your order has been ".$order->getStatus()), 'bsecure_order_id' => $bsecureOrderId); //phpcs:ignore
                }
            } else {
                $returnRersult = array('status' => false, 'msg' => __("Unable to create order at magento. Please contact administrator or retry")); //phpcs:ignore
            }
        }
        
        $headerStatus = 200;
        if (!$returnRersult['status']) {
            $headerStatus = 422;
        }

        // @codingStandardsIgnoreStart
        @header('Content-Type: application/json');
        http_response_code($headerStatus);
        echo json_encode($returnRersult); exit;
        // @codingStandardsIgnoreEnd
    }
}
