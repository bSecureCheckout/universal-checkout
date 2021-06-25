<?php

namespace Bsecure\UniversalCheckout\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;
use Bsecure\UniversalCheckout\Helper\Data as BsecureHelper;
use Psr\Log\LoggerInterface as Logger;

class InstallData implements InstallDataInterface
{
    public function __construct(
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        Customer $customer,
        BsecureHelper $bsecureHelper,
        Logger $logger
    ) {
        
        $this->customerSetupFactory = $customerSetupFactory;
        $this->setFactory           = $setFactory;
        $this->customer           = $customer;
        $this->bsecureHelper     = $bsecureHelper;
        $this->logger     = $logger;
    }
    
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) //phpcs:ignore
    {
        $moduleContext = $context;
        $setupInterface = $setup;
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setupInterface]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
       
        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->setFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
       
        $customerSetup->addAttribute(
            $this->customer::ENTITY,
            'country_code',
            [
            'type' => 'varchar',
            'label' => 'Country Code',
            'input' => 'text',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1000,
            'position' => 1000,
            'system' => 0,
            ]
        );
        
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()
        ->getAttribute($this->customer::ENTITY, 'country_code')
        ->addData(
            [
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
            ]
        );

        $attribute->save();

        $customerSetup->addAttribute(
            $this->customer::ENTITY,
            'bsecure_user_account_email',
            [
            'type' => 'varchar',
            'label' => 'bSecure user account email',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1001,
            'position' => 1001,
            'system' => 0
            ]
        );
        
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()
        ->getAttribute($this->customer::ENTITY, 'bsecure_user_account_email')
        ->addData(
            [
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
            ]
        );

        $attribute->save();

        $customerSetup->addAttribute(
            $this->customer::ENTITY,
            'bsecure_access_token',
            [
            'type' => 'varchar',
            'label' => 'bSecure access token',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1002,
            'position' => 1002,
            'system' => 0,
            ]
        );
                
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()
        ->getAttribute($this->customer::ENTITY, 'bsecure_access_token')
        ->addData(
            [
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
            ]
        );

        $attribute->save();

        $customerSetup->addAttribute(
            $this->customer::ENTITY,
            'bsecure_auth_code',
            [
            'type' => 'varchar',
            'label' => 'bSecure auth code',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1003,
            'position' => 1003,
            'system' => 0,
            ]
        );
                
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()
        ->getAttribute($this->customer::ENTITY, 'bsecure_auth_code')
        ->addData(
            [
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
            ]
        );

        $attribute->save();

        // Handle Installation notitficatio to bSecure //

        $storeId = $this->bsecureHelper->getConfig('universalcheckout/general/bsecure_store_id');

        $this->logger->debug("------installData------storeId: ".$storeId);

        if (!empty($storeId)) {

            $this->bsecureHelper->installNotification();

        } else {

            $this->bsecureHelper->setConfig('universalcheckout/general/bsecure_installed', 1);

        }
    }
}
