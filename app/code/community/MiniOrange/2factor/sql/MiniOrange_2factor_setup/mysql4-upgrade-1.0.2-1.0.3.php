<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('admin/user'), 'admin_ga_configured', 'int null');

$setup = Mage::getModel('customer/entity_setup', 'core_setup');	

	$setup->addAttribute('customer', 'customer_ga_configured', array(
    'type' => 'int','input' => 'text','label' => 'Google Authenticator Configured','global' => 1,'visible' => 1,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 1,'source'=> '',
	));
	
	
if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
      $customer = Mage::getModel('customer/customer');
      $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'customer_ga_configured');
	  
}
if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
	 Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'customer_ga_configured')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();

}


$installer->endSetup();