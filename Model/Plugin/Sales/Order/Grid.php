<?php

namespace Bsecure\UniversalCheckout\Model\Plugin\Sales\Order;

class Grid
{

    public static $table = 'sales_order_grid';

    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
    }

    public function afterSearch($intercepter, $collection)
    {

        $dbPrefix = ($this->deploymentConfig->get('db/table_prefix'));
        $mainTable = $dbPrefix . self::$table;
        
        if ($collection->getMainTable() === $mainTable) {
            $collection->addFieldToFilter('status', ['neq' => 'bsecure_draft']);
        }
        return $collection;
    }
}
