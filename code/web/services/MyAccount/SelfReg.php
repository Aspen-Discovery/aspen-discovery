<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class SelfReg extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegFields = $catalog->getSelfRegistrationFields();
		// For Arlington, this function call causes a page redirect to an external web page. plb 1-15-2016

		if (isset($_REQUEST['submit'])) {

			require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
			$recaptchaValid = RecaptchaSetting::validateRecaptcha();

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
		require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
		$recaptcha = new RecaptchaSetting();
		if ($recaptcha->find(true) && !empty($recaptcha->publicKey)){
			$captchaCode        = recaptcha_get_html($recaptcha->publicKey);
			$interface->assign('captcha', $captchaCode);
		}

		$interface->assign('formLabel', 'Self Registration');
		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('selfRegForm', $fieldsForm);

		$interface->assign('selfRegistrationFormMessage', $library->selfRegistrationFormMessage);
		$interface->assign('selfRegistrationSuccessMessage', $library->selfRegistrationSuccessMessage);
		$interface->assign('promptForBirthDateInSelfReg', $library->promptForBirthDateInSelfReg);

		$this->display('selfReg.tpl', 'Register for a Library Card', '');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Register for a Library Card');
		return $breadcrumbs;
	}
}
