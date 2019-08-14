<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_type', 'varchar(128) null');


	$setup = Mage::getModel('customer/entity_setup', 'core_setup');	

	$setup->addAttribute('customer', 'miniorange_phone', array(
    'type' => 'varchar','input' => 'text','label' => 'miniOrange Phone','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));

	$setup->addAttribute('customer', 'miniorange_mobileconfigured', array(
    'type' => 'int','input' => 'text','label' => 'miniOrange MobileConfigured','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	
	$setup->addAttribute('customer', 'miniorange_email', array(
    'type' => 'varchar','input' => 'text','label' => 'miniOrange Email','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	
	$setup->addAttribute('customer', 'customer_downloaded_app', array(
    'type' => 'int','input' => 'text','label' => 'Customer Downloaded App','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	
	
if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
      $customer = Mage::getModel('customer/customer');
      $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'miniorange_phone');
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'miniorange_mobileconfigured');
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'miniorange_email');
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'customer_downloaded_app');
}
if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
	 Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'miniorange_phone')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();
	
	Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'miniorange_mobileconfigured')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();
	
	Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'miniorange_email')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();

	Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'customer_downloaded_app')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();

}

$installer->endSetup();