<?php
class MiniOrange_2factor_Block_Mo2fConfig extends Mage_Core_Block_Template{
	
	public function isEnabled(){
		$customer = Mage::helper('MiniOrange_2factor');
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
		if($customer->getConfig('isEnabled',$id)==1){
			return 'checked';
		}
		else{
			return '';
		}
	}
	
	public function isRoleEnabled($id){
		if(Mage::helper('MiniOrange_2factor')->getConfig('role_enabled',$id)==1)
			return 'checked';
		else
			return '';
	}
	
	public function twofactorEnabledForRole($id){
		return Mage::helper('MiniOrange_2factor')->getConfig('role_enabled',$id);
	}
	
	public function isGroupEnabled($id){
		if(Mage::helper('MiniOrange_2factor')->getConfig('group_enabled',$id)==1)
			return 'checked';
		else
			return '';
	}
	
	public function isadminRememberDeviceEnabled(){
		if(Mage::helper('MiniOrange_2factor')->getConfig('admin_rmd_enable',null)==1)
			return 'checked';
		else
			return '';
	}
	
	public function iscustomerRememberDeviceEnabled(){
		if (Mage::helper('MiniOrange_2factor')->getConfig('customer_rmd_enable',null)==1)
			return 'checked';
		else
			return '';
	}
	
	public function isadmininlineRegistrationEnabled(){
		if(Mage::helper('MiniOrange_2factor')->getConfig('admin_inline_reg',null)==1)
			return 'checked';
		else
			return '';
	}
	
	public function iscustomerinlineRegistrationEnabled(){
		if (Mage::helper('MiniOrange_2factor')->getConfig('customer_inline_reg',null)==1)
			return 'checked';
		else
			return '';
	}
	
	public function getadminurl($value){
		return Mage::helper("adminhtml")->getUrl($value);
	}
	
	public function miniorange_geturl($value){
		return Mage::getUrl($value,array('_secure'=>true));
	}
	
	public function getcurrentUrl(){
		return Mage::getBaseUrl();
	}
	
	public function getHostURl(){
		return  Mage::helper('MiniOrange_2factor')->getHostURl();
	}
	
	public function getqrCode(){
		return Mage::getSingleton('admin/session')->getmo2fqrcode();
	}
	
	
	public function getTransactionId(){
		return  Mage::getSingleton('admin/session')->getmo2ftransactionId();
	}
	
	public function getcustomerqrCode(){
		return Mage::getSingleton('customer/session')->getmo2fqrcode();
	}
	
	
	public function getcustomerTransactionId(){
		return  Mage::getSingleton('customer/session')->getmo2ftransactionId();
	}
	
	public function downloaded(){
		if($this->getConfig('downloaded')==1){
			return "checked";
		}
		else{
			return;
		}
	}
	
	public function getCurrentUser(){
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			return $customer->getEmail();
		}
		return;
	}
	
	public function showEmail(){
		if(!Mage::getSingleton('customer/session')->getmoId())
		$id = Mage::getSingleton('admin/session')->getUser()->getUserId();
		else 
		$id = Mage::getSingleton('customer/session')->getmoId();
		$customer = Mage::helper('MiniOrange_2factor');
		return $customer->showEmail($id);
	}
	
	public function saveConfig($url,$value){
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
		$data = array($url=>$value);
		$model = Mage::getModel('admin/user')->load($id)->addData($data);
		try {
				$model->setId($id)->save(); 
			} catch (Exception $e){
				Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	public function getImage($image){
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
		return $url.'adminhtml/default/default/images/MiniOrange_2factor/'.$image.'.png';
	}
	
	public function isCustomerEnabled(){
		$customer = Mage::helper('MiniOrange_2factor');
		if($customer->getConfig('isCustomerEnabled','')==1){
			return 'checked';
		}
		else{
			return '';
		}
	}
	
	public function getConfig($config,$id=""){
		$user = Mage::helper('MiniOrange_2factor');
		if( !Mage::getSingleton('customer/session')->isLoggedIn() ) {
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			return $user->getConfig($config,$id);
		}
		else{
			$id = Mage::getSingleton('customer/session')->getCustomer()->getId();
			return $user->getConfig($config,$id);
		}
	}
	
	public function cURLEnabled(){
		$customer = Mage::helper('MiniOrange_2factor');
		return $customer->is_curl_installed();
	}
	
	public function getSession(){
		if( !Mage::getSingleton('customer/session')->isLoggedIn() ) {
			$session = Mage::getSingleton('customer/session');
		}else{
			$session = Mage::getSingleton('admin/session');
		}
		return $session;
	}
	
	public function getGAData($data){
		if( !Mage::getSingleton('customer/session')->isLoggedIn() )
			$mo2fdata = Mage::getSingleton('admin/session')->getmo2fGoogleAuth();
		else
			$mo2fdata = Mage::getSingleton('customer/session')->getmo2fGoogleAuth();
		if(strcmp($data,"QR")==0)
			return $mo2fdata['ga_qrCode'];
		else{
			return $mo2fdata['ga_secret'];
		}
	}
}