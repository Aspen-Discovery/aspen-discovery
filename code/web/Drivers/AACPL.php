<?php
/**
 *
 * Copyright (C) Marmot Library Network 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once ROOT_DIR . '/Drivers/SirsiDynixROA.php';

class AACPL extends SirsiDynixROA {

	function translateFineMessageType($code){
		switch ($code){

			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtoupper($locationCode);
		$locationMap = array(

		);
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : "Unknown" ;
	}
	public function translateCollection($collectionCode){
		$collectionCode = strtoupper($collectionCode);
		$collectionMap = array(

		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : "Unknown $collectionCode";
	}
	public function translateStatus($statusCode){
		$statusCode = strtolower($statusCode);
		$statusMap = array(

		);
		return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown (' . $statusCode . ')';
	}
	public function getLocationMapLink($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array();
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : '' ;
	}

	public function getLibraryHours($locationId, $timeToCheck){
		return null;
	}

	public function getSelfRegistrationFields(){
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 25, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 25, 'required' => false);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 60, 'required' => true);
		$fields[] = array('property'=>'suffix', 'type'=>'text', 'label'=>'Suffix', 'description'=>'Your suffix', 'maxLength' => 15, 'required' => false);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Address', 'description'=>'Address', 'maxLength' => 128, 'required' => true);
//		$fields[] = array('property'=>'address2', 'type'=>'text', 'label'=>'Apartment Number', 'description'=>'Apartment Number', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'email', 'type'=>'email', 'label'=>'E-Mail', 'description'=>'E-Mail', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'phone', 'type'=>'text', 'label'=>'Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'pin', 'type'=>'pin', 'label'=>'Pin (Save this number)', 'description'=>'Pin (Save this number)', 'maxLength' => 25, 'required' => true);
		$fields[] = array('property'=>'pin1', 'type'=>'pin', 'label'=>'Re-enter Pin (Save this number)', 'description'=>'Pin (Save this number)', 'maxLength' => 25, 'required' => true);

		$location = new Location();
		$location->libraryId = $library->libraryId;
		$location->validHoldPickupBranch = 1;

		if ($location->find()) {
			$pickupLocations = array();
			while($location->fetch()) {
				$pickupLocations[$location->code] = $location->displayName;
			}
			sort($pickupLocations);
			$fields[] = array('property' => 'pickupLocation', 'type' => 'enum', 'label' => 'Preferred Library', 'description' => 'Please choose the Library location you would prefer to use', 'values' => $pickupLocations, 'required' => true);
		}


		return $fields;
	}


//	function selfRegister(){
//		global $logger;
//
//		//Setup Curl
//		$header=array();
//		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
//		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
//		$header[] = "Cache-Control: max-age=0";
//		$header[] = "Connection: keep-alive";
//		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
//		$header[] = "Accept-Language: en-us,en;q=0.5";
//		$cookie = tempnam ("/tmp", "CURLCOOKIE");
//
//		//Start at My Account Page
//		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$this->selfRegProfile}&menu=account";
//		$curl_connection = curl_init($curl_url);
//		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
//		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
//		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
//		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
//		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
//		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
//		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
//		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
//		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
//		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
//		curl_setopt($curl_connection, CURLOPT_HEADER, false);
//		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
//		$sresult = curl_exec($curl_connection);
//		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);
//
//		//Extract the session id from the requestcopy javascript on the page
//		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
//			$sessionId = $matches[1];
//		} else {
//			PEAR_Singleton::raiseError('Could not load session information from page.');
//		}
//
//		//Login by posting username and password
//		$post_data = array(
//      'aspect' => 'overview',
//      'button' => 'New User',
//      'login_prompt' => 'true',
//      'menu' => 'account',
//			'newuser_prompt' => 'true',
//      'profile' => $this->selfRegProfile,
//      'ri' => '',
//      'sec1' => '',
//      'sec2' => '',
//      'session' => $sessionId,
//		);
//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
//		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
//		curl_setopt($curl_connection, CURLOPT_POST, true);
//		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
//		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
//		$sresult = curl_exec($curl_connection);
//
//		$firstName = strip_tags($_REQUEST['firstname']);
//		$lastName = strip_tags($_REQUEST['lastname']);
//		$streetAddress = strip_tags($_REQUEST['address1']);
//		$apartment = strip_tags($_REQUEST['address2']);
//		$citySt = strip_tags($_REQUEST['city_st']);
//		$zip = strip_tags($_REQUEST['postal_code']);
//		$email = strip_tags($_REQUEST['email_address']);
//		$sendNoticeBy = strip_tags($_REQUEST['send_notice_by']);
//		$pin = strip_tags($_REQUEST['pin#']);
//		$confirmPin = strip_tags($_REQUEST['confirmpin#']);
//		$phone = strip_tags($_REQUEST['phone_no']);
//
//		//Register the patron
//		$post_data = array(
//      'address1' => $streetAddress,
//		  'address2' => $apartment,
//			'aspect' => 'basic',
//			'pin#' => $pin,
//			'button' => 'I accept',
//			'city_st' => $citySt,
//			'confirmpin#' => $confirmPin,
//			'email_address' => $email,
//			'firstname' => $firstName,
//			'ipp' => 20,
//			'lastname' => $lastName,
//			'menu' => 'account',
//			'newuser_info' => 'true',
//			'npp' => 30,
//			'postal_code' => $zip,
//      'phone_no' => $phone,
//      'profile' => $this->selfRegProfile,
//			'ri' => '',
//			'send_notice_by' => $sendNoticeBy,
//			'session' => $sessionId,
//			'spp' => 20
//		);
//
//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
//		curl_setopt($curl_connection, CURLOPT_POST, true);
//		curl_setopt($curl_connection, CURLOPT_URL, $curl_url . '#focus');
//		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
//		$sresult = curl_exec($curl_connection);
//
//		//Get the temporary barcode from the page
//		if (preg_match('/Here is your temporary barcode\\. Use it for future authentication:&nbsp;([\\d-]+)/s', $sresult, $regs)) {
//			$tempBarcode = $regs[1];
//			//TODO: Append the library prefix to the card number
//			$tempBarcode = $tempBarcode;
//			$success = true;
//		}else{
//			$success = false;
//		}
//
//		unlink($cookie);
//
//		return array(
//		  'barcode' => $tempBarcode,
//		  'success'  => $success
//		);
//
//	}

}
