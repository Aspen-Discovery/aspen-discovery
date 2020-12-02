<?php

require_once ROOT_DIR . '/Drivers/CarlX.php';

class Nashville extends CarlX {

	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
	}

	public function getFineSystem($branchId){
		if ($branchId >= 30 && $branchId<= 178 && $branchId != 42 && $branchId != 171) {
			return "MNPS";
		} else {
			return "NPL";
		}
	}

	function getSelfRegTemplate($reason){
		if ($reason == 'duplicate_email'){
			return 'Emails/nashville-self-registration-denied-duplicate_email.tpl';
		}elseif ($reason == 'duplicate_name+birthdate') {
			return 'Emails/nashville-self-registration-denied-duplicate_name+birthdate.tpl';
		}elseif ($reason == 'success') {
			return 'Emails/nashville-self-registration.tpl';
		}else{
			return;
		}
	}

}
