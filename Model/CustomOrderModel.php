<?php

namespace Bsecure\UniversalCheckout\Model;

class CustomOrderModel extends \Magento\Framework\Model\AbstractModel
{
    protected $_orderCollectionFactory;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
    }

    public function getOrderCollection($bsecureOrderRef)
    {
        $collection = $this->_orderCollectionFactory->create()
         ->addAttributeToSelect('entity_id')
         ->addFieldToFilter('bsecure_order_ref', ['eq' => $bsecureOrderRef])
         ->getLastItem()
         ->toArray();
         
        if (!empty($collection['entity_id'])) {
            $order = $this->orderRepository->get($collection['entity_id']);
            return $order;
        }

        return false;
    }

    public function getOrderCollectionByBsecureId($bsecureId)
    {
        $collection = $this->_orderCollectionFactory->create()
         ->addAttributeToSelect('entity_id')
         ->addFieldToFilter('bsecure_order_id', ['eq' => $bsecureId])
         ->getLastItem()
         ->toArray();
         
        if (!empty($collection['entity_id'])) {
            $order = $this->orderRepository->get($collection['entity_id']);
            return $order;
        }

        return false;
    }
}
