<?php
/** miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
    Copyright (C) 2015  miniOrange

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>
* @package 		miniOrange OAuth
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
/**
This library is miniOrange Authentication Service. 
Contains Request Calls to Customer service.
**/
class MiniOrange_2factor_Helper_mo2fUtility extends Mage_Core_Helper_Abstract{
	
	public $email;
	public $phone;
	public $hostname = "https://auth.miniorange.com";
	public $pluginName = 'Magento 2 Factor Authentication Plugin';
	
	function getHostURl(){
		return $this->hostname;
	}
	
	function check_customer($email){
		$url 	= $this->hostname . '/moas/rest/customer/check-if-exists';
		$ch 	= curl_init( $url );
		
		$fields = array(
			'email' 	=> $email,
		);
		$field_string = json_encode( $fields );

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Basic' ) );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec( $ch );
		
		if( curl_errno( $ch ) ){
			echo 'Request Error:' . curl_error( $ch );
			exit();
		}
		curl_close( $ch );
		
		return $content;
	}
	
	
	function send_otp_token($email,$authType,$defaultCustomerKey,$defaultApiKey){
		$url = $this->hostname . '/moas/api/auth/challenge';
		$ch = curl_init($url);
		$customerKey =  $defaultCustomerKey;
		$apiKey =  $defaultApiKey;

		$currentTimeInMillis = round(microtime(true) * 1000);

		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue = hash("sha512", $stringToHash);

		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . $currentTimeInMillis;
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = '';
		if( $authType == 'EMAIL' ) {
			$fields = array(
				'customerKey' => $customerKey,
				'email' => $email,
				'authType' => $authType,
				'transactionName' => $this->pluginName,
			);
		}else{
			$fields = array(
				'customerKey' => $customerKey,
				'username' => $email,
				'authType' => $authType,
				'transactionName' =>  $this->pluginName,
			);
		}
		
		$field_string = json_encode($fields);

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader,
											$timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec($ch);

		if(curl_errno($ch)){
			echo 'Request Error:' . curl_error($ch);
		   exit();
		}
		curl_close($ch);
		return $content;
	}
	
	
	function validate_otp_token($authType,$username,$transactionId,$otpToken,$defaultCustomerKey,$defaultApiKey){
		$url = $this->hostname . '/moas/api/auth/validate';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey =  $defaultCustomerKey;
	
		/* The customer API Key provided to you */
		$apiKey = $defaultApiKey;
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . $currentTimeInMillis;
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = '';
		if( $authType == 'SOFT TOKEN' ) {
			/*check for soft token*/
			$fields = array(
				'customerKey' => $customerKey,
				'username' => $username,
				'token' => $otpToken,
				'authType' => $authType
			);
		}else{
			//*check for otp over sms/email
			$fields = array(
				'txId' => $transactionId,
				'token' => $otpToken,
			);
		}
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, 
											$timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			echo 'Request Error:' . curl_error($ch);
		   exit();
		}
		curl_close($ch);
		return $content;
	}
	
	function create_customer($email,$phone,$password){
		$url = $this->hostname . '/moas/rest/customer/add';
		$ch  = curl_init($url);

		
		$fields = array(
			'companyName' => $_SERVER['SERVER_NAME'],
			'areaOfInterest' => $this->pluginName,
			'email' => $email,
			'phone' => $phone,
			'password' => $password
		);
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'charset: UTF - 8',
			'Authorization: Basic'
			));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			echo 'Request Error:' . curl_error($ch);
		   exit();
		}
		

		curl_close($ch);
		return $content;
	}
	
	function get_customer_key($email,$password) {
		$url = $this->hostname .  "/moas/rest/customer/key";
		$ch = curl_init($url);
		
		$fields = array(
			'email' => $email,
			'password' => $password
		);
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'charset: UTF - 8',
			'Authorization: Basic'
			));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec($ch);
		if(curl_errno($ch)){
			echo 'Request Error:' . curl_error($ch);
		   exit();
		}
		curl_close($ch);

		return $content;
	}
	
	
	function submit_contact_us( $q_email, $q_phone, $query, $user) {
		$url = $this->hostname .  "/moas/rest/customer/contact-us";
		$ch = curl_init($url);
		$query = '[Magento 2 Factor Authentication Plugin]: ' . $query;
		$fields = array(
			'firstName'			=> $user->getFirstname(),
			'lastName'	 		=> $user->getLastname(),
			'company' 			=> $_SERVER['SERVER_NAME'],
			'email' 			=> $q_email,
			'phone'				=> $q_phone,
			'query'				=> $query
		);
		$field_string = json_encode( $fields );
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'charset: UTF-8', 'Authorization: Basic' ) );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec( $ch );
		
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);

		return true;
	}
	
	function adminExists($username){
		$adminuser = Mage::getModel('admin/user');
		$adminuser->loadByUsername($username);
		if ($adminuser->getId()){
			return true;
		}
		else{
			return false;
		}
	}
	
	function getAdmin($username){
		$adminuser = Mage::getModel('admin/user');
		$adminuser->loadByUsername($username);
		if ($adminuser->getId()){
			return $adminuser;
		}
		else{
			return;
		}
	}
	
	function register_mobile($useremail,$id){
		$url = $this->hostname . '/moas/api/auth/register-mobile';
		$ch = curl_init($url);
		$email = $useremail;
		
		/* The customer Key provided to you */
		$customerKey = $this->getConfig('customerKey',$id);
	
		/* The customer API Key provided to you */
		$apiKey = $this->getConfig('apiKey',$id);
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . $currentTimeInMillis;
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'username' => $email
		);
		
		$field_string = json_encode($fields);

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, $timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		$content = curl_exec($ch);

		if(curl_errno($ch)){
			echo 'Request Error:' . curl_error($ch);
		   exit();
		}
		curl_close($ch);
		return $content;
	}
	
	
	function forgot_password($email,$defaultCustomerKey,$defaultApiKey){
		$url = $this->hostname . '/moas/rest/customer/password-reset';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = $defaultCustomerKey;
	
		/* The customer API Key provided to you */
		$apiKey = $defaultApiKey;
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . $currentTimeInMillis;
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = '';
	
			//*check for otp over sms/email
			$fields = array(
				'email' => $email
			);
		
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, 
											$timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);
		return $content;
	}
	
	/*Function to extract config stored in the database*/
	function getConfig($config,$id){
		switch($config){
			case 'isEnabled':
				$result = Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_Admin_enable');
				break;
			case 'email':
				$result =  Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_email');
				break;
			case 'pass':
				$result =  Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_pass');
				break;
			case 'customerKey':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_customer_key');
				break;
			case 'apiKey':
				$result =  Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_api_key');
				break;
			case 'apiToken':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_token');
				break;
			case 'otp':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_otp');
				break;	
			case 'qrcode':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_qr');
				break;
			case 'configure':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_show_configure');
				break;
			case 'validated':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_validated');
				break;
			case 'login':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_login');
				break;
			case 'mainAdmin':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_admin_registered');
				break;
			case 'downloaded':
				$result =   Mage::getModel('admin/user')->load($id)->getData('miniorange_2factor_downloaded_app');
				break;
			default:
				return;
				break;
		}
			return $result;
	}
	
	/*Function to show his partial registered email to user*/
	function showEmail($id){
			$email = $this->getConfig('email',$id);
			$emailsize = strlen($email);
			$partialemail = substr($email,0,1);
			$temp = strrpos($email,"@");
			$endemail = substr($email,$temp-1,$emailsize);
			for($i=1;$i<$temp;$i++){
				$partialemail = $partialemail . 'x';
			}
			$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/*Function to check if cURL is enabled*/
	function is_curl_installed() {
		if  (in_array  ('curl', get_loaded_extensions())) {
			return 1;
		} else 
			return 0;
	}

}?>