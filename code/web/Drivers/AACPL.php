<?php

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
}
