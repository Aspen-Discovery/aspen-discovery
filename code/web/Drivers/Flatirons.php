<?php

require_once ROOT_DIR . '/Drivers/Millennium.php';

class Flatirons extends Millennium{
	public function getSelfRegistrationFields(){
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => false);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'phone', 'type'=>'text', 'label'=>'Phone Number', 'description'=>'Phone Number', 'maxLength' => 16, 'required' => true);
		$fields[] = array('property'=>'email', 'type'=>'email', 'label'=>'Email', 'description'=>'Email', 'maxLength' => 128, 'required' => false);
		//$fields[] = array('property'=>'universityID', 'type'=>'text', 'label'=>'Drivers License #', 'description'=>'Drivers License', 'maxLength' => 128, 'required' => false);

		return $fields;
	}

	function selfRegister()
	{
		// Capitalize Mailing address
		$_REQUEST['address'] = strtoupper($_REQUEST['address']);
		$_REQUEST['city']    = strtoupper($_REQUEST['city']);
		$_REQUEST['state']   = strtoupper($_REQUEST['state']);
		$_REQUEST['zip']     = strtoupper($_REQUEST['zip']);

		return parent::selfRegister();
	}


}
