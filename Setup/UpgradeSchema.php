<?php

namespace Bsecure\UniversalCheckout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

            $moduleContext = $context;
            $quote = 'quote';
            $orderTable = 'sales_order';

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'bsecure_order_ref',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'bSecure order reference'
                    )
                );
            //Order table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'bsecure_order_type',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'bSecure order type'
                    )
                );

            //Order table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'bsecure_order_id',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'bSecure order id'
                    )
                );
        

        $setup->endSetup();
    }
}
