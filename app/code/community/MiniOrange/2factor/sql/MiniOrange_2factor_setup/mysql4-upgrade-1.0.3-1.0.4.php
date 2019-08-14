<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('admin/user'), 'inline_reg_status', 'varchar(128) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'kba_Configured', 'varchar(128) null');
$installer->getConnection()->addColumn($this->getTable('admin/role'), 'enable_two_factor', 'tinyint(1) NOT NULL DEFAULT 0');


$setup = Mage::getModel('customer/entity_setup', 'core_setup');	
	$setup->addAttribute('customer', 'inline_reg_status', array(
    'type' => 'varchar','input' => 'text','label' => 'Inline Registration Status','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	$setup->addAttribute('customer', 'kba_Configured', array(
    'type' => 'int','input' => 'text','label' => 'KBA Configured','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	
	
if (version_compare(Mage::getVersion(), '1.6.0', '<=')){
      $customer = Mage::getModel('customer/customer');
      $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
	  $setup->addAttributeToSet('customer', $attrSetId, 'General', 'inline_reg_status');
	  $setup->addAttributeToSet('customer', $attrSetId, 'General', 'kba_Configured');
	  
}
if (version_compare(Mage::getVersion(), '1.4.2', '>=')){
	 Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'inline_reg_status')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();
	 Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'kba_Configured')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();
}



$installer->getConnection()->addColumn($this->getTable('customer/customer_group'), 'enable_two_factor', 'tinyint(1) NOT NULL DEFAULT 0');


$installer->endSetup();