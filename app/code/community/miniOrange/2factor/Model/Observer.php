<?php
class MiniOrange_2factor_Model_Observer
{
	
    private $defaultCustomerKey = "16352";												
	private $defaultApiKey = "AJG97LGpOVVwFUuuPSij5IH6Kvlu6qEj";
	
	public function controllerActionPredispatch(Varien_Event_Observer $observer){
					
		$request = Mage::app()->getRequest();
        $session = Mage::getSingleton('adminhtml/session');
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');	
			if (  $request->getRequestedControllerName() == 'index' && $request->getRequestedActionName() == 'login'){
					$session->unsLoginStatus();
					$session->unsWelcomeMessage();	
					$session->unsminiError();
					$session->unsshowsofttoken();
					$session->unsPhoneOpen();
						$request->setControllerName('miniOrange')
							->setActionName('login')
							->setDispatched(false);		
			}
			else{
				if($request->getPost('miniorange_mobile_validation_Username')){	
					//$user = Mage::getModel('admin/user')->loadByUsername(<username>); 
					$this->login($request->getPost('miniorange_mobile_validation_Username'), $request->getPost('miniorange_mobile_validation_Password'));
				}
				else if($request->getPost('miniorange-username')){
					$user = Mage::getModel('admin/user');
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					if($session->getLoginStatus()!='MO_2_FACTOR_CHALLENGE_AUTHENTICATION'){
						if($helper->adminExists($request->getPost('miniorange-username'))){
								if($user->authenticate($request->getPost('miniorange-username'),$request->getPost('miniorange-password'))){
									$useragent = $_SERVER['HTTP_USER_AGENT'];
									if(strpos($useragent,'Mobi') !== false){
										$session->unsLoginQRCode();
										$session->unsLogintxtId();
										$session->setPhoneOpen(1);
										$session->unsWelcomeMessage();	
										$session->setLoginStatus('MO_2_FACTOR_CHALLENGE_AUTHENTICATION');
									}
									else{
										$admin = $user->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password'));
										$id = $admin->getUserId();
										if($helper->getConfig('isEnabled',$id)==1){
											$apiKey = $helper->getConfig('apiKey',$id);
											$customerKey = $helper->getConfig('customerKey',$id);
											$showemail = $helper->showEmail($id);
											$content = $helper->send_otp_token($helper->getConfig('email',$id),'MOBILE AUTHENTICATION', $customerKey, $apiKey);
											$response = json_decode($content, true);
											if(json_last_error() == JSON_ERROR_NONE){
												$session->setLoginUsername($request->getPost('miniorange-username'));
												$session->setLoginPassword($request->getPost('miniorange-password'));
												$session->setshowEmail($showemail);
												$session->setLoginQRCode($response['qrCode']);
												$session->setLogintxtId($response['txId']);
												$session->setLoginStatus('MO_2_FACTOR_CHALLENGE_AUTHENTICATION');
												$session->setWelcomeMessage(true);
												$session->unsPhoneOpen();
											}
											else{ $session->addError("Invalid request"); }
										}
										else{ $this->login($request->getPost('miniorange-username'), $request->getPost('miniorange-password')); }
									}
								}
								else{ $session->addError("Invalid Credentials. Please Enter Correct Username and Password."); }
						}
						else{ $session->addError("Invalid Username"); }
					}
				}
				else if($request->getPost('softoken_entered')){
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					if( $request->getPost('softtoken')!=null){
						$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 						
						$id = $user->getUserId();
						$email = $helper->getConfig('email',$id);
						$customerKey = $helper->getConfig('customerKey',$id);
						$apiKey = $helper->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token('SOFT TOKEN',$email, null, $request->getPost('softtoken'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$this->login($session->getLoginUsername(),$session->getLoginPassword());
						}
						else{	
								$session->addError("Invalid Soft Token");
						}
					}
					else{
						$session->setshowsofttoken(1);
						$session->setWelcomeMessage(true);
						$session->unsminiError();
						$session->setminiError("Enter a 6 digit Soft Token");
					}
				}
				else if($request->getPost('disable_forgot_phone')){
						if($session->getshowforgotphone()){
							$session->unsshowforgotphone();
							$session->unsLoginUsername();
							$session->unsLoginPassword();
							$session->unsshowEmail();
							$session->unsWelcomeMessage();	
						}
				}
				else if($request->getPost('enable_forgot_phone')){
						Mage::getSingleton('adminhtml/session')->getMessages(true);
						$session->unsWelcomeMessage();	
						if(!$session->getshowforgotphone()){
							$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 						
							$id = $user->getUserId();
							$email = $helper->getConfig('email',$id);
							$customerKey = $helper->getConfig('customerKey',$id);
							$apiKey = $helper->getConfig('apiKey',$id);
							$response = json_decode($helper->send_otp_token($email,'EMAIL',$customerKey,$apiKey), true);
							if(strcasecmp($response['status'], 'SUCCESS') == 0){
								$session->setOTPtxtId($response['txId']);
								$session->unsLoginQRCode();
								$session->unsLoginStatus();
								$session->unsLogintxtId();
								$session->setshowforgotphone(1);
							}
							else{
								$session->addError("An error occurred while sending the OTP.");
							}
						}
				}
				else if($request->getPost('forgotPhoneOtp_entered')){
					Mage::getSingleton('adminhtml/session')->getMessages(true);
					$user = Mage::getModel('admin/user')->login($session->getLoginUsername(),$session->getLoginPassword()); 
					$id = $user->getUserId();
					if( $request->getPost('forgotPhoneOtp')!=null){
						$email = $helper->getConfig('email',$id);
						$customerKey = $helper->getConfig('customerKey',$id);
						$apiKey = $helper->getConfig('apiKey',$id);
						$content = $helper->validate_otp_token('EMAIL',$email, $session->getOTPtxtId(), $request->getPost('forgotPhoneOtp'), $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$session->unsshowforgotphone();
							$this->login($session->getLoginUsername(),$session->getLoginPassword());
						}
						else{
								$session->unsshowforgotphone();
								$session->addError("Invalid OTP Token.");
						}
					}
					else{
								$test = $helper->showEmail($id);
								$session->unsminiError();
								$session->setminiError('Cannot Submit. Please Enter the otp sent to '.$test.'.');
						}
				}
				else{
						$session->unsLoginQRCode();
						$session->unsLoginStatus();
						$session->unsLogintxtId();
						$session->unsWelcomeMessage();	
						$session->unsshowsofttoken();
						$session->unsminiError();
				}
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
		$session->unsLoginUsername();
		$session->unsLoginPassword();
		$session->unsshowEmail();
		$session->unsLoginQRCode();
		$session->unsLoginStatus();
		$session->unsLogintxtId();
		$session->unsWelcomeMessage();	
		$session->unsminiError();
		$session->unsshowsofttoken();
		Mage::dispatchEvent('admin_session_user_login_success',array('user'=>$user));
	}
	
}