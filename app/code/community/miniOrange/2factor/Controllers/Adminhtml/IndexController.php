<?php

class MiniOrange_2factor_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
  private $defaultCustomerKey = "16352";												
  private $defaultApiKey = "AJG97LGpOVVwFUuuPSij5IH6Kvlu6qEj";
	
  public function indexAction(){
         $this->loadLayout();
         $this->renderLayout();
		 Mage::getSingleton('core/session')->unsErrorMessage();
		 Mage::getSingleton('core/session')->unsSuccessMessage();
		  Mage::getSingleton('admin/session')->unsshowLoginSettings();
		  Mage::getSingleton('admin/session')->unsOTPsent();
		  Mage::getSingleton('admin/session')->unsEnteredEmail();
  }
  
   
	public function newUserAction(){
		$params = $this->getRequest()->getParams();
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$email = $params['email'];
			Mage::getSingleton('admin/session')->setEnteredEmail($email);
			$password = $params['password'];
			$phone = $params['phone'];
			$confirmPassword = $params['confirmPassword'];	
			if(strcmp($password,$confirmPassword)!=0){
				$this->displayMessage('Passwords do not match.',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else{
				$admin = Mage::getSingleton('admin/session')->getUser();
				$id = $admin->getUserId();
				$content = json_decode($customer->check_customer($email), true);
				if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){ 
				$content = json_decode($customer->send_otp_token($email,'EMAIL',$this->defaultCustomerKey,$this->defaultApiKey), true); //send otp for verification
					if(strcasecmp($content['status'], 'SUCCESS') == 0){
						Mage::getSingleton('admin/session')->setMytextid($content['txId']);
						Mage::getSingleton('admin/session')->setOTPsent(1);
						$this->saveConfig('miniorange_2factor_show_otp',1,$id);
						$this->saveConfig('miniorange_2factor_login',0,$id);
						$this->saveConfig('miniorange_2factor_email',$email,$id);
						$this->saveConfig('miniorange_2factor_pass',$password,$id);
						$this->saveConfig('miniorange_2factor_phone',$phone,$id);
						$this->saveConfig('miniorange_2factor_admin_registered',$id,$id);
						$this->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.',"SUCCESS");
						$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
					else{
						$this->displayMessage('You are already a registered user',"ERROR");
						$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
				}
				else{
					$content = $customer->get_customer_key($email,$password);
					$customerKey = json_decode($content, true);
					if(json_last_error() == JSON_ERROR_NONE) {
						$this->saveConfig('miniorange_2factor_email',$email,$id);
						$this->saveConfig('miniorange_2factor_phone',$phone,$id);
						$collection = Mage::getModel('admin/user')->getCollection();
						foreach($collection as $item){
							$ids=$item->getData('user_id');
							$this->saveConfig('miniorange_2factor_customer_key',$customerKey['id'],$ids);
							$this->saveConfig('miniorange_2factor_api_key',$customerKey['apiKey'],$ids);
							$this->saveConfig('miniorange_2factor_token',$customerKey['token'],$ids);
							$this->saveConfig('miniorange_2factor_admin_registered',$id,$ids);
							if($ids!=$id){
								$this->saveConfig('miniorange_2factor_validated',0,$ids);
							}
						}
						$this->saveConfig('miniorange_2factor_pass',"",$id);
						$this->saveConfig('miniorange_2factor_show_otp',0,$id);
						$this->saveConfig('miniorange_2factor_show_configure',1,$id);
						$this->saveConfig('miniorange_2factor_validated',1,$id);
						$this->saveConfig('miniorange_2factor_login',0,$id);
						$this->displayMessage('Registration Successful configure your mobile below',"SUCCESS");
						$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
					else{
						$this->saveConfig('miniorange_2factor_login',"1",$id);
						$this->displayMessage('Invalid Credentials',"ERROR");
						$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
				}
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
    }
	
	public function validateNewUserAction(){
		$params = $this->getRequest()->getParams();
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$otp = $params['otp'];
			$email = Mage::getSingleton('core/session')->getaddAdmin();
			$phone = Mage::getSingleton('core/session')->getaddPhone();
			if(strcmp($otp,"")!=0){
				$transactionId  =  Mage::getSingleton('admin/session')->getMytextid();
				$content = json_decode($customer->validate_otp_token( 'EMAIL', null, $transactionId , $otp , $this->defaultCustomerKey, $this->defaultApiKey),true);
				
					if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
						$adminregistered = $customer->getConfig('mainAdmin',$id);
						if($adminregistered!="" && $adminregistered==$id){
							$this->mo2f_create_customer();
						}
						else{
							$this->saveConfig('miniorange_2factor_email',$email,$id);
							$this->saveConfig('miniorange_2factor_phone',$phone,$id);
							$this->saveConfig('miniorange_2factor_pass',"",$id);
							$this->saveConfig('miniorange_2factor_show_otp',0,$id);
							$this->saveConfig('miniorange_2factor_show_configure',1,$id);
							$this->saveConfig('miniorange_2factor_validated',1,$id);
							$this->saveConfig('miniorange_2factor_login',0,$id);
							$this->displayMessage('Registration Complete. Please Configure your mobile',"SUCCESS");
							$this->redirect("miniorange_2factor/adminhtml_index/index");
						}
				}
				else{
					$this->displayMessage('Please enter a valid otp',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
				$this->displayMessage('Please enter a valid otp',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
    }

	public function existingUserAction(){
		$params = $this->getRequest()->getParams();
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$email = $params['loginemail'];
			Mage::getSingleton('admin/session')->setEnteredEmail($email);
			$password = $params['loginpassword'];
			$submit = $params['submit'];
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			if(strcasecmp($submit,"Submit") == 0){
				$content = $customer->get_customer_key($email,$password);
				$customerKey = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					$this->saveConfig('miniorange_2factor_email',$email,$id);
					$collection = Mage::getModel('admin/user')->getCollection();
					foreach($collection as $item){
						$ids=$item->getData('user_id');
						$this->saveConfig('miniorange_2factor_customer_key',$customerKey['id'],$ids);
						$this->saveConfig('miniorange_2factor_api_key',$customerKey['apiKey'],$ids);
						$this->saveConfig('miniorange_2factor_token',$customerKey['token'],$ids);
						if($ids!=$id){
							$this->saveConfig('miniorange_2factor_validated',0,$ids);
						}
					}
					$this->saveConfig('miniorange_2factor_pass',"",$id);
					$this->saveConfig('miniorange_2factor_show_otp',0,$id);
					$this->saveConfig('miniorange_2factor_show_configure',1,$id);
					$this->saveConfig('miniorange_2factor_validated',1,$id);
					$this->saveConfig('miniorange_2factor_login',0,$id);
					$this->displayMessage('Registration Successful. Please Configure your mobile below',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
					$this->saveConfig('miniorange_2factor_login',1,$id);
					$this->displayMessage('Invalid Credentials',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else if(strcasecmp($submit,"Forgot Password?") == 0){
				$this->forgotPass($email);
				$this->saveConfig('miniorange_2factor_login',1,$id);
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else{
				$this->saveConfig('miniorange_2factor_login',0,$id);
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
    }
	
	public function additionalAdminAction(){
		$params = $this->getRequest()->getParams();
			$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
			if($customer->is_curl_installed()){
				$email = $params['additional_email'];
				$phone = $params['additional_phone'];
				$admin = Mage::getSingleton('admin/session')->getUser();
				$id = $admin->getUserId();
				$content = json_decode($customer->send_otp_token($email,'EMAIL',$this->defaultCustomerKey,$this->defaultApiKey), true); 
				if(strcasecmp($content['status'], 'SUCCESS') == 0){
					$admin = Mage::getSingleton('admin/session')->getUser();
					$id = $admin->getUserId();
					Mage::getSingleton('admin/session')->setOTPsent(1);
					Mage::getSingleton('admin/session')->setMytextid($content['txId']);
					$this->saveConfig('miniorange_2factor_show_otp',1,$id);
					$this->saveConfig('miniorange_2factor_login',0,$id);
					Mage::getSingleton('core/session')->setaddAdmin($email);					
					Mage::getSingleton('core/session')->setaddPhone($phone);					
					$this->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
						$this->displayMessage('Error while sending OTP.',"ERROR");
						$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
				$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}	
	}
	
	public function enableAdminAction(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$params = $this->getRequest()->getParams();
			$email = $customer->getConfig('email',$id);
			$validated = $customer->getConfig('validated',$id);	
			$showqr = $customer->getConfig('configure',$id);	
			Mage::getSingleton('admin/session')->setshowLoginSettings(1);
			if($email!="" && $validated==1){
				if($showqr==0){
					$value = $params['adminrole_activation'];
					if($value==1){
						$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
						$this->displayMessage('Enabled Two Factor for Admin',"SUCCESS");
					}
					else{
						$this->saveConfig('miniorange_2factor_Admin_enable',0,$id);
						$this->displayMessage('Disabled Two Factor for Admin',"ERROR");
					}
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
					$this->displayMessage('You will have to configure your mobile before you can enable 2factor',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
				$this->displayMessage('You will have to register before you can enable 2factor',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
    }
	
	public function supportSubmitAction(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			$user = Mage::getSingleton('admin/session')->getUser();
			$customer->submit_contact_us($params['query_email'], $params['query_phone'], $params['query'], $user);
			$this->displayMessage('Your query has been sent. We will get in touch with you soon',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	
	public function registrationSuccessAction(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$url = Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
			$this->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with mobile authentication.',"SUCCESS");
			$this->saveConfig('miniorange_2factor_show_qr',0,$id);
			$this->saveConfig('miniorange_2factor_show_configure',0,$id);
			Mage::getSingleton('admin/session')->setshowLoginSettings(1);
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function showQRCodeAction(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$email = $customer->getConfig('email',$id);
			$validated = $customer->getConfig('validated',$id);
			if($email!="" && $validated==1){
				$this->saveConfig('miniorange_2factor_show_configure',1,$id);
				$this->saveConfig('miniorange_2factor_downloaded_app',$params['showDownload'],$id);
				$this->mo2f_get_qr_code_for_mobile($email,$id);
			}
			else{
				$this->displayMessage('You will have to register before configuring your mobile',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function resendValidationOTPAction(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$email = $customer->getConfig('email',$id);
			$content = json_decode($customer->send_otp_token($email,'EMAIL',$this->defaultCustomerKey,$this->defaultApiKey), true); //send otp for verification
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				Mage::getSingleton('admin/session')->setMytextid($content['txId']);
				$this->saveConfig('miniorange_2factor_show_otp',1,$id);
				$this->saveConfig('miniorange_2factor_login',0,$id);
				$this->saveConfig('miniorange_2factor_admin_registered',$id,$id);
				$this->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.',"SUCCESS");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else{
				$this->displayMessage('You are already a registered user',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function registrationTimeOut(){
		Mage::getSingleton('core/session')->unsmo2fqrcode($response['qrCode']);
		Mage::getSingleton('core/session')->unsmo2ftransactionId($response['txId']);
		$this->displayMessage('Connection TimedOut. Please click on the Re-Configure button below to configure your mobile.',"ERROR");
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function cancelValidationAction(){
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
		$this->saveConfig('miniorange_2factor_show_otp',null,$id);
		$this->saveConfig('miniorange_2factor_login',null,$id);
		$this->saveConfig('miniorange_2factor_admin_registered',null,$id);
		$this->saveConfig('miniorange_2factor_email',"",$id);
		$this->saveConfig('miniorange_2factor_pass',"",$id);
		$this->saveConfig('miniorange_2factor_phone',"",$id);
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	
	private function redirect($url){
		$redirect = Mage::helper("adminhtml")->getUrl($url);
		Mage::app()->getResponse()->setRedirect($redirect);
	}
  
	private function saveConfig($url,$value,$id){
		$data = array($url=>$value);
		$model = Mage::getModel('admin/user')->load($id)->addData($data);
		try {
				$model->setId($id)->save(); 
			} catch (Exception $e){
				Mage::log($e->getMessage(), null, 'miniorage_error.log', true);
		}
	}
  
	private function displayMessage($message,$type){
		Mage::getSingleton('core/session')->getMessages(true);
		Mage::getSingleton('core/session')->unsSuccessMessage();
		Mage::getSingleton('core/session')->unsErrorMessage();
		if(strcasecmp( $type,"SUCCESS") == 0)
			Mage::getSingleton('core/session')->setSuccessMessage($message);
		else
			Mage::getSingleton('core/session')->setErrorMessage($message);
	}
	
	private function mo2f_create_customer(){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$admin = Mage::getSingleton('admin/session')->getUser();
			$id = $admin->getUserId();
			$email = $customer->getConfig('email',$id);
			$password = $customer->getConfig('pass',$id);
			$customerKey = json_decode($customer->create_customer($email,'',$password), true);
			if(strcasecmp($customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0) {	//admin already exists in miniOrange
				$content = $customer->get_customer_key($email,$password);
				$customerKey = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					$collection = Mage::getModel('admin/user')->getCollection();
					foreach($collection as $item){
						$ids=$item->getData('user_id');
						$this->saveConfig('miniorange_2factor_customer_key',$customerKey['id'],$ids);
						$this->saveConfig('miniorange_2factor_api_key',$customerKey['apiKey'],$ids);
						$this->saveConfig('miniorange_2factor_token',$customerKey['token'],$ids);
						if($ids!=$id){
							$this->saveConfig('miniorange_2factor_validated',0,$ids);
						}
					}
					$this->saveConfig('miniorange_2factor_pass',"",$id);
					$this->saveConfig('miniorange_2factor_show_otp',0,$id);
					$this->saveConfig('miniorange_2factor_show_configure',1,$id);
					$this->saveConfig('miniorange_2factor_validated',1,$id);
					$this->saveConfig('miniorange_2factor_login',0,$id);
					$this->displayMessage('Registration Complete. Please Configure your mobile',"SUCCESS");
				} else {
					$this->displayMessage('An error occurred while creating customer',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}else{
					$collection = Mage::getModel('admin/user')->getCollection();
					foreach($collection as $item){
						$ids=$item->getData('user_id');
						$this->saveConfig('miniorange_2factor_customer_key',$customerKey['id'],$ids);
						$this->saveConfig('miniorange_2factor_api_key',$customerKey['apiKey'],$ids);
						$this->saveConfig('miniorange_2factor_token',$customerKey['token'],$ids);
						if($ids!=$id){
							$this->saveConfig('miniorange_2factor_validated',0,$ids);
						}
					}
					$this->saveConfig('miniorange_2factor_pass',"",$id);
					$this->saveConfig('miniorange_2factor_show_otp',0,$id);
					$this->saveConfig('miniorange_2factor_login',0,$id);
					$this->saveConfig('miniorange_2factor_show_configure',1,$id);
					$this->saveConfig('miniorange_2factor_validated',1,$id);
					$this->displayMessage('Registration Complete. Please Configure your mobile',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	
	private function mo2f_get_qr_code_for_mobile($email,$id){
		$registerMobile = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		if($customer->is_curl_installed()){
			$content = $registerMobile->register_mobile($email,$id);
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) {
				Mage::getSingleton('core/session')->setmo2fqrcode($response['qrCode']);
				Mage::getSingleton('core/session')->setmo2ftransactionId($response['txId']);
				$this->saveConfig('miniorange_2factor_show_qr',1,$id);
				$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	private function forgotPass($email){
		$customer = Mage::helper('MiniOrange_2factor/mo2fUtility');
		$params = $this->getRequest()->getParams();
		$content = json_decode($customer->forgot_password($email,$this->defaultCustomerKey,$this->defaultApiKey), true); 
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$this->displayMessage('Your new password has been generated and sent to '.$email.'.',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('Sorry we encountered an error while reseting your password.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
		
}