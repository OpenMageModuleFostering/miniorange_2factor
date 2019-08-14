<?php
class MiniOrange_2factor_Customer_InlineRegistrationController extends Mage_Core_Controller_Front_Action
{
	private $_helper1 = "MiniOrange_2factor";
	private $_helper2 = "MiniOrange_2factor/mo2fUtility";
	
	private function getSession(){
		return Mage::getSingleton('customer/session');
	}
	
	private function getHelper1(){
		return Mage::helper($this->_helper1);
	}
	
	private function getHelper2(){
		return Mage::helper($this->_helper2);
	}
	
	private function redirect($url){
		$redirectUrl = Mage::getModel('core/url')->getUrl($url);
		$this->_redirectUrl($redirectUrl); 
	}
	
	private function setInlineError($message){
		$this->getSession()->setminiError($message);
		$this->redirect("twofactorauth/InlineRegistration/index");
	}
	
	private function saveConfig($url,$value,$id){
		$data = array($url=>$value);
		$model = Mage::getModel('customer/customer')->load($id)->addData($data);
		try {
				$model->setId($id)->save(); 
			} catch (Exception $e){
				Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	private function saveTwoFactorType($authType,$phone,$id){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$content = $helper2->mo2f_update_userinfo($helper1->getConfig('miniorange_email',$id),$authType,$phone,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'));
		$response = json_decode($content, true); 
		if(strcasecmp($response['status'], 'SUCCESS') == 0) {
			$this->saveConfig('customer_twofactortype',$authType,$id);
			if(strcmp($authType, 'MOBILE AUTHENTICATION')==0 ||strcmp($authType, 'PUSH NOTIFICATIONS')==0 ||strcmp($authType, 'SOFT TOKEN')==0 ){
				$this->saveConfig('miniorange_mobileconfigured',1,$id);
				$this->saveConfig('customer_downloaded_app',1, $id);
			}else if(strcasecmp($authType, 'SMS') == 0 || strcasecmp($authType, 'PHONE VERIFICATION') == 0){
				$this->saveConfig('miniorange_phone',$session->getInlinePhone(),$id);
			}else if(strcasecmp($authType, 'GOOGLE AUTHENTICATOR') == 0){
				$this->saveConfig('customer_ga_configured',1,$id);
			}else if(strcasecmp($authType, 'KBA') == 0){
				$this->saveConfig('kba_Configured',1,$id);
			}
			$this->saveConfig('inline_reg_status',NULL,$id);
			$helper1->displayMessage($authType.' has been set as your Second Factor.','SUCCESS');
			$session->setLoginStatus('LOGIN_SUCCESS');
			Mage::dispatchEvent('customer_login_status');
		}
		else{ $this->setInlineError('There was an ERROR while setting your Authentication Type. Please Choose One from the list below:'); }
	}
	
	private function checkEndUser($email){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$customer = $session->getmoCustomer();
		$id = $session->getmoId();
		$check_user = json_decode($helper2->mo_check_user_already_exist($email,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
		if(json_last_error() == JSON_ERROR_NONE){
			if(strcasecmp($check_user['status'], 'USER_FOUND') == 0){
				$this->saveConfig('miniorange_email',$email,$id);
				$this->saveConfig('inline_reg_status','SETUP_TWO_FACTOR',$id);
				$session->unsInlineTxtId();
				$session->unsShowInlineValidate();
				$session->setShowInlineTwoFactor(1);
				$this->redirect("twofactorauth/InlineRegistration/index");
			}else if(strcasecmp($check_user['status'], 'USER_NOT_FOUND') == 0){
				$content = json_decode($helper2->mo_create_user($email,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'),$customer), true);
				if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					$this->saveConfig('miniorange_email',$email,$id);
					$this->saveConfig('inline_reg_status','SETUP_TWO_FACTOR',$id);
					$session->unsInlineTxtId();
					$session->unsShowInlineValidate();
					$session->setShowInlineTwoFactor(1);
					$this->redirect("twofactorauth/InlineRegistration/index");
				}else{ $this->setInlineError('There was an Error while creating End User!'); }
			}else if(strcasecmp($check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER') == 0){
				$this->setInlineError('The User already exists under another Admin.');
			}else{ 
				$this->setInlineError('User limit exceeded. Please upgrade your license to add more users.');
			}
		}else{ $this->setInlineError('There was an unknown error!'); }
	}
	
	private function processTwoFactor($authType){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $session->getmoId();
		$session->setAuthType($authType);
		if(strcasecmp($authType, 'MOBILE AUTHENTICATION') == 0 || strcasecmp($authType, 'SOFT TOKEN') == 0
				|| strcasecmp($authType, 'PUSH NOTIFICATIONS') == 0 ){
			$session->setShowConfigureMobile(1);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else if(strcasecmp($authType, 'SMS') == 0 || strcasecmp($authType, 'PHONE VERIFICATION') == 0){
			$session->setShowPhoneValidation(1);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else if(strcasecmp($authType, 'GOOGLE AUTHENTICATOR') == 0){
			$session->setShowGoogleAuthSetup(1);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else if(strcasecmp($authType, 'OUT OF BAND EMAIL') == 0){
			$this->saveTwoFactorType($authType,null,$session);
		}else if(strcasecmp($authType, 'KBA') == 0){
			$session->setShowKBASetup(1);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else{ $this->setInlineError('Invalid Second Factor Type. Please choose a valid Second Factor from the list below.'); }
		
	}
	
	private function sendValidationOTP($email){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $session->getmoId();
		$content = json_decode($helper2->send_otp_token($email,'EMAIL',$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)), true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$session->setInlineTxtId($content['txId']);
			$session->setShowInlineValidate(1);
			$session->setInlineEmail($email);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else{  $this->setInlineError('An error occurred while sending OTP to '.$email.'.');  }
	}
	
	public function preDispatch(){
		$helper1 = $this->getHelper1();
		if(!$helper1->getConfig('isCustomerEnabled')) {
			$this->_forward('defaultNoRoute');
		}
		parent::preDispatch();
	}
	
	public function indexAction(){
		$session = $this->getSession();
		if(!$session->getmoCustomer()){ $session->authenticate($this); return; }
        $this->loadLayout();
        $this->renderLayout();
        $session->unsminiError();
	}
	
	public function goBackLoginAction(){
		$session = $this->getSession();
		$session->setLoginStatus('GO_BACK_TO_LOGIN');
		Mage::dispatchEvent('customer_login_status');
	}
	
	public function setupUserAction(){
		$params = $this->getRequest()->getParams();
		$this->sendValidationOTP($params['setup-email']);
	}
	
	public function validateUserAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();		
		if(strcmp($params['submit'],'Validate OTP')==0){
			$content = json_decode($helper2->validate_otp_token( 'EMAIL', null, $session->getInlineTxtId() ,$params['setup-otp'],$helper1->getConfig('customerKey',$id), $helper1->getConfig('apiKey',$id)),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				$this->checkEndUser($session->getInlineEmail());
			}else{ 
				$this->setInlineError('Invalid OTP. Please enter the correct OTP'); 
			}
		}else{ 
			$this->sendValidationOTP($session->getInlineEmail()); 
		}
	}
	
	public function chooseTwoFactorAction(){
		$params = $this->getRequest()->getParams();
		$this->processTwoFactor($params['twofactor']);
	}
	
	public function configureMobileAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		if(strcmp($params['configure_mobile'],'Go Back')==0){
			$session->unsShowConfigureMobile();
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else {
			if(strcmp($params['configure_mobile'], 'Refresh QRCode')==0 || strcmp($params['configure_mobile'], 'Configure your phone')==0){
				$content = $helper2->register_mobile($helper1->getConfig('miniorange_email',$id),$id);
				$response = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					$session->setLoginQRCode($response['qrCode']);
					$session->setLogintxtId($response['txId']);
					$session->setShowInlineQrCode(1);
					$this->redirect("twofactorauth/InlineRegistration/index");
				}else{  $this->setInlineError('An error occured while contacting the server. Please try again.'); }
			}
		}
	}
	
	public function mobileRegistrationSuccessAction(){
		$id = $this->getSession()->getmoId();
		$this->saveTwoFactorType($this->getSession()->getAuthType(),null,$id);
	}
	
	public function mobileRegistrationFailedAction(){
		$session = $this->getSession();
		$session->unsLoginQRCode();
		$session->unsLogintxtId();
		$session->unsShowInlineQrCode();
		$session->unsShowConfigureMobile();
		 $this->setInlineError('Timed Out. Please Try Again.');
		$this->redirect("twofactorauth/InlineRegistration/index");
	}
	
	public function configurePhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		$content = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),$session->getAuthType(),$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id),$params['inlinetwofactor_phone']);
		$response = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE){
			$session->setLogintxId($response['txId']);
			$session->setInlinePhone($params['inlinetwofactor_phone']);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else{  $this->setInlineError('An error occured while sending the OTP. Please try again.'); }
	}
	
	public function validatePhoneNumberAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		if(strcmp($params['submit'],'Resend OTP')!=0){
			$content = $helper2->validate_otp_token(null,null,$session->getLogintxId(),$params['inlinephone_otp'],$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id));
			$response = json_decode($content, true);
			if(strcasecmp($response['status'], 'FAILED') != 0)
				$this->saveTwoFactorType($session->getAuthType(),$session->getInlinePhone(),$id);
			else{ $this->setInlineError('Invalid OTP! Please try again.');}
		}else{
			$phone=$session->getInlinePhone();
			$content = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),$session->getAuthType(),$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id),$phone);
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE){
				$session->setLogintxId($response['txId']);
				$session->setInlinePhone($phone);
				$session->setresendOTP(1);
				$this->redirect("twofactorauth/InlineRegistration/index");
			}else{  $this->setInlineError('An error occured while sending the OTP. Please try again.'); }
		}
		
	}
	
	public function chooseGAPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		$content = json_decode($helper2->mo2f_google_auth_service($helper1->getConfig('miniorange_email',$id),$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$session->setGAPhone($params['mo2f_app_type_radio']);
			$session->setGAQRCode($content['qrCodeData']);
			$session->setGASecret($content['secret']);
			$this->redirect("twofactorauth/InlineRegistration/index");
		}else{ $this->setInlineError('An error occured while contacting the server. Please try again.'); }
	}
	
	public function validateGATokenAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		$content = json_decode($helper2->mo2f_validate_google_auth($helper1->getConfig('miniorange_email',$id),$params['google_token'],$session->getGASecret(),$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0 && strcasecmp($content['message'], 'The OTP you have entered is incorrect.') != 0){
			$this->saveTwoFactorType($session->getAuthType(),null,$id);
		}else{  $this->setInlineError('Invalid Token. Please Try Again.'); }
	}
	
	public function saveKBAQuestionsAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$id = $session->getmoId();
		$kba_q1 = $params[ 'mo2f_kbaquestion_1' ];
		$kba_a1 = trim( $params[ 'mo2f_kba_ans1' ] );
		$kba_q2 = $params[ 'mo2f_kbaquestion_2' ];
		$kba_a2 = trim( $params[ 'mo2f_kba_ans2' ] );
		$kba_q3 = trim( $params[ 'mo2f_kbaquestion_3' ] );
		$kba_a3 = trim( $params[ 'mo2f_kba_ans3' ] );
		$kba_reg_reponse = json_decode($helper2->register_kba_details($helper1->getConfig('miniorange_email',$id),$kba_q1,$kba_a1,$kba_q2,$kba_a2,$kba_q3,$kba_a3,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
		if($kba_reg_reponse['status'] == 'SUCCESS'){
			$this->saveTwoFactorType($session->getAuthType(),null,$id);
		}else{ $this->setInlineError('An error occured. Please Try Again.'); }
	}
}