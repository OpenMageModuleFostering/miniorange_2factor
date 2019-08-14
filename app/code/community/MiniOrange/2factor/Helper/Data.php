<?php
class MiniOrange_2factor_Helper_Data extends Mage_Core_Helper_Abstract {
	private $hostname = "https://auth.miniorange.com";
	private $defaultCustomerKey = "16555";
	private $defaultApiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
	function adminExists($username) {
		$adminuser = Mage::getModel ( 'admin/user' );
		$adminuser->loadByUsername ( $username );
		if ($adminuser->getId ()) {
			return true;
		} else {
			return false;
		}
	}
	function getHostURl() {
		return $this->hostname;
	}
	function getdefaultCustomerKey() {
		return $this->defaultCustomerKey;
	}
	function getdefaultApiKey() {
		return $this->defaultApiKey;
	}
	function getAdmin($username) {
		$adminuser = Mage::getModel ( 'admin/user' );
		$adminuser->loadByUsername ( $username );
		if ($adminuser->getId ()) {
			return $adminuser;
		} else {
			return;
		}
	}
	
	/* Function to extract config stored in the database */
	function getConfig($config, $id="") {
		switch ($config) {
			case 'isEnabled' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_Admin_enable' );
				break;
			case 'isCustomerEnabled' :
				$result = Mage::getStoreConfig ( 'miniOrange/twofactor/customer/enable' );
				break;
			case 'status' :
				$result= Mage::getStoreConfig ( 'miniOrange/twofactor/registration/status' );
				break;
			case 'email' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_email' );
				break;
			case 'admin_reg_status' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'inline_reg_status' );
				break;
			case 'twofactortype' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_type' );
				break;
			case 'customerKey' :
				$result = Mage::getStoreConfig ( 'miniOrange/twofactor/customerKey' );
				break;
			case 'appSecret' :
				$result = Mage::getStoreConfig ( 'miniOrange/twofactor/appSecret' );
				break;
			case 'apiKey' :
				$result = Mage::getStoreConfig ( 'miniOrange/twofactor/apiKey' );
				break;
			case 'apiToken' :
				$result = Mage::getStoreConfig ( 'miniOrange/twofactor/twofactorToken' );
				break;
			case 'admin_rmd_enable' :
				$result = Mage::getStoreConfig( 'miniOrange/twofactor/admin/rmd_enable' );
				break;			
			case 'customer_rmd_enable' :
				$result = Mage::getStoreConfig( 'miniOrange/twofactor/customer/rmd_enable' );
				break;	
			case 'admin_inline_reg':
				$result = Mage::getStoreConfig( 'miniOrange/twofactor/admin/inline_registration' );
				break;
			case 'customer_inline_reg' :	
				$result = Mage::getStoreConfig( 'miniOrange/twofactor/customer/inline_registration' );
				break;
			case 'admin_ga' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'admin_ga_configured' );
				break;
			case 'admin_kba_Configured' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'kba_Configured' );
				break;
			case 'configure' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_configured' );
				break;
			case 'validated' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_validated' );
				break;
			case 'mainAdmin' :
				$result = $result = Mage::getStoreConfig ( 'miniOrange/twofactor/mainAdmin' );
				break;
			case 'downloaded' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_downloaded_app' );
				break;
			case 'phone' :
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'miniorange_2factor_phone' );
				break;
			case 'role_enabled' :
				$result = Mage::getModel ( 'admin/role' )->load ( $id )->getData ( 'enable_two_factor' );
				break;
			case 'group_enabled' :
				$result = Mage::getModel ( 'customer/group' )->load ( $id )->getData ( 'enable_two_factor' );
				break;
			case 'miniorange_phone' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'miniorange_phone' );
				break;
			case 'miniorange_mobileconfigured' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'miniorange_mobileconfigured' );
				break;
			case 'miniorange_email' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'miniorange_email' );
				break;
			case 'customer_validated' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'customer_validated' );
				break;
			case 'customer_downloaded_app' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'customer_downloaded_app' );
				break;
			case 'customer_twofactortype' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'customer_twofactortype' );
				break;	
			case 'customer_ga' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'customer_ga_configured' );
				break;
			case 'customer_kba_Configured' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'kba_Configured' );
				break;
			case 'customer_reg_status' :
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'inline_reg_status' );
				break;
			default :
				return;
				break;
		}
		return $result;
	}
	
	/* Function to show his partial registered email to user */
	function showEmail($id) {
		$email = $this->getConfig ( 'email', $id );
		$emailsize = strlen ( $email );
		$partialemail = substr ( $email, 0, 1 );
		$temp = strrpos ( $email, "@" );
		$endemail = substr ( $email, $temp - 1, $emailsize );
		for($i = 1; $i < $temp; $i ++) {
			$partialemail = $partialemail . 'x';
		}
		$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/* Function to show his partial phone number to user */
	function showPhone($id) {
		$phone = $this->getConfig ( 'phone', $id );
		$phonesize = strlen ( $phone );
		$endphone = substr ( $phone, $phonesize - 4, $phonesize );
		$partialphone = '+';
		for($i = 1; $i < $phonesize - 4; $i ++) {
			$partialphone = $partialphone . 'x';
		}
		$showphone = $partialphone . $endphone;
		
		return $showphone;
	}
	
	/* Function to show his partial phone number to user */
	function showCustomerPhone($id) {
		$phone = $this->getConfig ( 'miniorange_phone', $id );
		$phonesize = strlen ( $phone );
		$endphone = substr ( $phone, $phonesize - 4, $phonesize );
		$partialphone = '+';
		for($i = 1; $i < $phonesize - 4; $i ++) {
			$partialphone = $partialphone . 'x';
		}
		$showphone = $partialphone . $endphone;
	
		return $showphone;
	}
	
	function showCustomerEmail($id) {
		$email = $this->getConfig ( 'miniorange_email', $id );
		$emailsize = strlen ( $email );
		$partialemail = substr ( $email, 0, 1 );
		$temp = strrpos ( $email, "@" );
		$endemail = substr ( $email, $temp - 1, $emailsize );
		for($i = 1; $i < $temp; $i ++) {
			$partialemail = $partialemail . 'x';
		}
		$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/* Function to check if cURL is enabled */
	function is_curl_installed() {
		if (in_array ( 'curl', get_loaded_extensions () )) {
			return 1;
		} else
			return 0;
	}
	
	
	function displayMessage($message, $type) {
		Mage::getSingleton ( 'core/session' )->getMessages ( true );
		if (strcasecmp ( $type, "SUCCESS" ) == 0)
			Mage::getSingleton ( 'core/session' )->addSuccess ( $message );
		else if (strcasecmp ( $type, "ERROR" ) == 0)
			Mage::getSingleton ( 'core/session' )->addError ( $message );
		else if (strcasecmp ( $type, "NOTICE" ) == 0)
			Mage::getSingleton ( 'core/session' )->addNotice ( $message );
		else
			Mage::getSingleton ( 'core/session' )->addWarning ( $message );
	}
	
	/*RBA*/
	function mo2f_collect_attributes($email,$attributes,$id,$customerKey,$apiKey,$appSecret,$loginType=null){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if(strcasecmp ( $loginType, "ADMIN" ) == 0)
			$rbaenabled = $this->getConfig('admin_rmd_enable',$id);
		else if(strcasecmp ( $loginType, "CUSTOMER" ) == 0)
			$rbaenabled = $this->getConfig('customer_rmd_enable',$id);
		else
			$rbaenabled = 0;
		if($rbaenabled==1){
			$rba_response = json_decode($helper->mo2f_collect_attributes($email,$attributes,$customerKey,$apiKey),true); //collect rba attributes
			if(json_last_error() == JSON_ERROR_NONE){
				if($rba_response['status'] == 'SUCCESS'){ //attribute are collected successfully
					$sessionUuid = $rba_response['sessionUuid'];
					$rba_risk_response = json_decode($helper->mo2f_evaluate_risk($email,$sessionUuid,$customerKey,$apiKey,$appSecret),true); // evaluate the rba risk
					if(json_last_error() == JSON_ERROR_NONE){
						if($rba_risk_response['status'] == 'SUCCESS' || $rba_risk_response['status'] == 'WAIT_FOR_INPUT'){
							$mo2f_rba_status = array();
							$mo2f_rba_status['status'] = $rba_risk_response['status'];
							$mo2f_rba_status['sessionUuid'] = $sessionUuid;
							$mo2f_rba_status['decision_flag'] = true;
							return $mo2f_rba_status;
						}else{
							$mo2f_rba_status = array();
							$mo2f_rba_status['status'] = $rba_risk_response['status'];
							$mo2f_rba_status['sessionUuid'] = $sessionUuid;
							$mo2f_rba_status['decision_flag'] = false;
							return $mo2f_rba_status;
						}
					}else{
						$mo2f_rba_status = array();
						$mo2f_rba_status['status'] = 'JSON_EVALUATE_ERROR';
						$mo2f_rba_status['sessionUuid'] = $sessionUuid;
						$mo2f_rba_status['decision_flag'] = false;
						return $mo2f_rba_status;
					}
				}else{
					$mo2f_rba_status = array();
					$mo2f_rba_status['status'] = 'ATTR_NOT_COLLECTED';
					$mo2f_rba_status['sessionUuid'] = '';
					$mo2f_rba_status['decision_flag'] = false;
					return $mo2f_rba_status;
				}
			}else{
				$mo2f_rba_status = array();
				$mo2f_rba_status['status'] = 'JSON_ATTR_NOT_COLLECTED';
				$mo2f_rba_status['sessionUuid'] = '';
				$mo2f_rba_status['decision_flag'] = false;
				return $mo2f_rba_status;
			}
		}else{
			$mo2f_rba_status = array();
			$mo2f_rba_status['status'] = 'RBA_NOT_ENABLED';
			$mo2f_rba_status['sessionUuid'] = '';
			$mo2f_rba_status['decision_flag'] = false;
			return $mo2f_rba_status;
		}
	}
}  