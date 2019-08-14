<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/**
 * Add yubikey field to table 'admin/user'
 */
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_email', 'varchar(128) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_pass', 'varchar(100) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_phone', 'varchar(60) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_Admin_enable', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_customer_key', 'varchar(25) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_api_key', 'varchar(50) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_token', 'varchar(50) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_otp', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_qr', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_configure', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_validated', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_login', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_admin_registered', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_downloaded_app', 'int null');

$installer->endSetup();