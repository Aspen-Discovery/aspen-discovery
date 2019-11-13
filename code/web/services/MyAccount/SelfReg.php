<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class SelfReg extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;
		global $configArray;

		/** @var  CatalogConnection $catalog */
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegFields = $catalog->getSelfRegistrationFields();
		// For Arlington, this function call causes a page redirect to an external web page. plb 1-15-2016

		if (isset($_REQUEST['submit'])) {

			if (isset($configArray['ReCaptcha']['privateKey'])){
				$privateKey = $configArray['ReCaptcha']['privateKey'];
				$resp = recaptcha_check_answer ($privateKey,
					$_SERVER["REMOTE_ADDR"],
					$_POST["g-recaptcha-response"]);
				$recaptchaValid = $resp->is_valid;
			}else{
				$recaptchaValid = true;
			}

			if (!$recaptchaValid) {
				$interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
			} else {
				//Submit the form to ILS
				$result = $catalog->selfRegister();
				$interface->assign('selfRegResult', $result);
			}

			// Pre-fill form with user supplied data
			foreach ($selfRegFields as &$property) {
				if ($property['type'] == 'section'){
					foreach ($property['properties'] as &$propertyInSection) {
						$userValue = $_REQUEST[$propertyInSection['property']];
						$propertyInSection['default'] = $userValue;
					}
				}else{
					$userValue = $_REQUEST[$property['property']];
					$property['default'] = $userValue;
				}
			}
		}

		$interface->assign('submitUrl', '/MyAccount/SelfReg');
		$interface->assign('structure', $selfRegFields);
		$interface->assign('saveButtonText', 'Register');

		// Set up captcha to limit spam self registrations
		if (isset($configArray['ReCaptcha']['publicKey'])) {
			$recaptchaPublicKey = $configArray['ReCaptcha']['publicKey'];
			$captchaCode        = recaptcha_get_html($recaptchaPublicKey);
			$interface->assign('captcha', $captchaCode);
		}

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('selfRegForm', $fieldsForm);

		$interface->assign('selfRegistrationFormMessage', $library->selfRegistrationFormMessage);
		$interface->assign('selfRegistrationSuccessMessage', $library->selfRegistrationSuccessMessage);
		$interface->assign('promptForBirthDateInSelfReg', $library->promptForBirthDateInSelfReg);

		$this->display('selfReg.tpl', 'Self Registration');
	}
}
