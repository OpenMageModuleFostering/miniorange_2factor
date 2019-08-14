<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_email', 'varchar(128) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_pass', 'varchar(100) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_phone', 'varchar(60) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_Admin_enable', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_otp', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_qr', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_show_configure', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_validated', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_login', 'int null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'miniorange_2factor_downloaded_app', 'int null');


$installer->endSetup();