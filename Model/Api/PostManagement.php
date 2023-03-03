<?php

namespace Bsecure\UniversalCheckout\Model\Api;

use Bsecure\UniversalCheckout\Api\PostManagementInterface;

class PostManagement implements PostManagementInterface
{
    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,
        \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->product              = $product;
        $this->storeManager         = $storeManager;
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->productRepository    = $productRepository;
        $this->orderHelper          = $orderHelper;
        $this->bsecureHelper        = $bsecureHelper;
        $this->stockRegistry        = $stockRegistry;
        $this->orderRepository      = $orderRepository;
        $this->json                 = $json;
        $this->request              = $request;
    }
    
    /**
     * @api
     * @param string $sku
     * @return mixed[]
     * @since 1.0.0
     */
    public function getPost($sku)
    {

        $returnRersult = [];

        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

        if ($moduleEnabled != 1) {
            $returnRersult = [
                                'status' => false,
                                "msg" => __("bSecure Magento Module is disabled!")
                            ];

            http_response_code(422);
            return json_encode($returnRersult);
        }

        if (!filter_var($sku, FILTER_SANITIZE_SPECIAL_CHARS)) {
            $returnRersult = [
                                'status' => false,
                                "msg" => __("Invalid sku provided!")
                            ];

            http_response_code(422);
            return json_encode($returnRersult);
        }
        $product = $this->productRepository->get($sku);

        if (empty($product->getId())) {
            $returnRersult =  [
                                'status' => false,
                                "msg" => __("No product found for provided sku!")
                            ];

            http_response_code(422);
            return json_encode($returnRersult);
        }

        $productStock = $this->stockRegistry->getStockItem($product->getId());
        $productIsInStock = $productStock->getIsInStock();
        $isSalable = $product->isSalable();

        if (! $isSalable) {
            $returnRersult = [
                                'status' => false,
                                "msg" => __("Product is not salable"),
                                'product_details' => []
                            ];
            http_response_code(422);
            return json_encode($returnRersult);
        }

        if (! $productIsInStock) {
            $returnRersult = [
                                'status' => false,
                                "msg" => __("Product is out of stock"),
                                'product_details' => []
                            ];
            http_response_code(422);
            return json_encode($returnRersult);
        }
            
        $inStockLabel = "in stock";
        $productInfo = $this->orderHelper->getProductForApi($product);

        if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'configurable') {
            $children  = $product->getTypeInstance()->getUsedProductIds($product);
            ;

            if (!empty($children)) {
                foreach ($children as $key => $value) {
                    $_product = $this->product->load($value);

                    $productInfo['children_products'][] = $this->orderHelper->getProductForApi($_product);
                }
            }
        }

        $returnRersult = [
                            'status' => true,
                            "msg" => __("Product is " . $inStockLabel),
                            'product_details' => $productInfo
                        ];
        http_response_code(200);
        return json_encode($returnRersult);
    }
   
    /**
     * Post for product api
     * @param string POST
     * @return mixed[]
     * @since 1.0.0
     */

    public function manageOrder()
    {
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

        if ($moduleEnabled != 1) {
            $returnRersult = [
                                'status' => false,
                                "msg" => __("bSecure Magento Module is disabled!")
                            ];

            http_response_code(422);
            return json_encode($returnRersult);
        }
        
        $orderData = json_decode($this->request->getContent());

        $returnRersult = ['status' => false, 'msg' => __("Invalid Request")];

        $validateOrderData =  $this->orderHelper->validateOrderData($orderData);

        if (!empty($validateOrderData['status'])) {
            $returnRersult = $validateOrderData;
        } else {
            if ($orderData->placement_status == 2) {
                $msg = __("Sorry! Your order has not been proccessed.");
                $returnRersult = [
                                'status' => false,
                                'msg' => __($msg)
                            ];
            } else {
                $orderId = $this->orderHelper->createMagentoOrder($orderData);

                if ($orderId > 0) {
                    $order = $this->orderRepository->get($orderId);

                    $bsecureOrderId = $order->getData('bsecure_order_id');
                    
                    $returnRersult = [
                                        'status' => true,
                                        'msg' => __("Order added successfully at magento."),
                                        'bsecure_order_id' => $bsecureOrderId
                                    ];

                    if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                        $returnRersult = [
                                            'status' => true,
                                            'msg' => __("Sorry! Your order has been " . $order->getStatus()),
                                            'bsecure_order_id' => $bsecureOrderId
                                        ];
                    }
                } else {
                    $msg = __("Unable to create order at magento. Please contact administrator or retry");
                    $returnRersult = [
                                        'status' => false,
                                        'msg' => __($msg)
                                    ];
                }
            }
        }
        
        $headerStatus = 200;
        if (!$returnRersult['status']) {
            $headerStatus = 422;
        }
       
        http_response_code($headerStatus);
        return json_encode($returnRersult);
    }
}
