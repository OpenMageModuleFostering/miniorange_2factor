<?php
class MiniOrange_2factor_Model_Observer
{
	public function controllerActionPredispatch(Varien_Event_Observer $observer){
		
		$request = Mage::app()->getRequest();
		$session = Mage::getSingleton('adminhtml/session');
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');	
		$data = Mage::helper('MiniOrange_2factor');	
			if ($request->getRequestedControllerName() == 'index' && $request->getRequestedActionName() == 'login'){
				if($session->getInlineValidateStatus()){
					$request->setControllerName('inlineRegistration')->setActionName('index')->setDispatched(false);
				}else{
					$request->setControllerName('miniOrange')->setActionName('login')->setDispatched(false);		
					if($session->getLoginStatus()!='MO_2_FACTOR_CHALLENGE_AUTHENTICATION'){
						$this->unsetSessionVariables($session);
						$session->unsshowforgotphone();
					}
					$session->unsLoginStatus();
				}
			}else{
				if($request->getPost('miniorange_mobile_validation_success')){	
					$this->checkRBA($session);
				}else if($request->getPost('miniorange_mobile_validation_failed')){
					$session->unsLoginStatus();
					$session->unsshowEmail();
					$session->unsshowPhone();
					$session->unsShowGAScreen();
					$this->unsetSessionVariables($session);
					$session->addError("Authentication Failed");
				}else if($request->getPost('miniorange-username')){
					$user = Mage::getModel('admin/user');
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					if( $session->getCaptchaStatus()!='CHECK_CAPTCHA' && $this->checkCaptcha($observer)){
					if($session->getLoginStatus()!='MO_2_FACTOR_CHALLENGE_AUTHENTICATION'){
						if($data->adminExists($request->getPost('miniorange-username'))){
								if($user->authenticate($request->getPost('miniorange-username'),$request->getPost('miniorange-password'))){
										$admin = $user->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password'));
										$id = $admin->getUserId();
										$role = $admin->getRole()->getData('enable_two_factor');
										if ($role==1){
										$attributes = $request->getPost('miniorange_rba_attribures');
										if((strcmp($data->getConfig('admin_inline_reg',$id),'0')==0 || strcmp($data->getConfig('admin_inline_reg',$id),'')==0) || ($data->getConfig('admin_reg_status',$id)=='' && $data->getConfig('email',$id)!='')){
											if($data->getConfig('isEnabled',$id)==1){
											$apiKey = $data->getConfig('apiKey',$id);
											$customerKey = $data->getConfig('customerKey',$id);
											$appSecret = $data->getConfig('appSecret',$id);
											$showemail = $data->showEmail($id);
											$showphone = $data->showPhone($id);
											$session->setLoginStatus('MO_2_FACTOR_CHALLENGE_AUTHENTICATION');
											$mo2f_rba_status = $data->mo2f_collect_attributes($data->getConfig('email',$id),stripslashes($attributes),$id,$customerKey,$apiKey,$appSecret,'ADMIN'); //RBA - Attributes
											if($mo2f_rba_status['status'] == 'SUCCESS' && $mo2f_rba_status['decision_flag']){
												$this->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password'));
											}else{
											$session->setMo2fRba($mo2f_rba_status);
											$content = $helper->mo2f_get_userinfo($data->getConfig('email',$id),$customerKey,$apiKey);
											$response = json_decode($content,true);
											if(json_last_error() == JSON_ERROR_NONE){
												$session->setLoginUsername($request->getPost('miniorange-username'));
												$session->setLoginPassword($request->getPost('miniorange-password'));
												$session->setshowEmail($showemail);
												$session->setshowPhone($showphone);
												$this->processAuthtType($response['authType'],$session,$id,$apiKey,$customerKey);
											}else{ $session->addError("Could not process your request. Please Contact the Admin."); }
										  }
										}else{ $this->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password')); }
									  }else{ $this->startInlineRegistration($request,$session,$id);  }
									}else{ $this->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password')); }
									}else{ $session->addError("Invalid Credentials. Please Enter Correct Username and Password."); }
								}else{ $session->addError("Invalid Credentials. Please Enter Correct Username and Password."); }
							}else{ $session->addError("Invalid Username"); }
						}else{ $session->unsLoginStatus(); }
				}else if($request->getPost('enable_soft_token')){
						Mage::getSingleton('adminhtml/session')->getMessages(true);
						if(!$session->getshowsofttoken()){
							$session->unsLoginStatus();
							$this->unsetSessionVariables($session);
							$session->setshowsofttoken(1);
							$session->setShowLoginScreen(true);
						}
				}else if($request->getPost('goback_to_login')){
							$session->unsLoginStatus();
							$session->unsshowEmail();
							$session->unsshowPhone();
							$session->unsShowGAScreen();
							$session->unsShowInlineValidate();
							$session->unsInlineEmail();
							$this->unsetSessionVariables($session);
							$session->unsminiError();
							if($session->getpushnotification() || $session->getoutofband()){
								$session->getMessages(true);
								$session->addError('Authentication Failed.');
							}
				}else if($request->getPost('softoken_entered')){
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					if( $request->getPost('softtoken')!=null){
						$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 						
						$id = $user->getUserId();
						$email = $data->getConfig('email',$id);
						$customerKey = $data->getConfig('customerKey',$id);
						$apiKey = $data->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token('SOFT TOKEN',$email, null, $request->getPost('softtoken'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcmp($response['status'], 'FAILED') != 0){
							$this->checkRBA($session);
						}else{	
							$session->unsshowsofttoken();
							$session->unsShowLoginScreen();
							$session->addError("Invalid Soft Token");
						}
					}else{
						$session->setshowsofttoken(1);
						$session->setShowLoginScreen(true);
						$session->unsminiError();
						$session->setminiError("Enter a 6 digit Soft Token");
					}
				}else if($request->getPost('enable_forgot_phone')){
						Mage::getSingleton('adminhtml/session')->getMessages(true);
						if(!$session->getshowforgotphone()){
							$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 						
							$id = $user->getUserId();
							$email = $data->getConfig('email',$id);
							$customerKey = $data->getConfig('customerKey',$id);
							$apiKey = $data->getConfig('apiKey',$id);
							$response = json_decode($helper->send_otp_token($email,'EMAIL',$customerKey,$apiKey), true);
							if(strcmp($response['status'], 'SUCCESS') == 0){
								$session->unsLoginStatus();
								$this->unsetSessionVariables($session);
								$session->setOTPtxtId($response['txId']);
								$session->setshowforgotphone(1);
								$session->setShowLoginScreen(true);
								$session->setLoginStatus('MO_2_FACTOR_CHALLENGE_AUTHENTICATION');
							}else{ $session->addError("An error occurred while sending the OTP."); }
						}
				}else if($request->getPost('forgotPhoneOtp_entered')){
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 
					$id = $user->getUserId();
					if( $request->getPost('forgotPhoneOtp')!=null){
						$email = $data->getConfig('email',$id);
						$customerKey = $data->getConfig('customerKey',$id);
						$apiKey = $data->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token('EMAIL',$email, $session->getOTPtxtId(), $request->getPost('forgotPhoneOtp'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcmp($response['status'], 'FAILED') != 0){
							$this->checkRBA($session);
						}else{
							$session->unsshowforgotphone();
							$session->unsShowLoginScreen();
							$session->addError("Invalid OTP Token.");
						}
					}else{
							$email = $data->showEmail($id);
							$session->unsminiError();
							$session->setminiError('Cannot Submit. Please Enter the otp sent to '.$email.'.');
						}
				}else if($request->getPost('smsotp_entered')){
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 
					$id = $user->getUserId();
					if( $request->getPost('smsotp')!=null && $request->getPost('gatoken')==null){
						$email = $data->getConfig('email',$id);
						$customerKey = $data->getConfig('customerKey',$id);
						$apiKey = $data->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token(null,null, $session->getLogintxId(), $request->getPost('smsotp'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcmp($response['status'], 'FAILED') != 0){
							$this->checkRBA($session);
						}else{
							$this->unsetSessionVariables($session);
							$session->addError("Invalid OTP Token.");
						}
					}else if($request->getPost('gatoken')!=null){
						$email = $data->getConfig('email',$id);
						$customerKey = $data->getConfig('customerKey',$id);
						$apiKey = $data->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token('GOOGLE AUTHENTICATOR',$email, null, $request->getPost('gatoken'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcmp($response['status'], 'FAILED') != 0){
							$this->checkRBA($session);
						}else{
							$session->unsShowGAScreen();
							$session->unsShowLoginScreen();
							$session->addError("Invalid Auth Token.");
						}
					}else{
						$session->setshowotpscreen(1);
						$session->setShowLoginScreen(true);
						$phone = $session->getshowPhone();
						$session->unsminiError();
						if(!$session->getphoneverification())
							$session->setminiError('Cannot Submit. Please Enter the otp sent to '.$phone.'.');
						else
							$session->setminiError('Cannot Submit. Please Enter the otp called to '.$phone.'.');
					}
				}else if($request->getPost('denied_transaction')){
					if($session->getpushnotification() || $session->getoutofband()){
						$session->getMessages(true);
						$this->unsetSessionVariables($session);
						$session->unsminiError();
						$session->addError('You have DENIED the transaction.');
					}
				}else if($request->getPost('mo2f_trust_device_confirm')){
					$apiKey = $data->getConfig('apiKey',$id);
					$customerKey = $data->getConfig('customerKey',$id);
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					$email = $data->getConfig('email',$id);
					$this->mo2f_register_profile($email,'true',$session->getMo2fRba(),$customerKey, $apiKey);
					$session->unsMo2fRba();
					$this->login($session->getLoginUsername(),$session->getLoginPassword());
				}else if($request->getPost('mo2f_trust_device_cancel')){
					$session->unsMo2fRba();
					$this->login($session->getLoginUsername(),$session->getLoginPassword());
				}else if($request->getPost('setup-email') && $session->getLoginUsername()){
					if(!$session->getInlineEmail()){
						$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
						$this->sendValidationOTP($request->getPost('setup-email'),$session,$id);
					}
				}else if($request->getPost('setup-otp') && $session->getLoginUsername()){
					if(!$session->getShowInlineTwoFactor()){
						if(strcmp($request->getPost('submit'), 'Validate OTP')==0){
							$content = json_decode($helper->validate_otp_token( 'EMAIL', null, $session->getInlineTxtId() , $request->getPost('setup-otp') ,  $data->getConfig('customerKey',$id), $data->getConfig('apiKey',$id)),true);
							if(strcmp($content['status'], 'SUCCESS') == 0){
								$this->checkEndUser($session->getInlineEmail(),$session);
							}else{ $session->setminiError('Invalid OTP. Please enter the correct OTP'); }
						}else{ 
							$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
							$this->sendValidationOTP($session->getInlineEmail(),$session,$id);
						}
					}
				}else if($request->getPost('twofactor') && $session->getLoginUsername()){
					if(strcmp($session->getInlineValidateStatus(),'SETUP_AUTH')!=0 && $session->getLoginUsername() ){
						$apiKey = $data->getConfig('apiKey',$id);
						$customerKey = $data->getConfig('customerKey',$id);
						$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
						$this->processTwoFactor($request->getPost('twofactor'),$session);
					}
				}else if($request->getPost('configure_mobile')  && $session->getLoginUsername()){
					if(!$session->getShowInlineQrCode()){
						if(strcmp($request->getPost('configure_mobile'),'Go Back')==0){
							$session->unsShowConfigureMobile();
							$session->setInlineValidateStatus('SETUP_TWO_FACTOR');
						}else {
							$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
							if(strcmp($request->getPost('configure_mobile'), 'Refresh QRCode')==0 || strcmp($request->getPost('configure_mobile'), 'Configure your phone')==0){
								$content = $helper->register_mobile($data->getConfig('email',$id),$id);
								$response = json_decode($content, true);
								if(json_last_error() == JSON_ERROR_NONE) {
									$session->setLoginQRCode($response['qrCode']);
									$session->setLogintxtId($response['txId']);
									$session->setShowInlineQrCode(1);
								}else{ $session->setminiError('An error occured while contacting the server. Please try again.'); }
							}
						}
					}
				}else if($request->getPost('inlinetwofactor_phone') && $session->getLoginUsername()){
					if(!$session->getInlinePhone()){
						$phone=$request->getPost('inlinetwofactor_phone');
						$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
						$content = $helper->send_otp_token($data->getConfig('email',$id),$session->getAuthType(),$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id),$phone);
						$response = json_decode($content, true);
						if(json_last_error() == JSON_ERROR_NONE){
							$session->setLogintxId($response['txId']);
							$session->setInlinePhone($phone);
						}else{ $session->setminiError('An error occured while sending the OTP. Please try again.'); }
					}
				}else if($request->getPost('inlinephone_verify') && $session->getLoginUsername()){
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					if(strcmp($request->getPost('submit'),'Resend OTP')!=0){
						$content = $helper->validate_otp_token(null,null,$session->getLogintxId(),$request->getPost('inlinephone_otp'),$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id));
						$response = json_decode($content, true);
						if(strcmp($response['status'], 'FAILED') != 0)
							$this->saveTwoFactorType($session->getAuthType(),$session->getInlinePhone(),$session);
						else
							$session->setminiError('Invalid OTP! Please try again.');
					}else if(!$session->getresendOTP()){
						$phone=$session->getInlinePhone();
						$content = $helper->send_otp_token($data->getConfig('email',$id),$session->getAuthType(),$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id),$phone);
						$response = json_decode($content, true);
						if(json_last_error() == JSON_ERROR_NONE){
							$session->setLogintxId($response['txId']);
							$session->setInlinePhone($phone);
							$session->setresendOTP(1);
						}else{ $session->setminiError('An error occured while sending the OTP. Please try again.'); }
					}else{ $session->unsresendOTP(); }
				}else if($request->getPost('mo2f_app_type_radio')&& $session->getLoginUsername()){
					if(strcmp($session->getGAPhone(),$request->getPost('mo2f_app_type_radio'))!=0){
						$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
						$content = json_decode($helper->mo2f_google_auth_service($data->getConfig('email',$id),$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id)),true);
						if(strcmp($content['status'], 'SUCCESS') == 0){
							$session->setGAPhone($request->getPost('mo2f_app_type_radio'));
							$session->setGAQRCode($content['qrCodeData']);
							$session->setGASecret($content['secret']);
						}else{ $session->setminiError('An error occured while contacting the server. Please try again.'); }
					}
				}else if($request->getPost('mobile_registration_success')&& $session->getLoginUsername()){
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					$this->saveTwoFactorType($session->getAuthType(),NULL,$session);
				}else if($request->getPost('mobile_registration_failed')&& $session->getLoginUsername()){
					$this->unsetSessionVariables($session);
					$session->addError('Registration Failed');
				}else if($request->getPost('google_token')&& $session->getLoginUsername()){
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					$content = json_decode($helper->mo2f_validate_google_auth($data->getConfig('email',$id),$request->getPost('google_token'),$session->getGASecret(),$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id)),true);
					if(strcmp($content['status'], 'SUCCESS') == 0 && strcmp($content['message'], 'The OTP you have entered is incorrect.') != 0){
						$this->saveTwoFactorType($session->getAuthType(),null, $session);
					}else{  $session->setminiError('Invalid Token. Please Try Again.'); }
				}else if($request->getPost('mo2f_answer_1') && $session->getLoginUsername()){
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					$apiKey = $data->getConfig('apiKey',$id);
					$customerKey = $data->getConfig('customerKey',$id);
					$otptoken = array();
					$otptoken[0] = $session->getKBAQuestion1();
					$otptoken[1] = trim($request->getPost('mo2f_answer_1'));
					$otptoken[2] = $session->getKBAQuestion2();
					$otptoken[3] = trim($request->getPost('mo2f_answer_2'));
					$content = $helper->validate_otp_token('KBA',null, $session->getLogintxId(),$otptoken, $customerKey, $apiKey);
					$response = json_decode($content, true);
					if(strcmp($response['status'], 'FAILED') != 0){
						$this->checkRBA($session);
					}else{
						$this->unsetSessionVariables($session);
						$session->addError("Invalid OTP Token.");
					}
				}else if($request->getPost('mo2f_kba_ans3') && $session->getLoginUsername()){
					$id = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword())->getUserId();
					$kba_q1 = $request->getPost('mo2f_kbaquestion_1');
					$kba_a1 = trim( $request->getPost('mo2f_kba_ans1') );
					$kba_q2 = $request->getPost('mo2f_kbaquestion_2');
					$kba_a2 = trim($request->getPost('mo2f_kba_ans2'));
					$kba_q3 = trim($request->getPost('mo2f_kbaquestion_3'));
					$kba_a3 = trim($request->getPost('mo2f_kba_ans3'));
					$kba_reg_reponse = json_decode($helper->register_kba_details($data->getConfig('email',$id),$kba_q1,$kba_a1,$kba_q2,$kba_a2,$kba_q3,$kba_a3,$data->getConfig('customerKey'),$data->getConfig('apiKey')),true);
					if($kba_reg_reponse['status'] == 'SUCCESS'){
						$this->saveTwoFactorType($session->getAuthType(),null,$session);
					}else{ $session->setminiError('An error occured. Please try again.'); }
				}
			}
	}
	
	public function customerLogin(Varien_Event_Observer $observer){
		$request = Mage::app()->getRequest();
		$session = Mage::getSingleton('customer/session');
		$customer = $session->getCustomer();
		$id = $customer->getId();
		$groupId = $customer->getGroupId();
		if(Mage::helper('MiniOrange_2factor')->getConfig('group_enabled',$groupId)==1){
			if (Mage::helper('MiniOrange_2factor')->getConfig('isCustomerEnabled') && !Mage::helper('MiniOrange_2factor')->getConfig('miniorange_email',$id)) {
				Mage::helper('MiniOrange_2factor')->displayMessage('Admin has Enabled Two Factor Authentication for your account. Please configure your account below.','NOTICE');
				$session->setBeforeAuthUrl(Mage::getUrl('twofactorauth/Index/configureTwoFactorPage'));			
			}
		}
	}
	
	public function customerAuthenticateAfter(Varien_Event_Observer $observer){
		if (Mage::helper('MiniOrange_2factor')->getConfig('isCustomerEnabled')) {
			$session = Mage::getSingleton('customer/session');
			$customer = $observer->getEvent()->getCustomer();
			$session->setmoCustomer($customer);
			$id=$customer->getId();
			$session->setmoId($id);
			$redirectUrl = Mage::getModel('core/url')->getUrl('twofactorauth/Index/validationPage'); 
			$session->setOriginalAfterAuthUrl($session->getAfterAuthUrl());
            $session->setBeforeAuthUrl($redirectUrl);
        }
		else{
			return $this;
		}		
    }
    
    public function customerInlineRegistration(Varien_Event_Observer $observer){
    	$session = Mage::getSingleton('customer/session');
    	$customer = $observer->getEvent()->getCustomer();
    	$session->setmoCustomer($customer);
    	$id=$customer->getId();
    	$session->setmoId($id);
    	if(strcmp(Mage::helper('MiniOrange_2factor')->getConfig('customer_reg_status',$id),'SETUP_TWO_FACTOR')==0)
    		$session->setShowInlineTwoFactor(1);
    	$redirectUrl = Mage::getModel('core/url')->getUrl('twofactorauth/InlineRegistration/index');
    	$session->setOriginalAfterAuthUrl($session->getAfterAuthUrl());
    	$session->setBeforeAuthUrl($redirectUrl);
    }
	
	public function checkStatus(){
		$session = Mage::getSingleton('customer/session');
		$this->unsetSessionVariables($session);
		$session->unsEnteredEmail(); $session->unsEnteredPass(); $session->unsshowEmail(); $session->unsshowPhone(); 
		$session->unsshowforgotphone();$session->unsMo2fRba();$session->unsOTPtxtId(); $session->unsShowInlineValidate();
		$session->unsInlineEmail(); 
		
		if(strcmp($session->getLoginStatus(),"LOGIN_SUCCESS")==0){
			$session->setCustomerAsLoggedIn($session->getmoCustomer());
			$session->unsmoCustomer();
			$session->unsmoId();
			if (!$redirectUrl = $session->getOriginalAfterAuthUrl()) {
				$redirectUrl = Mage::getModel('core/url')->getUrl('customer/account');
			}
			if(strpos($session->getOriginalAfterAuthUrl(),'validationPage') !== false || strpos($session->getOriginalAfterAuthUrl(),'InlineRegistration') !== false ){
				$redirectUrl = Mage::getModel('core/url')->getUrl('customer/account');
			}
			Mage::app()->getResponse()->setRedirect($redirectUrl);
		}else if(strcmp($session->getLoginStatus(),"LOGIN_FAILED")==0){
			$session->unsmoCustomer();
			$session->unsmoId();
			Mage::helper('MiniOrange_2factor')->displayMessage('Validation Failed. Please Try Again.',"ERROR");
			Mage::app()->getResponse()->setRedirect(Mage::getUrl("customer/account/login"));	
		}else if(strcmp($session->getLoginStatus(),"LOGIN_DENIED")==0){
			$session->unsmoCustomer();
			$session->unsmoId();
			Mage::helper('MiniOrange_2factor')->displayMessage('You have DENIED the transaction',"ERROR");
			Mage::app()->getResponse()->setRedirect(Mage::getUrl("customer/account/login"));
		}else{
			$session->unsmoCustomer();
			$session->unsmoId();
			Mage::app()->getResponse()->setRedirect(Mage::getUrl("customer/account/login"));
		}
	}
	
	
	public function checkUserLoginBackend(Varien_Event_Observer $observer){
		//we will validate captcha in checkCaptcha function.
	}
	
	
	public function checkCaptcha($observer){
		$session = Mage::getSingleton('adminhtml/session');
		$formId = 'backend_login';
		$session->setCaptchaStatus('CHECK_CAPTCHA');
		$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
		$loginParams = Mage::app()->getRequest()->getPost('login', array());
		$login = array_key_exists('miniorange-username', $loginParams) ? $loginParams['miniorange-username'] : null;
		if ($captchaModel->isRequired($login)) {
			$controller = $observer->getControllerAction();
			$word = $this->_getCaptchaString($controller->getRequest(), $formId);
			if (!$captchaModel->isCorrect($word)) {
				 $captchaModel->logAttempt($login);
				 	$session->addError('Incorrect CAPTCHA.');	
				return 0;
			}
		}
		$captchaModel->logAttempt($login);
		return 1;
    }
	
	 /**
     * Get Captcha String
     *
     * @param Varien_Object $request
     * @param string $formId
     * @return string
     */
    private function _getCaptchaString($request, $formId){
        $captchaParams = $request->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
        return $captchaParams[$formId];
    }
	
    //Unset all session variables
	private function unsetSessionVariables($session){
		$session->unsLoginWaitId(); $session->unspushnotification(); $session->unsLoginQRCode(); $session->unsLogintxtId();
		$session->unsoutofband(); $session->unsCaptchaStatus();	$session->unsshowsofttoken(); $session->unsShowGAScreen();	
		$session->unsshowotpscreen();  $session->unsShowLoginScreen(); $session->unsLogintxId(); $session->unsshowRBAScreen();
		$session->unsInlineTxtId(); $session->unsInlineValidateStatus(); $session->unsShowInlineTwoFactor(); $session->unsShowGoogleAuthSetup();	
		$session->unsShowPhoneValidation();$session->unsShowConfigureMobile(); $session->unsShowInlineQrCode(); $session->unsAuthType(); 
		$session->unsInlinePhone(); $session->unsresendOTP(); $session->unsGAPhone(); $session->unsGAQRCode(); $session->unsGASecret();
	    $session->unsphoneverification(); $session->unsminiError();  $session->unsBeforeAuthUrl(); $session->unsKBAQuestion1();$session->unsKBAQuestion2();
	    $session->unsShowKBAScreen(); $session->unsShowKBASetup(); 
	}
	
	//RBA
	private function mo2f_register_profile($email,$deviceKey,$mo2f_rba_status,$customerKey,$apiKey){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if(isset($deviceKey) && $deviceKey == 'true'){
			if($mo2f_rba_status['status'] == 'WAIT_FOR_INPUT' && $mo2f_rba_status['decision_flag']){
				$rba_response = json_decode($helper->mo2f_register_rba_profile($email,$mo2f_rba_status['sessionUuid'],$customerKey,$apiKey),true); //register profile
				return true;
			}else{ return false; }
		}
		return false;
	}
	
	private function checkRBA($session){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$data = Mage::helper('MiniOrange_2factor');
		$session->unsLoginStatus();
		$session->unsshowforgotphone();
		$session->unsOTPtxtId();
		$session->unsphoneverification();
		$this->unsetSessionVariables($session);
		if($data->getConfig('admin_rmd_enable',null)==1){
			if($data->getConfig('appSecret',null)==NULL){
				$appSecret = json_decode($helper->mo2f_get_app_secret($data->getConfig('customerKey',null),$data->getConfig('apiKey',null)),true); //register profile
				$storeConfig = new Mage_Core_Model_Config();
				$storeConfig ->saveConfig('miniOrange/twofactor/appSecret',$appSecret['appSecret'], 'default', 0);
			}
			$session->setshowRBAScreen(1);
			$session->unsShowLoginScreen();
		}else{
			$session->unsMo2fRba();
			$this->login($session->getLoginUsername(),$session->getLoginPassword());
		}
	}
	
	//Inline Registration
	private function startInlineRegistration($request,$session,$id){
		$data = Mage::helper('MiniOrange_2factor');
		$session->setLoginUsername($request->getPost('miniorange-username'));
		$session->setLoginPassword($request->getPost('miniorange-password'));
		if(strcmp($data->getConfig('admin_reg_status',$id),'SETUP_TWO_FACTOR')==0)
			$session->setShowInlineTwoFactor(1);
		$request->setControllerName('inlineRegistration')->setActionName('index')->setDispatched(false);
	}
	
	private function processAuthtType($authType,$session,$id,$apiKey,$customerKey){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$data = Mage::helper('MiniOrange_2factor');
		switch($authType){
			case "MOBILE AUTHENTICATION":
				if(!$session->getLogintxtId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'MOBILE AUTHENTICATION', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLoginQRCode($status['qrCode']);
						$session->setLogintxtId($status['txId']);
						$session->setShowLoginScreen(true);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			case "SOFT TOKEN":
				$session->setshowsofttoken(1);
				$session->setShowLoginScreen(true);
				break;
		
			case "SMS":
				if(!$session->getLogintxId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'SMS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($status['txId']);
						$session->setshowotpscreen(1);
						$session->setShowLoginScreen(true);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			case "PHONE VERIFICATION":
				if(!$session->getLogintxId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'PHONE VERIFICATION', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($status['txId']);
						$session->setphoneverification(1);
						$session->setShowLoginScreen(true);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			case "PUSH NOTIFICATIONS":
				if(!$session->getLoginWaitId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'PUSH NOTIFICATIONS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLoginWaitId($status['txId']);
						$session->setpushnotification(1);
						$session->setShowLoginScreen(true);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			case "OUT OF BAND EMAIL":
				if(!$session->getLoginWaitId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'OUT OF BAND EMAIL', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLoginWaitId($status['txId']);
						$session->setoutofband(1);
						$session->setShowEmail($data->showEmail($id));
						$session->setShowLoginScreen(true);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			case "GOOGLE AUTHENTICATOR":
				$session->setShowGAScreen(1);
				$session->setShowLoginScreen(true);
				break;
			
			case "KBA":
				if(!$session->getLogintxId()){
					$sendotp = $helper->send_otp_token($data->getConfig('email',$id),'KBA', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($status['txId']);
						$session->setShowKBAScreen(1);
						$session->setShowLoginScreen(true);
						$questions = array();
						$questions[0] = $status['questions'][0]['question'];
						$questions[1] = $status['questions'][1]['question'];
						$session->setKBAQuestion1($questions[0]);
						$session->setKBAQuestion2($questions[1]);
					}
					else{ $session->addError("Invalid request"); }
				}
				break;
					
			default:
				$session->unsLoginStatus();
				$session->unsminiError();
				$session->unsshowEmail();
				$session->unsshowPhone();
				$this->unsetSessionVariables($session);
				$session->getMessages(true);
				$session->addError("Invalid request");
				break;
		}
	}
	
	private function checkEndUser($email,$session){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$data = Mage::helper('MiniOrange_2factor');
		$user = Mage::getModel('admin/user');
		$admin = $user->login($session->getLoginUsername(), $session->getLoginPassword());
		$id = $admin->getUserId();
		$check_user = json_decode($helper->mo_check_user_already_exist($email,$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id)),true);
		if(json_last_error() == JSON_ERROR_NONE){
			if(strcmp($check_user['status'], 'USER_FOUND') == 0){
				$this->saveConfig('miniorange_2factor_email',$email,$id);
				$this->saveConfig('miniorange_2factor_phone',$phone,$id);
				//$session->setInlineValidateStatus('SETUP_TWO_FACTOR');
				$this->saveConfig('inline_reg_status','SETUP_TWO_FACTOR',$id);
				$session->unsShowInlineValidate();
				$session->unsInlineTxtId();
				$session->setShowInlineTwoFactor(1);
			}else if(strcmp($check_user['status'], 'USER_NOT_FOUND') == 0){
					$content = json_decode($helper->mo_create_user($email,$data->getConfig('customerKey'),$data->getConfig('apiKey'),$admin), true);
						if(strcmp($content['status'], 'SUCCESS') == 0) {
							$this->saveConfig('miniorange_2factor_email',$email,$id);
							$this->saveConfig('miniorange_2factor_phone',$phone,$id);
							//$session->setInlineValidateStatus('SETUP_TWO_FACTOR');
							$this->saveConfig('inline_reg_status','SETUP_TWO_FACTOR',$id);
							$session->unsShowInlineValidate();
							$session->unsInlineTxtId();
							$session->setShowInlineTwoFactor(1);
						}else{
							$session->setminiError('There was an Error while creating End User!');
						}
				}else{ $session->setminiError('The User already exists under another Admin.'); }
		}else{ $session->setminiError('There was an unknown error! Contact Admin.'); }
	}
	
	private function processTwoFactor($authType,$session){
		$session->setInlineValidateStatus('SETUP_AUTH');
		$session->setAuthType($authType);
		if(strcmp($authType, 'MOBILE AUTHENTICATION') == 0 || strcmp($authType, 'SOFT TOKEN') == 0
				|| strcmp($authType, 'PUSH NOTIFICATIONS') == 0 ){
			$session->setShowConfigureMobile(1);
		}else if(strcmp($authType, 'SMS') == 0 || strcmp($authType, 'PHONE VERIFICATION') == 0){
			$session->setShowPhoneValidation(1);
		}else if(strcmp($authType, 'GOOGLE AUTHENTICATOR') == 0){
			$session->setShowGoogleAuthSetup(1);
		}else if(strcmp($authType, 'OUT OF BAND EMAIL') == 0){
			$this->saveTwoFactorType($authType,null,$session);
		}else if(strcmp($authType, 'KBA') == 0){
			$session->setShowKBASetup(1);
		}else{ $session->setminiError('Invalid Second Factor. Please select a valid Second Factor from the list.'); }
	}
	
	private function sendValidationOTP($email,$session,$id){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$data = Mage::helper('MiniOrange_2factor');
			$content = json_decode($helper->send_otp_token($email,'EMAIL',$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id)), true);
			if(strcmp($content['status'], 'SUCCESS') == 0){
				$session->setInlineValidateStatus('STARTED');
				$session->setInlineTxtId($content['txId']);
				$session->setInlineEmail($email);
				$session->setShowInlineValidate(1);
			}else{  $session->setminiError('An error occurred while sending OTP to '.$email.'.');  }
	}
	
	private function saveConfig($url,$value,$id){
		$data = array($url=>$value);
		$model = Mage::getModel('admin/user')->load($id)->addData($data);
		try {
			$model->setId($id)->save();
		} catch (Exception $e){
			Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	private function saveTwoFactorType($authType,$phone,$session){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$data = Mage::helper('MiniOrange_2factor');
		$admin = Mage::getModel('admin/user')->login($session->getLoginUsername(), $session->getLoginPassword());
		$id = $admin->getUserId();
		$content = $helper->mo2f_update_userinfo($data->getConfig('email',$id),$authType,$phone,$data->getConfig('customerKey',$id),$data->getConfig('apiKey',$id));
		$response = json_decode($content, true);
		if(strcmp($response['status'], 'SUCCESS') == 0) {
			$this->saveConfig('miniorange_2factor_type',$authType,$id);
			if(strcmp($authType, 'MOBILE AUTHENTICATION')==0 ||strcmp($authType, 'PUSH NOTIFICATIONS')==0 ||strcmp($authType, 'SOFT TOKEN')==0 ){
				$this->saveConfig('miniorange_2factor_configured',1,$id);
				$this->saveConfig('miniorange_2factor_downloaded_app',1, $id);
			}else if(strcmp($authType, 'SMS') == 0 || strcmp($authType, 'PHONE VERIFICATION') == 0){
				$this->saveConfig('miniorange_2factor_phone',$session->getInlinePhone(),$id);
			}else if(strcmp($authType, 'GOOGLE AUTHENTICATOR') == 0){
				$this->saveConfig('admin_ga_configured',1,$id);
			}else if(strcmp($authType, 'KBA') == 0){
				$this->saveConfig('kba_Configured',1,$id);
			}
			$this->saveConfig('inline_reg_status',NULL,$id);
			$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
			$this->unsetSessionVariables($session);
			$session->addSuccess($authType.' has been set as your Second Factor.'); 
			$this->login($session->getLoginUsername(),$session->getLoginPassword());
		}
		else{
			$session->setminiError('There was an ERROR while setting your Authentication Type.');
		}
	}
	
	private function login($username,$password){
		$user = Mage::getModel('admin/user');
		$user->login($username, $password);
		if ($user->getId()) {
			if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
				Mage::getSingleton('adminhtml/url')->renewSecretUrls();
			}
		}
		$session = Mage::getSingleton('admin/session');
		$session->setIsFirstVisit(true);
		$session->setUser($user);
		$session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
		$session->unsLoginUsername();	$session->unsLoginPassword();
		$session->unsshowEmail();		$session->unsshowPhone();
		$session->unsLoginStatus();		$session->unsminiError();
		$this->unsetSessionVariables($session);
		Mage::dispatchEvent('admin_session_user_login_success',array('user'=>$user));
	}
	
}