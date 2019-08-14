<?php

class MiniOrange_2factor_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
	private $_helper1 = "MiniOrange_2factor";
	private $_helper2 = "MiniOrange_2factor/mo2fUtility";
	
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
        $session = $this->getSession();
		$this->getCoreSession()->unsErrorMessage();
		$this->getCoreSession()->unsSuccessMessage();
		$session->unsshowLoginSettings();
	    $session->unsOTPsent();
	    $session->unsEnteredEmail();
    }
	
	public function validateNewUserAction(){
		$params = $this->getRequest()->getParams();
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$otp = $params['otp'];
			$email = $session->getaddAdmin();
			$phone = $session->getaddPhone();
			$pass = $session->getaddPass();
			if(strcmp($otp,"")!=0){
				$transactionId  =  $session->getMytextid();
				$content = json_decode($customer->validate_otp_token( 'EMAIL', null, $transactionId , $otp , $customer->getConfig('customerKey',$id), $customer->getConfig('apiKey',$id)),true);
				if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
							$this->checkEndUser($email,$phone);
				}
				else{
					$this->displayMessage('Please enter a valid otp',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
				$this->displayMessage('Please enter an otp',"ERROR");
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
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			$email = $params['loginemail'];
			$session->setEnteredEmail($email);
			$session->setaddAdmin($email);
			$password = $params['loginpassword'];
			$submit = array_key_exists('submit', $params) ? $params['submit'] : "";
			$hidden = array_key_exists('hidden', $params) ? $params['hidden'] : "";
			//$admin = $session->getUser();
			$id = $this->getId();
			if(strcasecmp($submit,"submit") == 0){
				$content = $customer->get_customer_key($email,$password);
				$customerKey = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					$this->saveConfig('miniorange_2factor_email',$email,$id);
					$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
					$storeConfig = new Mage_Core_Model_Config();
					$storeConfig ->saveConfig('miniOrange/twofactor/customerKey',$customerKey['id'], 'default', 0);
					$storeConfig ->saveConfig('miniOrange/twofactor/apiKey',$customerKey['apiKey'], 'default', 0);
					$storeConfig ->saveConfig('miniOrange/twofactor/twofactorToken',$customerKey['token'], 'default', 0);
					$storeConfig ->saveConfig('miniOrange/twofactor/appSecret',$customerKey['appSecret'], 'default', 0);
					$storeConfig ->saveConfig('miniOrange/twofactor/admin/inline_registration','1', 'default', 0);
					$roles = Mage::getModel('admin/roles')->getCollection();
					foreach($roles as $role):
						$this->saveRoleConfig('enable_two_factor',1,$role->getRoleId());
					endforeach;
					$session->unsaddAdmin();
					$session->unsaddPhone();
					$session->unsaddPass();
					$session->unsShowOTP();
					$session->setShowTwoFactorSettings(1);
					$this->saveTwoFactorType("OUT OF BAND EMAIL");
					$this->displayMessage('Registration Successful. EMAIL VERIFICATION has been set as your second factor. You can change your second factor below.',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
					$this->displayMessage('Invalid Credentials',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else if(strcasecmp($submit,"Forgot Password?") == 0){
				$this->forgotPass($email);
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else if(strcasecmp($hidden,"Go Back") == 0){
				$session->unsaddadmin();
				$session->unsaddPhone();
				$session->unsaddPass();
				$session->unsRegTxID();
				$session->unsmo2fstatus();
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else{
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
			$helper = $this->getHelper1();
			$customer = $this->getHelper2();
			$session = $this->getSession();
			if($helper->is_curl_installed()){
				$email = $params['additional_email'];
				$phone = $params['additional_phone'];
				$content = json_decode($customer->send_otp_token($email,'EMAIL',$helper->getdefaultCustomerKey(),$helper->getdefaultApiKey()), true); 
				if(strcasecmp($content['status'], 'SUCCESS') == 0){
					//$admin = $session->getUser();
					$id = $this->getId();
					$session->setOTPsent(1);
					$session->setMytextid($content['txId']);
					$session->setShowOTP(1);
					$session->setaddAdmin($email);					
					$session->setaddPhone($phone);					
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
	
	public function saveLoginSettingsAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper->getConfig('email',$id);
			$twofactortype = $helper->getConfig('twofactortype',$id);	
			$session->setshowLoginSettings(1);
			$session->unsShowConfigureMobile();
			$session->unsShowPhoneValidation();		
			if($email!=""){
				if($twofactortype){
					$value2 = array_key_exists('customer_activation', $params) ? $params['customer_activation'] : "";
					$value3 = array_key_exists('admin_remember_device_activation', $params) ? $params['admin_remember_device_activation'] : "";
					$value4 = array_key_exists('customer_remember_device_activation', $params) ? $params['customer_remember_device_activation'] : "";
					$value5 = array_key_exists('admin_inline_registration_activation', $params) ? $params['admin_inline_registration_activation'] : "";
					$value6 = array_key_exists('customer_inline_registration_activation', $params) ? $params['customer_inline_registration_activation'] : "";
					$roles = Mage::getModel('admin/roles')->getCollection();
					$groups = Mage::helper('customer')->getGroups();
					foreach($roles as $role):
						$checkRole = array_key_exists($role->getRoleName(), $params) ? $params[$role->getRoleName()] : 0;
						if($checkRole==1)
							$this->saveRoleConfig('enable_two_factor',1,$role->getRoleId());
						else
							$this->saveRoleConfig('enable_two_factor',0,$role->getRoleId());
					endforeach;
					foreach($groups as $group):
						$checkGroup = array_key_exists($group->getCustomerGroupCode(), $params) ? $params[$group->getCustomerGroupCode()] : 0;
						if($checkGroup==1)
							$this->saveGroupConfig('enable_two_factor',1,$group->getCustomerGroupId());
						else
							$this->saveGroupConfig('enable_two_factor',0,$group->getCustomerGroupId());
					endforeach;
					$storeConfig = new Mage_Core_Model_Config();
					if($value2==1)
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/enable','1', 'default', 0);
					else
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/enable','0', 'default', 0);
					if($value3==1)
						$storeConfig ->saveConfig('miniOrange/twofactor/admin/rmd_enable','1', 'default', 0);
					else
						$storeConfig ->saveConfig('miniOrange/twofactor/admin/rmd_enable','0', 'default', 0);
					if($value4==1)
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/rmd_enable','1', 'default', 0);
					else
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/rmd_enable','0', 'default', 0);
					if($value5==1)
						$storeConfig ->saveConfig('miniOrange/twofactor/admin/inline_registration','1', 'default', 0);
					else
						$storeConfig ->saveConfig('miniOrange/twofactor/admin/inline_registration','0', 'default', 0);
					if($value6==1)
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/inline_registration','1', 'default', 0);
					else
						$storeConfig ->saveConfig('miniOrange/twofactor/customer/inline_registration','0', 'default', 0);
					$this->displayMessage('Settings Saved.',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
					$this->displayMessage('You will have to configure your mobile before you can enable two factor',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
				$this->displayMessage('You will have to register before you can enable two factor',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
    }
	
	public function configurePhoneAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();	
			$email = $helper->getConfig('email',$id);
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
				$content = $customer->send_otp_token($helper->getConfig('email',$id),$authType,$helper->getConfig('customerKey'),$helper->getConfig('apiKey'),$phone);
					$response = json_decode($content, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($response['txId']);
						$session->setPhone($phone);
						if(strcasecmp($authType, 'OTP_OVER_SMS') == 0)
							$this->displayMessage('An OTP has been sent to <b>'.$phone.'</b>. Please enter the one time passcode below.',"SUCCESS");
						else
							$this->displayMessage('You will receive a phone call on this number '.$phone.'. Please enter the one time passcode below.',"SUCCESS");
						$this->redirect("miniorange_2factor/adminhtml_index/index"); 
					}
					else{ 
						$this->displayMessage('Invalid Request!',"ERROR");
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
	
	public function verifyPhoneAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper->getConfig('email',$id);
			if($email!=""){
				$otp = $params['otp'];
				$submit = array_key_exists('submit', $params) ? $params['submit'] : "";
				if(strcasecmp($submit,"Validate") == 0){
					$content = $customer->validate_otp_token(null,null,$session->getLogintxId(),$otp,$helper->getConfig('customerKey'),$helper->getConfig('apiKey'));
					$response = json_decode($content, true);
					if(strcasecmp($response['status'], 'FAILED') != 0){
						$session->unsLogintxId();
						$this->saveConfig('miniorange_2factor_phone',$session->getPhone(),$id);
						$session->unsShowPhoneValidation();
						$session->unsPhone();
						if(!$session->getReconfigure()){
							$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
							$this->saveTwoFactorType($session->getTwoFactorType());
							$session->unsTwoFactorType();
							$session->setshowLoginSettings(1);
							$url = Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
							$this->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with Phone Validation.',"SUCCESS");
						}else{ 
							$this->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
							$session->setShowTwoFactorSettings(1);
							$session->unsReconfigure();
						}
						$session->unsTestPhone();
						$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
					else{	
							$session->unsLogintxId();
							$session->unsPhone();
							$this->displayMessage("Invalid OTP!","ERROR");
							$session->setShowTwoFactorSettings(1);
							$this->redirect("miniorange_2factor/adminhtml_index/index");
					}
				}
				else if(strcasecmp($submit,"Go Back") == 0){
					$session->unsLogintxId();
					$session->unsPhone();
					$session->unsReconfigure();
					$session->unsShowPhoneValidation();
					$session->unsTwoFactorType();
					$session->setShowTwoFactorSettings(1);
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else if(strcasecmp($submit,"Resend OTP") == 0){
					$content = $customer->send_otp_token($helper->getConfig('email',$id),$session->getTwoFactorType(),$helper->getConfig('customerKey'),$helper->getConfig('apiKey'),$session->getPhone());
					$response = json_decode($content, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setLogintxId($response['txId']);
						$phone = $session->getPhone();
						if(strcasecmp($session->getTwoFactorType(), 'SMS') != 0)
						$this->displayMessage('An OTP has been sent to <b>'.$phone.'</b>. Please enter the one time passcode below.',"SUCCESS");
						else
						$this->displayMessage('You will receive a phone call on this number '.$phone.'. Please enter the one time passcode below.',"SUCCESS");
						$this->redirect("miniorange_2factor/adminhtml_index/index"); 
					}
					else{ 
						$this->displayMessage('Invalid Request!',"ERROR");
						$this->redirect("miniorange_2factor/adminhtml_index/index"); 
					}
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
	
	/* SAVE TWO FACTOR METHOD */
	public function saveMethodAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$email = $helper->getConfig('email',$id);
			if($email!=""){
				$session->setShowTwoFactorSettings(1);
				if(strcasecmp($params['mo2f_selected_2factor_method'], 'MOBILE AUTHENTICATION') == 0 || strcasecmp($params['mo2f_selected_2factor_method'], 'SOFT TOKEN') == 0 
								|| strcasecmp($params['mo2f_selected_2factor_method'], 'PUSH NOTIFICATIONS') == 0 ){
					if($helper->getConfig('configure',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
							$session->setShowConfigureMobile(1);
							$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else if(strcasecmp($params['mo2f_selected_2factor_method'], 'SMS') == 0 || strcasecmp($params['mo2f_selected_2factor_method'], 'PHONE VERIFICATION') == 0){
					if($helper->getConfig('phone',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
							$session->setShowPhoneValidation(1);
							$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else if(strcasecmp($params['mo2f_selected_2factor_method'], 'GOOGLE AUTHENTICATOR') == 0){
					if($helper->getConfig('admin_ga',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
						$session->setShowGoogleAuthSetup(1);
						$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else if(strcasecmp($params['mo2f_selected_2factor_method'], 'KBA') == 0){
					if($helper->getConfig('admin_kba_Configured',$id)){
						$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
					}else{
						$session->setShowKBASetup(1);
						$session->setTwoFactorType($params['mo2f_selected_2factor_method']);
					}
				}else{
					$this->saveTwoFactorType($params['mo2f_selected_2factor_method']);
				}
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->displayMessage('You will have to register before you can enable 2factor',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		
	}
	
	public function saveKBAQuestionsAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$params = $this->getRequest()->getParams();
		if($helper->is_curl_installed()){
		$kba_q1 = $params[ 'mo2f_kbaquestion_1' ];
		$kba_a1 = trim( $params[ 'mo2f_kba_ans1' ] );
		$kba_q2 = $params[ 'mo2f_kbaquestion_2' ];
		$kba_a2 = trim( $params[ 'mo2f_kba_ans2' ] );
		$kba_q3 = trim( $params[ 'mo2f_kbaquestion_3' ] );
		$kba_a3 = trim( $params[ 'mo2f_kba_ans3' ] );
		$kba_reg_reponse = json_decode($customer->register_kba_details($helper->getConfig('email',$id),$kba_q1,$kba_a1,$kba_q2,$kba_a2,$kba_q3,$kba_a3,$helper->getConfig('customerKey'),$helper->getConfig('apiKey')),true);
			if($kba_reg_reponse['status'] == 'SUCCESS'){
				$session->unsShowKBASetup();
				$this->saveConfig('kba_Configured',1,$id);
				if(!$session->getReconfigure()){
					$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
					$this->saveTwoFactorType($session->getTwoFactorType());
					$session->unsTwoFactorType();
					$session->setshowLoginSettings(1);
					$url = Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
					$this->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with KBA.',"SUCCESS");
				}else{
					$this->displayMessage('You have Successfully Re-Configured KBA',"SUCCESS");
					$session->setShowTwoFactorSettings(1);
					$session->unsReconfigure();
				}
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->displayMessage('Error occured while saving your kba details. Please try again.',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function reconfigureKBAAction(){
		$session = $this->getSession();
		$session->setShowKBASetup(1);
		$session->setReconfigure(1);
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function reconfigurePhoneAction(){
		$params = $this->getRequest()->getParams();
		$session = $this->getSession();
		$session->setShowPhoneValidation(1);
		$session->setTestPhone($params["phone_reconfigure"]);
		$session->setReconfigure(1);
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function goBackTwoFactorAction(){
		$session = $this->getSession(); 
		$session->unsmo2fqrcode();
		$session->setmo2ftransactionId();
		$session->unsTestValidationScreen();
		$session->unsTwoFactorType();
		$session->unsVerifytxId();
		$session->unsTestKBA();
		$session->unsKBAQuestion1();
		$session->unsKBAQuestion2();
		$session->unsTestValidationScreen();
		$session->unsTestsms();
		$session->unsTestsofttoken();	
		$session->unsGAPhone();
		$session->unsmo2fGoogleAuth();
		$session->unsShowGoogleAuthSetup();
		$session->unsTestphoneverification();
		$session->unsShowTestMobileAuth();
		$session->unsShowTwoFactorSettings();
		$session->unsShowKBASetup();
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function testTwoFactorOTPAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$session = $this->getSession();
			$email = $helper->getConfig('email',$id);
			$customerKey = $helper->getConfig('customerKey');
			$apiKey = $helper->getConfig('apiKey');
			$soft_token = array_key_exists('soft_token', $params) ? $params['soft_token'] : '';
			$sms_otp = array_key_exists('sms_otp', $params) ? $params['sms_otp'] : '';
			$phonecall_otp = array_key_exists('phonecall_otp', $params) ? $params['phonecall_otp'] : '';
			$gaauth_otp = array_key_exists('gaauth_otp', $params) ? $params['gaauth_otp'] : '';
			$mo2f_answer_1 = array_key_exists('mo2f_answer_1', $params) ? $params['mo2f_answer_1'] : '';
			$mo2f_answer_2 = array_key_exists('mo2f_answer_2', $params) ? $params['mo2f_answer_2'] : '';
				if($soft_token!=''){
					$content = $customer->validate_otp_token('SOFT TOKEN',$email, null, $soft_token, $customerKey, $apiKey);
					$response = json_decode($content, true);
					if(strcasecmp($response['status'], 'FAILED') != 0){
						$session->unsTestValidationScreen();
						$session->unsTestsofttoken();
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
					}
					else{	
						$session->unsTestValidationScreen();
						$session->unsTestsofttoken();
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
					}
				}else if($sms_otp!=''){
					$content = $customer->validate_otp_token(null,null, $session->getVerifytxId(),$sms_otp, $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$session->unsVerifytxId();
							$session->unsTestValidationScreen();
							$session->unsTestsms();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
						}
						else{
							$session->unsVerifytxId();
							$session->unsTestValidationScreen();
							$session->unsTestsms();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
						}
				}else if($phonecall_otp!=''){
					$content = $customer->validate_otp_token(null,null, $session->getVerifytxId(),$phonecall_otp, $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$session->unsVerifytxId();
							$session->unsTestValidationScreen();
							$session->unsTestphoneverification();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
						}
						else{
							$session->unsVerifytxId();
							$session->unsTestValidationScreen();
							$session->unsTestphoneverification();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
						}
				}else if($gaauth_otp!=''){
					$content = $customer->validate_otp_token('GOOGLE AUTHENTICATOR',$email, null, $gaauth_otp, $customerKey, $apiKey);
						$response = json_decode($content, true);
						if(strcasecmp($response['status'], 'FAILED') != 0){
							$session->unsTestValidationScreen();
							$session->unsTestGoogleAuth();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
						}
						else{
							$session->unsTestValidationScreen();
							$session->unsTestGoogleAuth();
							$session->setShowTwoFactorSettings(1);
							$this->displayMessage('Transaction Failed. Invalid OTP.',"ERROR");
						}
				}else if($mo2f_answer_1!="" && $mo2f_answer_2!=""){
					$otptoken = array();
					$otptoken[0] = $session->getKBAQuestion1();
					$otptoken[1] = trim($mo2f_answer_1);
					$otptoken[2] = $session->getKBAQuestion2();
					$otptoken[3] = trim($mo2f_answer_2);
					$content = $customer->validate_otp_token('KBA',null, $session->getLogintxId(),$otptoken, $customerKey, $apiKey);
					$response = json_decode($content, true);
					if(strcasecmp($response['status'], 'FAILED') != 0){
						$session->unsTestValidationScreen();
						$session->unsTestKBA();
						$session->unsVerifytxId();
						$session->unsKBAQuestion1();
						$session->unsKBAQuestion2();
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
					}else{
						$session->unsTestValidationScreen();
						$session->unsTestKBA();
						$session->unsVerifytxId();
						$session->unsKBAQuestion1();
						$session->unsKBAQuestion2();
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('Transaction Failed. Invalid Answers.',"ERROR");
					}
				}else{
						$this->displayMessage('Invalid Request!',"ERROR");
				}
				$this->redirect("miniorange_2factor/adminhtml_index/index");
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function testTwoFactorAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$params = $this->getRequest()->getParams();
			$session = $session;
			$email = $helper->getConfig('email',$id);
			$customerKey = $helper->getConfig('customerKey');
			$apiKey = $helper->getConfig('apiKey');
			switch($params['test_2factor']){
				case "MOBILE AUTHENTICATION":
					$sendotp = $customer->send_otp_token($email,'MOBILE AUTHENTICATION', $customerKey, $apiKey);
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
					$sendotp = $customer->send_otp_token($email,'SMS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setVerifytxId($status['txId']);
						$session->setTestsms(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('An OTP has been sent to <b>'.$helper->getConfig('phone',$id).'</b>. Please enter the one time passcode below.',"SUCCESS");
					}
					else{	$this->displayMessage("Invalid request",'ERROR'); }
					break;
				
				case "PHONE VERIFICATION":
					$sendotp = $customer->send_otp_token($email,'PHONE VERIFICATION', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setVerifytxId($status['txId']);
						$session->setTestphoneverification(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('You will get a call on <b>'.$helper->getConfig('phone',$id).'</b>. Please enter the one time passcode below.',"SUCCESS");
					}
					else{ $this->displayMessage("Invalid request",'ERROR'); }
					break;
				
				case "PUSH NOTIFICATIONS":
					$sendotp = $customer->send_otp_token($email,'PUSH NOTIFICATIONS', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setmo2ftransactionId($status['txId']);
						$session->setTestpushnotification(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
					}
					else{	$this->displayMessage("Invalid request",'ERROR'); }
					break;
				
				case "OUT OF BAND EMAIL":
					$sendotp = $customer->send_otp_token($email,'OUT OF BAND EMAIL', $customerKey, $apiKey);
					$status = json_decode($sendotp, true);
					if(json_last_error() == JSON_ERROR_NONE){
						$session->setmo2ftransactionId($status['txId']);
						$session->setTestoutofband(1);
						$session->setTestValidationScreen(1);
						$session->setShowTwoFactorSettings(1);
						$this->displayMessage('A mail has been sent to <b>'.$helper->getConfig('email',$id).'</b>. Please Accept or Deny the transaction.',"SUCCESS");
					}
					else{ $this->displayMessage("Invalid request",'ERROR'); }
					break;
					
				case "GOOGLE AUTHENTICATOR":
					$session->setTestValidationScreen(1);
					$session->setTestGoogleAuth(1);
					$session->setShowTwoFactorSettings(1);
					break;
				
				case "KBA":
					$sendotp = $customer->send_otp_token($email,'KBA', $customerKey, $apiKey);
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
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function supportSubmitAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			$user = $session->getUser();
			$customer->submit_contact_us($params['query_email'], $params['query_phone'], $params['query'], $user);
			$this->displayMessage('Your query has been sent. We will get in touch with you soon',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	
	public function transactionSuccessAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$session->unsShowQR();
			$session->unsmo2fqrcode();
			$session->unsmo2ftransactionId();
			if($session->getShowTestMobileAuth() || $session->getTestpushnotification() 
					|| $session->getTestoutofband() ){
				$session->unsTestValidationScreen();			
				$session->unsShowTestMobileAuth();	
				$session->unsTestpushnotification();
				$session->unsTestoutofband();	
				$session->setShowTwoFactorSettings(1);
				$this->displayMessage('Transaction Validated. Test Successful.',"SUCCESS");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else if($session->getReconfigure()){
				$session->setShowTwoFactorSettings(1);
				$session->unsReconfigure();
				$this->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
				$this->saveConfig('miniorange_2factor_configured',1,$id);
				$session->unsShowConfigureMobile();
				$session->setshowLoginSettings(1);
				$this->saveTwoFactorType($session->getTwoFactorType());
				$session->unsTwoFactorType();
				$url = Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
				$this->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with mobile authentication.',"SUCCESS");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function showQRCodeAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			//$admin = $session->getUser();
			$id = $this->getId();
			$email = $helper->getConfig('email',$id);						
			$submit = array_key_exists('submit', $params) ? $params['submit'] : "";
			if( strcasecmp($submit, 'Go Back') != 0  ){
				if($email!=""){
					if($params['reconfigure_mobile']){
						$session->setReconfigure(1);
					}
					else{
						$this->saveConfig('miniorange_2factor_downloaded_app',$params['showDownload'],$id);
					}
					$this->mo2f_get_qr_code_for_mobile($email,$id);
					$session->setShowQR(1);
					$session->setShowTwoFactorSettings(1);
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
				else{
					$this->displayMessage('You will have to register before configuring your mobile',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}
			else{
					
					$session->unsTwoFactorType();
					$session->unsShowConfigureMobile();
					$session->unsmo2fqrcode();
					$session->unsmo2ftransactionId();
					$session->unsShowQR();
					$session->setShowTwoFactorSettings(1);
					$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	
	public function resendValidationOTPAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			//$admin = $session->getUser();
			$id = $this->getId();
			$email =$session->getaddAdmin();
			$content = json_decode($customer->send_otp_token($email,'EMAIL',$helper->getdefaultCustomerKey(),$helper->getdefaultApiKey()), true); //send otp for verification
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				$session->setMytextid($content['txId']);
				$session->setShowOTP(1);
				$this->displayMessage('OTP has been sent to your Email. Please check your mail and enter the otp below.',"SUCCESS");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
			else{
				$this->displayMessage('Error sending OTP. Please try again!',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
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
		$this->displayMessage('Timed Out. Please Try Again.',"ERROR");
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function cancelValidationAction(){
		//$admin = $session->getUser();
		$session = $this->getSession();
		$id = $this->getId();
		$session->unsShowOTP();
		$session->unsaddAdmin();
		$session->unsaddPhone();
		$session->unsaddPass();
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	/*Google Authenticator*/
	public function selectGAPhoneAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$params = $this->getRequest()->getParams();
		$email = $helper->getConfig('email',$id);
		$session->setShowTwoFactorSettings(1);
		$content = json_decode($customer->mo2f_google_auth_service($email,$helper->getConfig('customerKey'),$helper->getConfig('apiKey')),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$mo2f_google_auth = array();
			$mo2f_google_auth['ga_qrCode'] = $content['qrCodeData'];
			$mo2f_google_auth['ga_secret'] = $content['secret'];
			$session->setmo2fGoogleAuth($mo2f_google_auth);
			$session->setGAPhone($params['mo2f_app_type_radio']);
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('There was an error proccessing your request. Try Again!',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	public function validateGATokenAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$params = $this->getRequest()->getParams();
		$mo2fdata = Mage::getSingleton('admin/session')->getmo2fGoogleAuth();
		$email = $helper->getConfig('email',$id);
		$content = json_decode($customer->mo2f_validate_google_auth($email,$params['google_token'],$mo2fdata['ga_secret'],$helper->getConfig('customerKey'),$helper->getConfig('apiKey')),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$this->saveConfig('admin_ga_configured',1,$id);
			$session->unsGAPhone();
			$session->unsmo2fGoogleAuth();
			$session->unsShowGoogleAuthSetup();
			if(!$session->getReconfigure()){
				$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
				$this->saveTwoFactorType($session->getTwoFactorType());
				$session->unsTwoFactorType();
				$session->setshowLoginSettings(1);
				$url = Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
				$this->displayMessage('You are Done. You can <a href="'.$url.'">log out</a> and log back in with Google Authenticator.',"SUCCESS");	
			}else{
				$this->displayMessage('You have Successfully Re-Configured your device',"SUCCESS");
				$session->setShowTwoFactorSettings(1);
				$session->unsReconfigure();
			}
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}else{
			$this->displayMessage('Invalid OTP! Please Try Again',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function reconfigureGAAction(){
		$session = $this->getSession();
		$session->setShowGoogleAuthSetup(1);
		$session->setReconfigure(1);
		$this->redirect("miniorange_2factor/adminhtml_index/index");
	}
	
	private function getSession(){
		return  Mage::getSingleton('admin/session');
	}
	
	private function getCoreSession(){
		return  Mage::getSingleton('core/session');
	}
	
	private function getId(){
		return $this->getSession()->getUser()->getUserId();
	}
	
	private function getHelper1(){
		return Mage::helper($this->_helper1);
	}
	
	private function getHelper2(){
		return Mage::helper($this->_helper2);
	}
	
	private function checkEndUser($email,$phone){
		$helper = $this->getHelper1();
		$admin = $this->getHelper2();
		$session = $this->getSession();
		$customerAdmin = $session->getUser();
		$id = $customerAdmin->getId();
		$check_user = json_decode($admin->mo_check_user_already_exist($email,$helper->getConfig('customerKey'),$helper->getConfig('apiKey')),true);
		if(json_last_error() == JSON_ERROR_NONE){	
			if(strcasecmp($check_user['status'], 'USER_FOUND') == 0){
					$this->saveConfig('miniorange_2factor_email',$email,$id);
					$this->saveConfig('miniorange_2factor_phone',$phone,$id);
					$session->unsaddAdmin();
					$session->unsaddPhone();
					$session->unsaddPass();
					$session->unsShowOTP();
					$this->saveTwoFactorType("OUT OF BAND EMAIL");
					$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
					$this->displayMessage('Registration Successful. EMAIL VERIFICATION has been set as your second factor. You can change your second factor below.',"SUCCESS");
					$this->redirect("miniorange_2factor/adminhtml_index/index");			
			}else if(strcasecmp($check_user['status'], 'USER_NOT_FOUND') == 0){
					$content = json_decode($admin->mo_create_user($email,$helper->getConfig('customerKey'),$helper->getConfig('apiKey'),$customerAdmin), true);
						if(strcasecmp($content['status'], 'SUCCESS') == 0) {
							$this->saveConfig('miniorange_2factor_email',$email,$id);
							$this->saveConfig('miniorange_2factor_phone',$phone,$id);
							$session->unsaddAdmin();
							$session->unsaddPhone();
							$session->unsaddPass();
							$session->unsShowOTP();
							$this->saveTwoFactorType("OUT OF BAND EMAIL");
							$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
							$this->displayMessage('Registration Successful. EMAIL VERIFICATION has been set as your second factor. You can change your second factor below.',"SUCCESS");
							$this->redirect("miniorange_2factor/adminhtml_index/index");
						}else{
							$this->displayMessage('There was an Error while creating End User!',"ERROR");
							$this->redirect("miniorange_2factor/adminhtml_index/index");
						}
				}else{
						$this->displayMessage('The User already exists under another Admin.',"ERROR");
						$this->redirect("miniorange_2factor/adminhtml_index/index");		
				}
		}else{
				$this->displayMessage('There was an unknown error! Contact Admin.',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");				
		}
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
				Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	private function saveRoleConfig($url,$value,$id){
		$data = array($url=>$value);
		$model = Mage::getModel('admin/role')->load($id)->addData($data);
		try {
			$model->setId($id)->save();
		} catch (Exception $e){
			Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	private function saveGroupConfig($url,$value,$id){
		$data = array($url=>$value);
		$model = Mage::getModel('customer/group')->load($id)->addData($data);
		try {
			$model->setId($id)->save();
		} catch (Exception $e){
			Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
  
	private function displayMessage($message,$type){
		$this->getCoreSession()->getMessages(true);
		$this->getCoreSession()->unsSuccessMessage();
		$this->getCoreSession()->unsErrorMessage();
		if(strcasecmp($type,"SUCCESS") == 0)
			$this->getCoreSession()->setSuccessMessage($message);
		else
			$this->getCoreSession()->setErrorMessage($message);
	}
	
	private function mo2f_get_qr_code_for_mobile($email,$id){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			$content = $customer->register_mobile($email,$id);
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) {
				$session->setmo2fqrcode($response['qrCode']);
				$session->setmo2ftransactionId($response['txId']);
			}
		}
		else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	private function forgotPass($email){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		$content = json_decode($customer->forgot_password($email,$helper->getConfig('customerKey'),$helper->getConfig('apiKey')), true); 
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$this->displayMessage('Your new password has been generated and sent to '.$email.'.',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('Sorry we encountered an error while reseting your password.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	private function saveTwoFactorType($authType,$email="",$customerKey="",$apikey=""){
		$id = $this->getId();
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$email = !empty($email) ? $email :  $helper->getConfig('email',$id);
		$customerKey = !empty($customerKey) ? $customerKey : $helper->getConfig('customerKey');
		$apikey = !empty($apikey) ? $apikey : $helper->getConfig('apiKey');
		//$admin = $session->getUser();

		$content = $customer->mo2f_update_userinfo($email,$authType,$helper->getConfig('phone',$id),$customerKey,$apikey);
		$response = json_decode($content, true); 

		if(strcasecmp($response['status'], 'SUCCESS') == 0) {
			$this->saveConfig('miniorange_2factor_type',$authType,$id);
			$this->displayMessage($authType." has been set as your Two Factor.","SUCCESS");
		}
		else{
			$this->displayMessage('There was an ERROR while setting your Authentication Type. Please Choose One from the list below:',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	
	//added for registration
	public function sendOTPPhoneAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		if(array_key_exists('phone',$params)){
			$phone = $params['phone'];
			$storeConfig ->saveConfig('miniorange/twofactor/phone',$phone,'default', 0);
			$content = json_decode($customer->send_otp_token(null,'OTP_OVER_SMS',$datahelper->getdefaultCustomerKey(),$datahelper->getdefaultApiKey(), $phone), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				$this->displayMessage(' A one time passcode is sent to ' . $phone . '. Please enter the otp here to verify your email.',"SUCCESS");
				$session->setRegTxID($content['txId']);
				$session->setaddPhone($phone);
				$session->setmo2fstatus('MO_OTP_PHONE_VALIDATE');
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->displayMessage('There was an error in sending SMS. Please click on Resend OTP to try again.',"ERROR");
				$session->setmo2fstatus('MO_OTP_DELIVERED_FAILURE');
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}else{
			$this->displayMessage('Please Enter a Phone Number.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	public function registerNewUserAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		if($datahelper->is_curl_installed()){
			
			$email = $params['email'];
			$password = $params['password'];
			$confirm = $params['confirmPassword'];
			$submit = $params['submit'];
					
			if(strcasecmp($submit,"Register") == 0){
				if($password==$confirm){ 
					$session->setaddAdmin($email);
					$session->setaddPhone($phone);
					$session->setaddPass($password);				
					$content = $customer->check_customer($email);
					$content = json_decode($content, true);
					if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){ 
						$content = json_decode($customer->send_otp_token($email,'EMAIL',$datahelper->getdefaultCustomerKey(),$datahelper->getdefaultApiKey()), true);
						if(strcasecmp($content['status'], 'SUCCESS') == 0) {
							$session->setRegTxID($content['txId']);
							$session->setmo2fstatus('MO_OTP_EMAIL_VALIDATE');
							$this->displayMessage('A one time passcode is sent to '. $email .'. Please enter the otp here to verify your email.',"SUCCESS");
							$this->redirect("miniorange_2factor/adminhtml_index/index");
						}else{
							
							$session->setmo2fstatus('MO_OTP_DELIVERED_FAILURE');
							$this->displayMessage('There was an error in sending email. Please verify your email and try again.',"ERROR");
							$this->redirect("miniorange_2factor/adminhtml_index/index");
						}
					}else{
						
						$this->get_current_customer($email,$password);
						
					}
				}else{
					
					$this->displayMessage('Passwords do not match',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}else if(strcasecmp($submit,"Forgot Password?") == 0){
				$this->forgotPass($email);
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	private function get_current_customer($email,$password){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$storeConfig = new Mage_Core_Model_Config();
		$content = $customer->get_customer_key($email,$password);
		$customerKey = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) {
			$this->saveConfig('miniorange_2factor_email',$email,$id);
			$storeConfig ->saveConfig('miniOrange/twofactor/customerKey',$customerKey['id'], 'default', 0);
			$storeConfig ->saveConfig('miniOrange/twofactor/apiKey',$customerKey['apiKey'], 'default', 0);
			$storeConfig ->saveConfig('miniOrange/twofactor/twofactorToken',$customerKey['token'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/appSecret',$customerKey['appSecret'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/mainAdmin',$id,'default', 0);
			$session->unsmo2fstatus();
			$this->saveTwoFactorType("OUT OF BAND EMAIL");
			$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
			$this->displayMessage('Your account has been retrieved successfully. EMAIL VERIFICATION has been set as your second factor.',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
		else{
			$this->displayMessage('You already have an account with miniOrange. Please enter a valid password.',"ERROR");
			$session->setmo2fstatus('MO_VERIFY_CUSTOMER');
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	private function create_customer($email,$phone,$pass){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		$customerKey = json_decode( $customer->create_customer($email,$phone,$pass), true );
		if( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0 ) {
				$this->get_current_customer($email,$pass);
		} else if( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
			$id = $this->getId();
			$this->saveConfig('miniorange_2factor_email',$email,$id);
			$this->saveConfig('miniorange_2factor_Admin_enable',1,$id);
			$storeConfig->saveConfig('miniOrange/twofactor/customerKey',$customerKey['id'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/apiKey',$customerKey['apiKey'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/twofactorToken',$customerKey['token'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/appSecret',$customerKey['appSecret'], 'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/mainAdmin',$id,'default', 0);
			$storeConfig->saveConfig('miniOrange/twofactor/admin/inline_registration','1', 'default', 0);
			$roles = Mage::getModel('admin/roles')->getCollection();
			foreach($roles as $role):
				$this->saveRoleConfig('enable_two_factor',1,$role->getRoleId());
			endforeach;
			$session->unsaddadmin();
			$session->unsaddPhone();
			$session->unsaddPass();
			$session->unsRegTxID();
			$session->unsmo2fstatus();
			$session->setShowTwoFactorSettings(1);
			$this->saveTwoFactorType("OUT OF BAND EMAIL",$email,$customerKey['id'],$customerKey['apiKey']);
			$this->displayMessage('Thank you for registering with miniorange.EMAIL VERIFICATION has been set as your second factor.',"SUCCESS");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
	
	public function validateNewAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$id = $this->getId();
		$submit = $params['submit'];
		$storeConfig = new Mage_Core_Model_Config();
		if($datahelper->is_curl_installed()){
			if(array_key_exists('otp',$params) && strcasecmp($submit,"Validate OTP")==0){
				$otp = $params['otp'];
				$transactionId  =  $session->getRegTxID();
				$email = $session->getaddAdmin();
				$phone = $session->getaddPhone();
				$pass = $session->getaddPass();
				$content = json_decode($customer->validate_otp_token($authType,null, $transactionId, $otp, $datahelper->getdefaultCustomerKey(), $datahelper->getdefaultApiKey()),true);
				if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					$this->create_customer($email,$phone,$pass);
				}else{
					$this->displayMessage('Invalid one time passcode. Please enter a valid otp.',"ERROR");
					$this->redirect("miniorange_2factor/adminhtml_index/index");
				}
			}else if(strcasecmp($submit,"Back")==0){
				$session->unsaddAdmin();
				$session->unsaddPass();
				$session->unsmo2fstatus();
				$session->unsRegTxID();
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}else{
				$this->displayMessage('Please enter a value in otp field',"ERROR");
				$this->redirect("miniorange_2factor/adminhtml_index/index");
			}
		}else{
			$this->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("miniorange_2factor/adminhtml_index/index");
		}
	}
		
}