<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/2/2018
 *
 */


class Lion extends Sierra
{

	public function getSelfRegistrationFields()
	{
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text',  'label'=>'First Name',   'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'lastName',  'type'=>'text',  'label'=>'Last Name',    'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'email',     'type'=>'email', 'label'=>'E-Mail',       'description'=>'E-Mail (for confirmation, notices and newsletters)', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'phone',     'type'=>'text',  'label'=>'Phone Number', 'description'=>'Phone Number', 'maxLength' => 12, 'required' => true);
		$fields[] = array('property'=>'address',   'type'=>'text',  'label'=>'Address',      'description'=>'Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city',      'type'=>'text',  'label'=>'City',         'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state',     'type'=>'text',  'label'=>'State',        'description'=>'State', 'maxLength' => 32, 'required' => true);
		//City State should be combined into one field when submitting registration
		$fields[] = array('property'=>'zip',       'type'=>'text',  'label'=>'Zip Code',     'description'=>'Zip Code', 'maxLength' => 5, 'required' => true);
		return $fields;
	}

	function selfRegister(){
		global $logger;
		global $library;

		$firstName = trim($_REQUEST['firstName']);
//		$middleName = trim($_REQUEST['middleName']); // Not used by LION
		$lastName = trim($_REQUEST['lastName']);
		$address = trim($_REQUEST['address']);
		$city = trim($_REQUEST['city']);
		$state = trim($_REQUEST['state']);
		$zip = trim($_REQUEST['zip']);
		$email = trim($_REQUEST['email']);

		$curl_url = $this->getVendorOpacUrl() . "/selfreg~S" . $this->getLibraryScope();
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$curl_connection = $this->_curl_connect($curl_url);

//		$post_data['nfirst'] = $middleName ? $firstName.' '.$middleName : $firstName; // add middle name onto first name;
		$post_data['nfirst'] =  $firstName;
		$post_data['nlast'] = $lastName;
		$post_data['stre_aaddress'] = $address;
		if ($this->combineCityStateZipInSelfRegistration()){
			$post_data['city_aaddress'] = "$city, $state $zip";
			$post_data['tzip1'] = $zip;  // tzip1 is unique to LION self-reg (so far)
		}else{
			$post_data['city_aaddress'] = "$city";
			$post_data['stat_aaddress'] = "$state";
			$post_data['post_aaddress'] = "$zip";
		}

		$post_data['zemailaddr'] = $email;
		if (isset($_REQUEST['phone'])){
			$phone = trim($_REQUEST['phone']);
			$post_data['tphone1'] = $phone;
		}
		if (isset($_REQUEST['birthDate'])){
			$post_data['F051birthdate'] = $_REQUEST['birthDate'];
		}
		if (isset($_REQUEST['universityID'])){
			$post_data['universityID'] = $_REQUEST['universityID'];
		}

		if ($library->selfRegistrationTemplate && $library->selfRegistrationTemplate != 'default'){
			$post_data['TemplateName'] = $library->selfRegistrationTemplate;
		}


		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);
//		$info = curl_getinfo($curl_connection); // for debug only

		$this->_close_curl();

		//Parse the library card number from the response
		if (preg_match('%your temporary account number, which is:.*?(\d+)</h1>%si', $sresult, $matches)) {
			$barcode = $matches[1];
			return array('success' => true, 'barcode' => $barcode);
		} else {
			return array('success' => false, 'barcode' => '');
		}
	}

}