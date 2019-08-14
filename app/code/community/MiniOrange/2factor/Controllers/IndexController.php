<?php
 
class MiniOrange_2factor_IndexController extends Mage_Core_Controller_Front_Action
{
    /*public function indexAction(){
        $this->loadLayout();   
        $this->renderLayout();
    }*/
	
	/*public function checkemailAction(){
        $params = $this->getRequest()->getParams();
			$customer = Mage::helper('MiniOrange_2factor/customersetup');
			$content = json_decode($customer->check_customer($params['email']), true);
			if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){ 
				$content = json_decode($customer->send_otp_token($email), true); //send otp for verification
				if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					Mage::getSingleton('core/session')->setMySessionVariable($content['txId']);
					//save 
				}
			}
			$redirect = Mage::helper('core/url')->getHomeUrl().'customer/account/login#loginScreen';
			$this->_redirectUrl($redirect); 
    }
	
	
	protected function _customerExists($email){
		//called to check if customer already exists
		$websiteId = Mage::app()->getWebsite()->getId(); 
		$customer = Mage::getModel('customer/customer');
		if ($websiteId) {
			$customer->setWebsiteId($websiteId);
		}
		$customer->loadByEmail($email);
		if ($customer->getId()) {
			return $customer;
		}
		return false;
	}*/

}
 
?>
