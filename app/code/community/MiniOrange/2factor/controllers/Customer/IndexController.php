<?php
class MiniOrange_2factor_Customer_IndexController extends Mage_Core_Controller_Front_Action
{
	private $_helper1 = "MiniOrange_2factor";
	private $_helper2 = "MiniOrange_2factor/mo2fUtility";
	
	public function preDispatch(){
		$helper1 = $this->getHelper1();
		if(!$helper1->getConfig('isCustomerEnabled')) {
            $this->_forward('defaultNoRoute');
        }
        parent::preDispatch();
    }
	
	
    public function configureTwoFactorPageAction(){
    	$session = $this->getSession();
		if( !$session->isLoggedIn() ) {
            $session->authenticate($this);
            return;
        }
        $this->loadLayout();
        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('twofactorauth/Index');
        }
        $this->renderLayout();
    }
	
	public function validationPageAction(){	
		$session = $this->getSession();	
		$session->setLoginStatus('LOGIN_VALIDATION_STARTED');
		$id = $session->getmoId();
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		if($helper1->is_curl_installed()){
			if(!$session->getLoginQRCode() && !$session->getshowforgotphone() && !$session->getshowsofttoken() && !$session->getshowotpscreen()  && !$session->getshowRBAScreen()
						&& !$session->getphoneverification() && !$session->getShowGAScreen() && !$session->getoutofband() && !$session->getpushnotification()){
				//if ($helper1->getConfig('miniorange_mobileconfigured',$id)) { 
						$apiKey = $helper1->getConfig('apiKey',$id);
						$customerKey = $helper1->getConfig('customerKey',$id);
						$appSecret = $helper1->getConfig('appSecret',$id);
						$session->setshowEmail($helper1->showCustomerEmail($id));
						$session->setShowPhone($helper1->showCustomerPhone($id));
						$content = $helper2->mo2f_get_userinfo($helper1->getConfig('miniorange_email',$id),$customerKey,$apiKey);
						$response = json_decode($content,true);
						if(json_last_error() == JSON_ERROR_NONE){
							switch($response['authType']){
							case "MOBILE AUTHENTICATION":
								$content = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'MOBILE AUTHENTICATION', $customerKey, $apiKey);
								$response = json_decode($content, true);
								if(json_last_error() == JSON_ERROR_NONE){
									$session->setLoginQRCode($response['qrCode']);
									$session->setLogintxtId($response['txId']);
									$session->unsPhoneOpen();	
								}
								else{ $helper1->displayMessage("Invalid request","ERROR"); }
								break;

								case "SOFT TOKEN":
									$session->setshowsofttoken(1);
									break;
								
								case "SMS":
										$sendotp = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'SMS', $customerKey, $apiKey);
										$status = json_decode($sendotp, true);
										if(json_last_error() == JSON_ERROR_NONE){
											$session->setLogintxId($status['txId']);
											$session->setshowotpscreen(1);
										}
										else{ $helper1->displayMessage("Invalid request","ERROR"); }
									break;
										
								case "PHONE VERIFICATION":
										$sendotp = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'PHONE VERIFICATION', $customerKey, $apiKey);
										$status = json_decode($sendotp, true);
										if(json_last_error() == JSON_ERROR_NONE){
											$session->setLogintxId($status['txId']);
											$session->setphoneverification(1);
										}
										else{ $helper1->displayMessage("Invalid request","ERROR"); }
									break;
										
								case "PUSH NOTIFICATIONS":
										$sendotp = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'PUSH NOTIFICATIONS', $customerKey, $apiKey);
										$status = json_decode($sendotp, true);
										if(json_last_error() == JSON_ERROR_NONE){
											$session->setLoginWaitId($status['txId']);
											$session->setpushnotification(1);
										}
										else{ $helper1->displayMessage("Invalid request","ERROR"); }
									break;
										
								case "OUT OF BAND EMAIL":
									if(!$session->getLoginWaitId()){
										$sendotp = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'OUT OF BAND EMAIL', $customerKey, $apiKey);
										$status = json_decode($sendotp, true);
										if(json_last_error() == JSON_ERROR_NONE){
											$session->setLoginWaitId($status['txId']);
											$session->setoutofband(1);
										}
										else{ $helper1->displayMessage("Invalid request","ERROR"); }
									}
									break;
								
								case "GOOGLE AUTHENTICATOR":
									$session->setShowGAScreen(1);
									break;

								case "KBA":
									if(!$session->getLogintxId()){
										$sendotp = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),'KBA', $customerKey, $apiKey);
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
										else{ $helper1->displayMessage("Invalid request","ERROR"); }
									}
									break;
									
								default:
									$session->unsLoginStatus();
									$session->unsshowEmail();
									$session->unsshowPhone();
									$session->unsShowLoginScreen();
									$session->unsshowsofttoken();
									$session->unsshowotpscreen();
									$session->unsphoneverification();
									$session->unsminiError();
									$session->unspushnotification();
									$session->unsoutofband();
									$helper1->displayMessage("Invalid request","ERROR");
									break;
							}
							$this->loadLayout();
							$this->renderLayout();
							
						}else{ 
							$helper1->displayMessage("Could not process your request. Please Contact the Admin.","ERROR"); 
							$this->redirect('customer/account/login/');
						}	
			}
			else{
				$this->loadLayout(); 
				$this->renderLayout();
			}
		}
		else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
		}
	}
	
	public function validateUserAction(){	
		$this->checkRBA();
    }
	
	public function authenticationFailedAction(){
		$this->getSession()->setLoginStatus('LOGIN_FAILED');
		Mage::dispatchEvent('customer_login_status');
	}
	
	public function deniedTransactionAction(){
		$this->getSession()->setLoginStatus('LOGIN_DENIED');
		Mage::dispatchEvent('customer_login_status');
	}
	
	public function sofTokenEnteredAction(){
		$helper1 = Mage::helper('MiniOrange_2factor');
		$helper2 = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$params = $this->getRequest()->getParams();
		$session = Mage::getSingleton('customer/session');
		if($helper1->is_curl_installed()){
			if(strcmp($params['softtoken'],"")!=0){
				$id = $session->getmoId();
				$email = $helper1->getConfig('miniorange_email',$id);
				$customerKey = $helper1->getConfig('customerKey',$id);
				$apiKey = $helper1->getConfig('apiKey',$id);
				$content = $helper2->validate_otp_token('SOFT TOKEN',$email, null, $params['softtoken'], $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'SUCCESS') == 0){
					$this->checkRBA();
				}
				else{	
						$session->setshowsofttoken(1);
						$session->unsminiError();
						$session->setminiError("Enter a valid Soft Token");
						$this->redirect("twofactorauth/Index/validationPage");
				}
			}
			else{
				$session->setshowsofttoken(1);
				$session->unsminiError();
				$session->setminiError("Enter a 6 digit Soft Token");
				$this->redirect("twofactorauth/Index/validationPage");
			}	
		}
		else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function enableForgotPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			if(!$session->getshowforgotphone()){
				$id = $session->getmoId();
				$email = $helper1->getConfig('miniorange_email',$id);
				$customerKey = $helper1->getConfig('customerKey',$id);
				$apiKey = $helper1->getConfig('apiKey',$id);
				$response = json_decode($helper2->send_otp_token($email,'EMAIL',$customerKey,$apiKey), true);
				if(strcasecmp($response['status'], 'SUCCESS') == 0){
					$session->unsminiError();
					$session->setOTPtxtId($response['txId']);
					$session->unsLoginQRCode();
					$session->unsLoginWaitId();
					$session->unsLogintxtId();
					$session->unsshowsofttoken();
					$session->setshowforgotphone(1);
					$this->redirect("twofactorauth/Index/validationPage");
				}
				else{
					$session->unsminiError();
					$session->setminiError("An error occurred while sending the OTP.");
					$this->redirect("twofactorauth/Index/validationPage");
				}
			}
		}
		else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function enableSoftTokenAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$session->unsminiError();
			$session->unsOTPtxtId();
			$session->unsLoginQRCode();
			$session->unsLogintxtId();
			$session->unsLoginWaitId();
			$session->unsshowforgotphone();
			$session->setshowsofttoken(1);
			$this->redirect("twofactorauth/Index/validationPage");
		}
		else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function goBackLoginAction(){
		$session = $this->getSession();
		$session->unsOTPtxtId();		
		$session->setLoginStatus('LOGIN_DISABLED_PHONE');
		Mage::dispatchEvent('customer_login_status');
	}
	
	public function enteredForgetPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		$session = $this->getSession();	
		if($helper1->is_curl_installed()){
			if( $params['forgotPhoneOtp']!=null){
				$id = $session->getmoId();
				$email = $helper1->getConfig('miniorange_email',$id);
				$customerKey = $helper1->getConfig('customerKey',$id);
				$apiKey = $helper1->getConfig('apiKey',$id);
				$content = $helper2->validate_otp_token('EMAIL',$email, $session->getOTPtxtId(), $params['forgotPhoneOtp'], $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$this->checkRBA();
				}
				else{
						$session->unsminiError();
						$session->setminiError('Invalid OTP!');
						$this->redirect("twofactorauth/Index/validationPage");
				}
			}
			else{
					$session->unsminiError();
					$session->setminiError('Cannot Submit. Please Enter the otp sent to '.$session->getshowEmail().'.');
					$this->redirect("twofactorauth/Index/validationPage");
			}
		}
		else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function enteredOTPPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$id = $session->getmoId();
			$email = $helper1->getConfig('miniorange_email',$id);
			$customerKey = $helper1->getConfig('customerKey',$id);
			$apiKey = $helper1->getConfig('apiKey',$id);
			if( $params['smsotp']!=null && $params['customergatoken']==null){
				$content = $helper2->validate_otp_token(null,null, $session->getLogintxId(), $params['smsotp'], $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$this->checkRBA();
				}
				else{
						$session->setminiError('Invalid OTP!');
						$this->redirect("twofactorauth/Index/validationPage");
				}
			}else if($params['customergatoken']!=null){
				$content = $helper2->validate_otp_token('GOOGLE AUTHENTICATOR',$email, null, $params['customergatoken'], $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$this->checkRBA();
				}
				else{
					$session->unsminiError();
					$session->setminiError('Invalid Auth Token.');
					$this->redirect("twofactorauth/Index/validationPage");
				}
			}else{
					$session->unsminiError();
					$session->setminiError('Invalid OTP Token.');
					$this->redirect("twofactorauth/Index/validationPage");
			}
		}else{
			$session->unsminiError();
			$session->setminiError("cURL is not enabled.");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function addUserAction(){
		$params = $this->getRequest()->getParams();
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();		
		if($helper1->is_curl_installed()){
			$email = $params['additional_email'];
			$phone = $params['additional_phone'];
			$content = json_decode($helper2->send_otp_token($email,'EMAIL',$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)), true); 
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				$id = $this->getId();
				$session->setOTPsent(1);
				$session->setMytextid($content['txId']);
				$session->setUser($email);					
				$session->setPhone($phone);					
				$helper1->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.','SUCCESS');
				$this->redirect('twofactorauth/Index/configureTwoFactorPage');
			}
			else{
				$helper1->displayMessage('Error while sending OTP.',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}	
	}
	
	public function validateNewUserAction(){
		$params = $this->getRequest()->getParams();
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$otp = $params['otp'];
			if(strcmp($otp,"")!=0){
				$transactionId  =  $session->getMytextid();
				$content = json_decode($helper2->validate_otp_token( 'EMAIL', null, $transactionId , $otp , $helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)),true);
					if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
									$this->checkEndUser();
					}
					else{
						$helper1->displayMessage('An Error Occured while validating your OTP!',"ERROR");
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
				}
				else{
					$helper1->displayMessage('Please enter a valid otp',"ERROR");
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}
		}
		else{
			$helper1->displayMessage('cURL is not enabled.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
    }
	
	public function cancelValidationAction(){
		$session = $this->getSession();
		$session->unsOTPsent();
		$session->unsMytextid($content['txId']);
		$session->unsUser($email);					
		$session->unsPhone($phone);
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	/*Google Authenticator*/
	public function selectGAPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$email = $helper1->getConfig('miniorange_email',$id);
		$params = $this->getRequest()->getParams();
		$session->setShowTwoFactorSettings(1);
		$content = json_decode($helper2->mo2f_google_auth_service($email,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$mo2f_google_auth = array();
			$mo2f_google_auth['ga_qrCode'] = $content['qrCodeData'];
			$mo2f_google_auth['ga_secret'] = $content['secret'];
			$session->setmo2fGoogleAuth($mo2f_google_auth);
			$session->setGAPhone($params['mo2f_app_type_radio']);
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
		else{
			$helper1->displayMessage('There was an error proccessing your request. Try Again!',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function validateGATokenAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$email = $helper1->getConfig('miniorange_email',$id);
		$params = $this->getRequest()->getParams();
		$mo2fdata = Mage::getSingleton('customer/session')->getmo2fGoogleAuth();
		$content = json_decode($helper2->mo2f_validate_google_auth($email,$params['google_token'],$mo2fdata['ga_secret'],$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$this->saveConfig('customer_ga_configured',1,$id);
			$session->unsGAPhone();
			$session->unsmo2fGoogleAuth();
			$session->unsShowGoogleAuthSetup();
			if(!$session->getReconfigure()){
				$this->saveTwoFactorType($session->getTwoFactorType(),$id);
				$this->saveConfig('miniorange_mobileconfigured',1,$id);
				$session->unsTwoFactorType();
				$session->setshowLoginSettings(1);
				$url = Mage::getUrl('customer/account/logout');
				$helper1->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with Google Authenticator.',"SUCCESS");
			}else{
				$helper1->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
				$session->setShowTwoFactorSettings(1);
				$session->unsReconfigure();
			}
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}else{
			$helper1->displayMessage('Invalid OTP! Please Try Again',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function resendValidationOTPAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$email = $session->getUser();
			$content = json_decode($helper2->send_otp_token($email,'EMAIL',$helper1->getConfig('customerKey',$id),$helper1->getConfig('apiKey',$id)), true); //send otp for verification
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				$this->getId();
				$session->setMytextid($content['txId']);
				$session->setOTPsent(1);
				$helper1->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.','SUCCESS');
				$this->redirect('twofactorauth/Index/configureTwoFactorPage');
			}
			else{
				$helper1->displayMessage('Error while sending OTP. Please try again!',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function reconfigureGAAction(){
		$session = $this->getSession();
		$session->setShowGoogleAuthSetup(1);
		$session->setReconfigure(1);
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function showQRCodeAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			$id = $this->getId();
			$email = $helper1->getConfig('miniorange_email',$id);
			if( strcasecmp($params['submit'], 'Go Back') != 0  ){
			if(strcmp($email,"")!=0){
				$useragent = $_SERVER['HTTP_USER_AGENT'];
				if(strpos($useragent,'Mobi') !== false){	
					$helper1->displayMessage('We Suggest Configuring Your Two Factor From a Desktop/Laptop.',"ERROR");
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}else{
					if($params['reconfigure_mobile'])
						$session->setReconfigure(1);
					else
					$this->saveConfig('customer_downloaded_app',$params['showDownload'],$id);
					$this->mo2f_get_qr_code_for_mobile($email,$id);
					$session->setShowQR(1);
					$session->setShowTwoFactorSettings(1);
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}
			}
			else{
				$helper1->displayMessage('You will have to register before configuring your mobile',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		  }else{
					
					$session->unsTwoFactorType();
					$session->unsShowConfigureMobile();
					$session->unsmo2fqrcode();
					$session->unsmo2ftransactionId();
					$session->unsShowQR();
					$session->setShowTwoFactorSettings(1);
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function transactionSuccessAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$id = $this->getId();
			$session->unsmo2fqrcode();
			$session->unsmo2ftransactionId();
			$session->unsShowQR();
			if($this->getSession()->getShowTestMobileAuth() || $this->getSession()->getTestpushnotification()
					|| $this->getSession()->getTestoutofband() ){
				$this->getSession()->unsTestValidationScreen();
				$this->getSession()->unsShowTestMobileAuth();
				$this->getSession()->unsTestpushnotification();
				$this->getSession()->unsTestoutofband();
				$this->getSession()->setShowTwoFactorSettings(1);
				$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}else if($session->getReconfigure()){
				$session->setShowTwoFactorSettings(1);
				$session->unsReconfigure();
				$helper1->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}else{
				$url = Mage::getUrl('customer/account/logout');
				$helper1->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with mobile authentication.',"SUCCESS");
				$this->saveConfig('miniorange_mobileconfigured',1,$id);
				$session->unsShowConfigureMobile();
				$this->saveTwoFactorType($session->getTwoFactorType(),$id);
				$session->unsTwoFactorType();
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
public function transactionTimeOutAction(){
		$session = $this->getSession();
		$session->unsmo2fqrcode();
		$session->unsmo2ftransactionId();
		$session->unsShowQR();
		$session->unsShowConfigureMobile();
		$session->unsTwoFactorType();
		$session->unsReconfigure();
		$session->unsTestpushnotification();
		$session->unsTestoutofband();
		$session->unsTestValidationScreen();			
		$session->unsShowTestMobileAuth();	
		$this->getHelper1()->displayMessage('Timed Out. Please Try Again.',"ERROR");
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function configurePhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 =  $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper1->getConfig('miniorange_email',$id);
			if($email!=""){
				$phone = $params['phone'];
				if($session->getReconfigure()){
					$authType = $session->getTestPhone();
				}else{
					$authType = $session->getTwoFactorType();
				}
				if( strcasecmp($authType,"SMS") == 0  ){
					$authType = "OTP_OVER_SMS";
				}
				else{
					$authType = "PHONE_VERIFICATION";
				}
				$content = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),$authType,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'),$phone);
				$response = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE){
					$session->setLogintxId($response['txId']);
					$session->setPhone($phone);
					if(strcasecmp($authType, 'OTP_OVER_SMS') == 0)
						$helper1->displayMessage('An OTP has been sent to <b>'.$phone.'</b>. Please enter the one time passcode below.',"SUCCESS");
					else
						$helper1->displayMessage('You will receive a phone call on this number '.$phone.'. Please enter the one time passcode below.',"SUCCESS");
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}
				else{
					$helper1->displayMessage('Invalid Request!',"ERROR");
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}
			}
			else{
				$helper1->displayMessage('You will have to register before you can enable 2factor',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function verifyPhoneAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$customer = $this->getSession()->getCustomer();
			$id = $customer->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper1->getConfig('miniorange_email',$id);
			if($email!=""){
				$otp = $params['otp'];
				$submit = $params['submit'];
				if(strcasecmp($submit,"Validate") == 0){
					$content = $helper2->validate_otp_token(null,null,$session->getLogintxId(),$otp,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'));
					$response = json_decode($content, true);
					if(strcasecmp($response['status'], 'FAILED') != 0){
						$session->unsLogintxId();
						$this->saveConfig('miniorange_phone',$session->getPhone(),$id);
						$session->unsShowPhoneValidation();
						$session->unsPhone();
						if(!$session->getReconfigure()){
							$this->saveTwoFactorType($session->getTwoFactorType(),$id);
							$session->unsTwoFactorType();
							$session->setshowLoginSettings(1);
							$url = Mage::getUrl('customer/account/logout');
							$helper1->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with Phone Validation.',"SUCCESS");
						}else{ 
							$helper1->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
							$session->setShowTwoFactorSettings(1);
							$session->unsReconfigure();
						}
						$session->unsTestPhone();
						$session->unsReconfigure();
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
					else{
						$session->unsLogintxId();
						$session->unsPhone();
						$helper1->displayMessage("Invalid OTP!","ERROR");
						$session->setShowTwoFactorSettings(1);
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
				}
				else if(strcasecmp($submit,"Go Back") == 0){
					$session->unsLogintxId();
					$session->unsPhone();
					$session->unsShowPhoneValidation();
					$session->unsTwoFactorType();
					$session->setShowTwoFactorSettings(1);
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");
				}
				else if(strcasecmp($submit,"Resend OTP") == 0){
					$content = $helper2->send_otp_token($helper1->getConfig('miniorange_email',$id),$session->getTwoFactorType(),$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'),$session->getPhone());
					$response = json_decode($content, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($response['txId']);
						$phone = $session->getPhone();
						if(strcasecmp($session->getTwoFactorType(), 'SMS') != 0)
							$helper1->displayMessage('An OTP has been sent to <b>'.$phone.'</b>. Please enter the one time passcode below.',"SUCCESS");
						else
							$helper1->displayMessage('You will receive a phone call on this number '.$phone.'. Please enter the one time passcode below.',"SUCCESS");
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
					else{
						$helper1->displayMessage('Invalid Request!',"ERROR");
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
				}
			}
			else{
				$helper1->displayMessage('You will have to register before you can enable 2factor',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	/* SAVE TWO FACTOR METHOD */
	public function saveMethodAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		if($helper1->is_curl_installed()){
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper1->getConfig('miniorange_email',$id);
			$session = $this->getSession();
			if($email!=""){
				$session->setShowTwoFactorSettings(1);
				if(strcasecmp($params['mo2f_selected_2factor_method'], 'MOBILE AUTHENTICATION') == 0 || strcasecmp($params['mo2f_selected_2factor_method'], 'SOFT TOKEN') == 0 
								|| strcasecmp($params['mo2f_selected_2factor_method'], 'PUSH NOTIFICATIONS') == 0 ){
					if($helper1->getConfig('miniorange_mobileconfigured',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method'],$id);
					}
					else{
							$session->setShowConfigureMobile(1);
							$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}
				else if(strcasecmp($params['mo2f_selected_2factor_method'], 'SMS') == 0 || strcasecmp($params['mo2f_selected_2factor_method'], 'PHONE VERIFICATION') == 0){
					if($helper1->getConfig('miniorange_phone',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method'],$id);
					}
					else{
							$session->setShowPhoneValidation(1);
							$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}
				else if(strcasecmp($params['mo2f_selected_2factor_method'], 'GOOGLE AUTHENTICATOR') == 0){
					if($helper1->getConfig('customer_ga',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
						$session->setShowGoogleAuthSetup(1);
						$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else if(strcasecmp($params['mo2f_selected_2factor_method'], 'KBA') == 0){
					if($helper1->getConfig('customer_kba_Configured',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
						$session->setShowKBASetup(1);
						$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else{
					$this->saveTwoFactorType($params['mo2f_selected_2factor_method'],$id);
				}
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");	
			}
			else{
				$helper1->displayMessage('You will have to register before you can enable 2factor',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}
		else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}	
	}
	
	public function reconfigurePhoneAction(){
		$params = $this->getRequest()->getParams();
		$session = $this->getSession();
		$session->setShowPhoneValidation(1);
		$session->setTestPhone($params["phone_reconfigure"]);
		$session->setReconfigure(1);
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function saveKBAQuestionsAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$customer = $this->getSession()->getCustomer();
			$id = $customer->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper1->getConfig('miniorange_email',$id);
			$kba_q1 = $params[ 'mo2f_kbaquestion_1' ];
			$kba_a1 = trim( $params[ 'mo2f_kba_ans1' ] );
			$kba_q2 = $params[ 'mo2f_kbaquestion_2' ];
			$kba_a2 = trim( $params[ 'mo2f_kba_ans2' ] );
			$kba_q3 = trim( $params[ 'mo2f_kbaquestion_3' ] );
			$kba_a3 = trim( $params[ 'mo2f_kba_ans3' ] );
			$kba_reg_reponse = json_decode($helper2->register_kba_details($email,$kba_q1,$kba_a1,$kba_q2,$kba_a2,$kba_q3,$kba_a3,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
			if($kba_reg_reponse['status'] == 'SUCCESS'){
				$session->unsShowKBASetup();
				$this->saveConfig('kba_Configured',1,$id);
				if(!$session->getReconfigure()){
					$this->saveTwoFactorType($session->getTwoFactorType(),$id);
					$session->unsTwoFactorType();
					$session->setshowLoginSettings(1);
					$url = Mage::getUrl('customer/account/logout');
					$helper1->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with KBA.',"SUCCESS");
				}else{
					$helper1->displayMessage('You have Successfully Re-Configured KBA',"SUCCESS");
					$session->setShowTwoFactorSettings(1);
					$session->unsReconfigure();
				}
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}else{
				$helper1->displayMessage('Error occured while saving your kba details. Please try again.',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}
		}else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function validateKBAAnswersAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$email = $helper1->getConfig('miniorange_email',$id);
		$params = $this->getRequest()->getParams();
		$apiKey = $helper1->getConfig('apiKey',$id);
		$customerKey = $helper1->getConfig('customerKey',$id);
		$otptoken = array();
		$otptoken[0] = $session->getKBAQuestion1();
		$otptoken[1] = trim($params['mo2f_answer_1']);
		$otptoken[2] = $session->getKBAQuestion2();
		$otptoken[3] = trim($params['mo2f_answer_2']);
		$content = $helper2->validate_otp_token('KBA',null, $session->getLogintxId(),$otptoken, $customerKey, $apiKey);
		$response = json_decode($content, true);
		if(strcasecmp($response['status'], 'FAILED') != 0){
			$this->checkRBA();
		}else{
			$session->setminiError("Enter a valid Soft Token");
			$this->redirect("twofactorauth/Index/validationPage");
		}
	}
	
	public function reconfigureKBAAction(){
		$session = $this->getSession();
		$session->setShowKBASetup(1);
		$session->setReconfigure(1);
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function goBackTwoFactorAction(){
		$session = $this->getSession();
		$session->unsmo2fqrcode();
		$session->setmo2ftransactionId();
		$session->unsTestValidationScreen();
		$session->unsVerifytxId();
		$session->unsTestValidationScreen();
		$session->unsTestsms();
		$session->unsTestKBA();
		$session->unsKBAQuestion1();
		$session->unsKBAQuestion2();
		$session->unsTestsofttoken();
		$session->unsGAPhone();
		$session->unsShowKBASetup();
		$session->unsmo2fGoogleAuth();
		$session->unsShowGoogleAuthSetup();
		$session->unsTestphoneverification();
		$session->unsShowTestMobileAuth();
		$session->unsShowTwoFactorSettings();
		$this->redirect("twofactorauth/Index/configureTwoFactorPage");
	}
	
	public function testTwoFactorAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		if($helper1->is_curl_installed()){
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$session = $this->getSession();
			$email = $helper1->getConfig('miniorange_email',$id);
			$customerKey = $helper1->getConfig('customerKey');
			$apiKey = $helper1->getConfig('apiKey');
			switch($params['test_2factor']){
				case "MOBILE AUTHENTICATION":
					$sendotp = $helper2->send_otp_token($email,'MOBILE AUTHENTICATION', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setmo2fqrcode($status['qrCode']);
						$session->setmo2ftransactionId($status['txId']);
						$session->setTestValidationScreen(1);
						$session->setShowTestMobileAuth(1);
						$session->setShowTwoFactorSettings(1);
					}
					break;
	
				case "SOFT TOKEN":
					$session->setTestValidationScreen(1);
					$session->setTestsofttoken(1);
					$session->setShowTwoFactorSettings(1);
					break;
						
				case "SMS":
					$sendotp = $helper2->send_otp_token($email,'SMS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setVerifytxId($status['txId']);
						$session->setTestsms(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$helper1->displayMessage('An OTP has been sent to <b>'.$helper1->getConfig('miniorange_phone',$id).'</b>. Please enter the one time passcode below.',"SUCCESS");
					}
					else{	$helper1->displayMessage("Invalid request",'ERROR'); }
					break;
	
				case "PHONE VERIFICATION":
					$sendotp = $helper2->send_otp_token($email,'PHONE VERIFICATION', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setVerifytxId($status['txId']);
						$session->setTestphoneverification(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$helper1->displayMessage('You will get a call on <b>'.$helper1->getConfig('miniorange_phone',$id).'</b>. Please enter the one time passcode below.',"SUCCESS");
					}
					else{ $helper1->displayMessage("Invalid request",'ERROR'); }
					break;
	
				case "PUSH NOTIFICATIONS":
					$sendotp = $helper2->send_otp_token($email,'PUSH NOTIFICATIONS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setmo2ftransactionId($status['txId']);
						$session->setTestpushnotification(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
					}
					else{	$helper1->displayMessage("Invalid request",'ERROR'); }
					break;
	
				case "OUT OF BAND EMAIL":
					$sendotp = $helper2->send_otp_token($email,'OUT OF BAND EMAIL', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setmo2ftransactionId($status['txId']);
						$session->setTestoutofband(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$helper1->displayMessage('A mail has been sent to <b>'.$helper1->getConfig('miniorange_email',$id).'</b>. Please Accept or Deny the transaction.',"SUCCESS");
					}
					else{ $helper1->displayMessage("Invalid request",'ERROR'); }
					break;
					
				case "GOOGLE AUTHENTICATOR":
					$session->setTestValidationScreen(1);
					$session->setTestGoogleAuth(1);
					$session->setShowTwoFactorSettings(1);
					break;
				
				case "KBA":
					$sendotp = $helper2->send_otp_token($email,'KBA', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setVerifytxId($status['txId']);
						$questions = array();
						$questions[0] = $status['questions'][0]['question'];
						$questions[1] = $status['questions'][1]['question'];
						$session->setKBAQuestion1($questions[0]);
						$session->setKBAQuestion2($questions[1]);
						$session->setTestValidationScreen(1);
						$session->setTestKBA(1);
						$session->setShowTwoFactorSettings(1);
					}else{ $session->addError("Invalid request"); }
					break;
					
				default:
					$this->displayMessage('Invalid Action!',"ERROR");
					break;
			}
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	public function testTwoFactorOTPAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->is_curl_installed()){
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper1->getConfig('miniorange_email',$id);
			$customerKey = $helper1->getConfig('customerKey');
			$apiKey = $helper1->getConfig('apiKey');
			$soft_token = array_key_exists('soft_token', $params) ? $params['soft_token'] : '';
			$sms_otp = array_key_exists('sms_otp', $params) ? $params['sms_otp'] : '';
			$phonecall_otp = array_key_exists('phonecall_otp', $params) ? $params['phonecall_otp'] : '';
			$gaauth_otp = array_key_exists('gaauth_otp', $params) ? $params['gaauth_otp'] : '';
			$mo2f_answer_1 = array_key_exists('mo2f_answer_1', $params) ? $params['mo2f_answer_1'] : '';
			$mo2f_answer_2 = array_key_exists('mo2f_answer_2', $params) ? $params['mo2f_answer_2'] : '';
			if($soft_token!=""){
				$content = $helper2->validate_otp_token('SOFT TOKEN',$email, null, $soft_token, $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$session->unsTestValidationScreen();
					$session->unsTestsofttoken();
					$session->setShowTwoFactorSettings(1);
					$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
				}
				else{
					$session->unsTestValidationScreen();
					$session->unsTestsofttoken();
					$session->setShowTwoFactorSettings(1);
					$helper1->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
				}
			}else if($sms_otp!=""){
				$content = $helper2->validate_otp_token(null,null, $session->getVerifytxId(),$sms_otp, $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$session->unsVerifytxId();
					$session->unsTestValidationScreen();
					$session->unsTestsms();
					$session->setShowTwoFactorSettings(1);
					$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
				}
				else{
					$session->unsVerifytxId();
					$session->unsTestValidationScreen();
					$session->unsTestsms();
					$session->setShowTwoFactorSettings(1);
					$helper1->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
				}
			}else if($phonecall_otp!=""){
				$content = $helper2->validate_otp_token(null,null, $session->getVerifytxId(),$phonecall_otp, $customerKey, $apiKey);
				$response = json_decode($content, true);
				if(strcasecmp($response['status'], 'FAILED') != 0){
					$session->unsVerifytxId();
					$session->unsTestValidationScreen();
					$session->unsTestphoneverification();
					$session->setShowTwoFactorSettings(1);
					$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
				}
				else{
					$helper1->displayMessage('Invalid Process!',"ERROR");
				}
			}else if($gaauth_otp!=""){
					$content = $helper2->validate_otp_token('GOOGLE AUTHENTICATOR',$email, null, $gaauth_otp, $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$session->unsTestValidationScreen();
							$session->unsTestGoogleAuth();
							$session->setShowTwoFactorSettings(1);
							$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
						}
						else{
							$session->unsTestValidationScreen();
							$session->unsTestGoogleAuth();
							$session->setShowTwoFactorSettings(1);
							$helper1->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
						}
			}else if($mo2f_answer_1!="" && $params["mo2f_answer_2"]!=""){
					$otptoken = array();
					$otptoken[0] = $session->getKBAQuestion1();
					$otptoken[1] = trim($mo2f_answer_1);
					$otptoken[2] = $session->getKBAQuestion2();
					$otptoken[3] = trim($mo2f_answer_2);
					$content = $helper2->validate_otp_token('KBA',null, $session->getLogintxId(),$otptoken, $customerKey, $apiKey);
					$response = json_decode($content, true);
					if(strcasecmp($response['status'], 'FAILED') != 0){
						$session->unsTestValidationScreen();
						$session->unsTestKBA();
						$session->unsVerifytxId();
						$session->unsKBAQuestion1();
						$session->unsKBAQuestion2();
						$session->setShowTwoFactorSettings(1);
						$helper1->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
					}else{
						$session->unsTestValidationScreen();
						$session->unsTestKBA();
						$session->unsVerifytxId();
						$session->unsKBAQuestion1();
						$session->unsKBAQuestion2();
						$session->setShowTwoFactorSettings(1);
						$helper1->displayMessage('Transaction Failed. Invalid Answers.',"ERROR");
					}
				}else{
				$helper1->displayMessage('Invalid Process!',"ERROR");
			}
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}else{
			$helper1->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}	

	public function trustDeviceConfirmAction(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$id = $id = $session->getmoId();
		$apiKey = $helper1->getConfig('apiKey',$id);
		$customerKey = $helper1->getConfig('customerKey',$id);
		$email = $helper1->getConfig('miniorange_email',$id);
		$this->mo2f_register_profile($email,'true',$session->getMo2fRba(),$customerKey, $apiKey);
		$session->unsMo2fRba();
		$session->setLoginStatus('LOGIN_SUCCESS');
		Mage::dispatchEvent('customer_login_status');
	}
	
	public function trustDeviceCancelAction(){
		$this->getSession()->setLoginStatus('LOGIN_SUCCESS');
		Mage::dispatchEvent('customer_login_status');
	}
	
	private function getSession(){
		return Mage::getSingleton('customer/session');
	}
	
	private function getId(){
		return $this->getSession()->getCustomer()->getId();
	}
	
	private function getHelper1(){
		return Mage::helper($this->_helper1);
	}
	
	private function getHelper2(){
		return Mage::helper($this->_helper2);
	}
	
	private function checkEndUser(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		$email = $session->getUser();
		$phone = $session->getPhone();
		$customer = $this->getSession()->getCustomer();
		$id = $this->getId();
		$check_user = json_decode($helper2->mo_check_user_already_exist($email,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey')),true);
		if(json_last_error() == JSON_ERROR_NONE){	
			if(strcasecmp($check_user['status'], 'USER_FOUND') == 0){
				$this->saveConfig('miniorange_email',$email,$id);
				$this->saveConfig('miniorange_phone',$phone,$id);														
				$session->unsOTPsent();
				$session->unsMytextid($content['txId']);
				$session->unsUser($email);					
				$session->unsPhone($phone);
				$this->saveTwoFactorType("OUT OF BAND EMAIL",$id);
				$helper1->displayMessage('Registration Successful. EMAIL VERIFICATION has been set as your second factor. You can change your second factor below.',"SUCCESS");
				//$helper1->displayMessage('Registration Complete. Please Configure your mobile',"SUCCESS");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}else if(strcasecmp($check_user['status'], 'USER_NOT_FOUND') == 0){
				$content = json_decode($helper2->mo_create_user($email,$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'),$customer), true);
					if(strcasecmp($content['status'], 'SUCCESS') == 0) {
						$this->saveConfig('miniorange_email',$email,$id);
						$this->saveConfig('miniorange_phone',$phone,$id);														
						$session->unsOTPsent();
						$session->unsMytextid($content['txId']);
						$session->unsUser($email);					
						$session->unsPhone($phone);
						$helper1->displayMessage('Registration Complete. Please Configure your mobile',"SUCCESS");
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}else{
						$helper1->displayMessage('There was an Error while creating End User!',"ERROR");
						$this->redirect("twofactorauth/Index/configureTwoFactorPage");
					}
			}else if(strcasecmp($check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER') == 0){
				$helper1->displayMessage('The User already exists under another Admin.',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");
			}else{
					$helper1->displayMessage('User limit exceeded. Please upgrade your license to add more users.',"ERROR");
					$this->redirect("twofactorauth/Index/configureTwoFactorPage");					
			}
		}else{
				$helper1->displayMessage('There was an unknown error!',"ERROR");
				$this->redirect("twofactorauth/Index/configureTwoFactorPage");		
		}
	}				
	
	
	private function mo2f_get_qr_code_for_mobile($email,$id){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
			$content = $helper2->register_mobile($email,$id);
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) {
				$session->setmo2fqrcode($response['qrCode']);
				$session->setmo2ftransactionId($response['txId']);
			}
	}
	
	
	private function redirect($url){
		$redirectUrl = Mage::getModel('core/url')->getUrl($url);
		$this->_redirectUrl($redirectUrl); 
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
	
	private function saveTwoFactorType($authType,$id){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$content = $helper2->mo2f_update_userinfo($helper1->getConfig('miniorange_email',$id),$authType,$helper1->getConfig('miniorange_phone',$id),$helper1->getConfig('customerKey'),$helper1->getConfig('apiKey'));
		$response = json_decode($content, true); 
		if(strcasecmp($response['status'], 'SUCCESS') == 0) {
			$this->saveConfig('customer_twofactortype',$authType,$id);
			$helper1->displayMessage($authType." has been set as your Two Factor.","SUCCESS");
		}
		else{
			$helper1->displayMessage('There was an ERROR while setting your Authentication Type. Please Choose One from the list below:',"ERROR");
			$this->redirect("twofactorauth/Index/configureTwoFactorPage");
		}
	}
	
	//RBA
	private function checkRBA(){
		$helper1 = $this->getHelper1();
		$helper2 = $this->getHelper2();
		$session = $this->getSession();
		if($helper1->getConfig('customer_rmd_enable',null)==1){
			if($helper1->getConfig('appSecret',null)==NULL){
				$appSecret = json_decode($helper2->mo2f_get_app_secret($helper1->getConfig('customerKey',null),$helper1->getConfig('apiKey',null)),true); //register profile
				$storeConfig = new Mage_Core_Model_Config();
				$storeConfig ->saveConfig('miniOrange/twofactor/appSecret',$appSecret['appSecret'], 'default', 0);
			}
			$session->unsLoginQRCode();
			$session->unsLogintxtId();
			$session->unsLogintxId();
			$session->unsLoginWaitId();
			$session->unsOTPtxtId();
			$session->setshowRBAScreen(1);
			$this->redirect("twofactorauth/Index/validationPage");
		}else{
			$session->setLoginStatus('LOGIN_SUCCESS');
			Mage::dispatchEvent('customer_login_status');
		}
	}
	
	protected function mo2f_register_profile($email,$deviceKey,$mo2f_rba_status,$customerKey,$apiKey){
		$helper = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if(isset($deviceKey) && $deviceKey == 'true'){
			if($mo2f_rba_status['status'] == 'WAIT_FOR_INPUT' && $mo2f_rba_status['decision_flag']){
				$rba_response = json_decode($helper->mo2f_register_rba_profile($email,$mo2f_rba_status['sessionUuid'],$customerKey,$apiKey),true); //register profile
				return true;
			}else{ return false; }
		}
		return false;
	}
	
}