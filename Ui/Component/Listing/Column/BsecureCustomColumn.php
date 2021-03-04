<?php

namespace Bsecure\UniversalCheckout\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Sales\Model\Order;

class BsecureCustomColumn extends Column
{
    protected $_searchCriteria;
    protected $_orderRepository;
    protected $_order;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        Order $order,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_order  = $order;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $orderId = (int) $item["entity_id"];
                
                   $order  = $this->_order->load($orderId);
                   $bsecureOrderId = $order->getData("bsecure_order_id");
                //$item['bsecure_order_id'] = $bsecureOrderId;
            }
        }

        return $dataSource;
    }
}
