<?php
class MiniOrange_2factor_Model_Session extends Mage_Customer_Model_Session{
	
	public function login($username, $password)
    {
       /* 
	    * Overriding the core session class to dispatch our event for user authentication
	    */
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
		if ($customer->authenticate($username, $password)) {
			$helper1 = Mage::helper('MiniOrange_2factor');
			$helper2 = Mage::helper('MiniOrange_2factor/Mo2fUtility');
			$session = Mage::getSingleton('customer/session');
			$id = $customer->getId();
			$attributes = $session->getMo2fCustomerAttr();
			$apiKey = $helper1->getConfig('apiKey',$id);
			$customerKey = $helper1->getConfig('customerKey',$id);
			$appSecret = $helper1->getConfig('appSecret',$id);
			$groupId = $customer->getGroupId();
			if(Mage::helper('MiniOrange_2factor')->getConfig('group_enabled',$groupId)==1){
			if($helper1->getConfig('customer_reg_status',$id)=='' && $helper1->getConfig('miniorange_email',$id)!=''){
				if($helper1->getConfig('miniorange_email',$id) && $helper1->getConfig('isCustomerEnabled',$id)){
					$mo2f_rba_status = $helper1->mo2f_collect_attributes($helper1->getConfig('miniorange_email',$id),stripslashes($attributes),$id,$customerKey,$apiKey,$appSecret,'CUSTOMER'); //RBA - Attributes
					if($mo2f_rba_status['status'] == 'SUCCESS' && $mo2f_rba_status['decision_flag']){
						$session->setbyPassLogin(true);
						$this->setCustomerAsLoggedIn($customer);
					}else{
						$session->setMo2fRba($mo2f_rba_status);
						Mage::dispatchEvent('miniorange_2factor_validate',array( 'customer' => $customer ));
						return true;
					}
				}else{
					$this->setCustomerAsLoggedIn($customer);
				}
			}else{
				if(strcmp($helper1->getConfig('customer_inline_reg',$id),'0')==0 || strcmp($helper1->getConfig('customer_inline_reg',$id),'')==0 )
					$this->setCustomerAsLoggedIn($customer);
				else
					Mage::dispatchEvent('miniorange_2factor_inline_registration',array( 'customer' => $customer ));
				return true;
			}
			}else{ $this->setCustomerAsLoggedIn($customer); }
        	return false;
		}
    }
}