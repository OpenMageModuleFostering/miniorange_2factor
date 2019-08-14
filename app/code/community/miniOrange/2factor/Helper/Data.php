<?php
class MiniOrange_2factor_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $hostname = "https://auth.miniorange.com";
	
	function adminExists($username){
		$adminuser = Mage::getModel('admin/user');
		$adminuser->loadByUsername($username);
		if ($adminuser->getId()){
			return true;
		}
		else{
			return false;
		}
	}
	
	function getHostURl(){
		return $this->hostname;
	}
	
	function getAdmin($username){
		$adminuser = Mage::getModel('admin/user');
		$adminuser->loadByUsername($username);
		if ($adminuser->getId()){
			return $adminuser;
		}
		else{
			return;
		}
	}
	
	/*Function to extract config stored in the database*/
	function getConfig($config,$id){
		switch($config){
			case 'isEnabled':
				$result = Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_Admin_enable');
				break;
			case 'isCustomerEnabled':
				$result = Mage::getStoreConfig('miniOrange/2factor/customer/enable');
				break;
			case 'email':
				$result =  Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_email');
				break;
			case 'pass':
				$result =  Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_pass');
				break;
			case 'customerKey':
				$result = Mage::getStoreConfig('miniOrange/2factor/customerKey');
				break;
			case 'apiKey':
				$result = Mage::getStoreConfig('miniOrange/2factor/apiKey');
				break;
			case 'apiToken':
				$result = Mage::getStoreConfig('miniOrange/2factor/2factorToken');
				break;
			case 'otp':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_otp');
				break;	
			case 'qrcode':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_qr');
				break;
			case 'configure':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_configure');
				break;
			case 'validated':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_validated');
				break;
			case 'login':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_login');
				break;
			case 'mainAdmin':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_admin_registered');
				break;
			case 'downloaded':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_downloaded_app');
				break;
			case 'phone':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_phone');
				break;
			case 'customer_mobile_configured':
				$result =   Mage::getModel('customer/customer')->load($id)->getData('miniorange_phone');
				break;
			case 'customer_phone':
				$result =   Mage::getModel('customer/customer')->load($id)->getData('miniorange_mobileconfigured');
				break;
			default:
				return;
				break;
		}
			return $result;
	}
	
	/*Function to show his partial registered email to user*/
	function showEmail($id){
			$email = $this->getConfig('email',$id);
			$emailsize = strlen($email);
			$partialemail = substr($email,0,1);
			$temp = strrpos($email,"@");
			$endemail = substr($email,$temp-1,$emailsize);
			for($i=1;$i<$temp;$i++){
				$partialemail = $partialemail . 'x';
			}
			$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/*Function to check if cURL is enabled*/
	function is_curl_installed() {
		if  (in_array  ('curl', get_loaded_extensions())) {
			return 1;
		} else 
			return 0;
	}
	
	function displayMessage($message,$type){
		Mage::getSingleton('core/session')->getMessages(true);
		if(strcasecmp( $type,"SUCCESS") == 0)
			Mage::getSingleton('core/session')->addSuccess($message);
		else if(strcasecmp($type,"ERROR") == 0)
			Mage::getSingleton('core/session')->addError($message);
		else if(strcasecmp($type,"NOTICE")==0)
			Mage::getSingleton('core/session')->addNotice($message);
		else
			Mage::getSingleton('core/session')->addWarning($message);
	}
	
}  