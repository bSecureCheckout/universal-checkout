<?php
namespace Bsecure\UniversalCheckout\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory, Config $eavConfig)
	{
		$this->eavSetupFactory = $eavSetupFactory;
		$this->eavConfig       = $eavConfig;
	}

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSetupFactory = $objectManager->create('Magento\Customer\Setup\CustomerSetupFactory');

        $setupInterface = $objectManager->create('Magento\Framework\Setup\ModuleDataSetupInterface');

        $customerSetup = $customerSetupFactory->create(['setup' => $setupInterface]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSetFactory = $objectManager->create('Magento\Eav\Model\Entity\Attribute\SetFactory');

        /** @var $attributeSet AttributeSet */
        $attributeSet = $attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'country_code', [
            'type' => 'varchar',
            'label' => 'Country Code',
            'input' => 'text',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1000,
            'position' => 1000,
            'system' => 0,
        ]);

        // more used_in_forms ['adminhtml_checkout','adminhtml_customer','adminhtml_customer_address','customer_account_edit','customer_address_edit','customer_register_address']
        
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'country_code')
        ->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
        ]);

        $attribute->save();


        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_user_account_email', [
            'type' => 'varchar',
            'label' => 'bSecure user account email',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1001,
            'position' => 1001,
            'system' => 0,
        ]);

  
        
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_user_account_email')
        ->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
        ]);

        $attribute->save();


        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_access_token', [
            'type' => 'varchar',
            'label' => 'bSecure access token',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1002,
            'position' => 1002,
            'system' => 0,
        ]);

                
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_access_token')
        ->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
        ]);

        $attribute->save();


        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_auth_code', [
            'type' => 'varchar',
            'label' => 'bSecure auth code',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'sort_order' => 1003,
            'position' => 1003,
            'system' => 0,
        ]);

                
        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'bsecure_auth_code')
        ->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => false,
        ]);

        $attribute->save();       
        

	}
}