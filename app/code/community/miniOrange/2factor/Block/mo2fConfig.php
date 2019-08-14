<?php
class MiniOrange_2factor_Block_mo2fConfig extends Mage_Core_Block_Template{
	
	
	public function isEnabled(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
		if($customer->getConfig('isEnabled',$id)==1){
			return 'checked';
		}
		else{
			return '';
		}
	}
	
	public function getadminurl($value){
		return Mage::helper("adminhtml")->getUrl($value);
	}
	
	public function getcurrentUrl(){
		return Mage::getBaseUrl();
	}
	
	public function getHostURl(){
		return  Mage::helper('MiniOrange_2factor/mo2fUtility')->getHostURl();
	}
	
	public function getqrCode(){
		return Mage::getSingleton('core/session')->getmo2fqrcode();
	}
	
	
	public function getTransactionId(){
		return  Mage::getSingleton('core/session')->getmo2ftransactionId();
	}
	
	public function downloaded(){
		if($this->getConfig('downloaded')==1){
			return "checked";
		}
		else{
			return;
		}
	}
	
	public function showEmail(){
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
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
				Mage::log($e->getMessage(), null, 'miniorage_error.log', true);
		}
	}
	
	public function getImage($image){
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
		return $url.'adminhtml/default/default/images/MiniOrange_2factor/'.$image.'.png';
	}
	
	public function getEmail(){
		return Mage::getStoreConfig('miniorange_2factor_options/register/miniorange_2factor_username');
	}
	
	public function getConfig($config,$id=""){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($id!=""){
			return $customer->getConfig($config,$id);
		}
		else{
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			return $customer->getConfig($config,$id);
		}
	}
	
	public function cURLEnabled(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		return $customer->is_curl_installed();
	}
	
	
	
}