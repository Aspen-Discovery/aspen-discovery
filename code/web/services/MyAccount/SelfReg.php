<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class SelfReg extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegFields = $catalog->getSelfRegistrationFields();
		if ($library->enableSelfRegistration == 0) {
			$this->display('selfRegistrationNotAllowed.tpl', 'Register for a Library Card', '');
		} else {
			if (isset($_REQUEST['submit'])) {

				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				$recaptchaValid = RecaptchaSetting::validateRecaptcha();

				if (!$recaptchaValid) {
					$interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
				} else {
					require_once ROOT_DIR . '/sys/Administration/USPS.php';
					require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
					$uspsInfo = USPS::getUSPSInfo();
					$streetAddress = '';
					$city = '';
					$state = '';
					$zip = '';
					$dob = '';

					//get the correct _REQUEST names as they differ across ILSes
					foreach ($_REQUEST as $selfRegValue => $val){
						if (!(preg_match('/(.*?)address2(.*)|(.*?)borrower_B(.*)|(.*?)borrower_alt(.*)/', $selfRegValue))){
							if (preg_match('/(.*?)address|street(.*)/', $selfRegValue)){
								$streetAddress = $val;
							}
							elseif (preg_match('/(.*?)city(.*)/', $selfRegValue)){
								$city = $val;
							}
							elseif (preg_match('/(.*?)state(.*)/', $selfRegValue)){
								//USPS does not accept anything other than 2 character state codes but will use the ZIP to fill in the blank
								if (strlen($val) == 2){
									$state = $val;
								}
							}
							elseif (preg_match('/(.*?)zip(.*)/', $selfRegValue)){
								$zip = $val;
							}
							elseif (preg_match('/(.*?)dob|dateofbirth|birth[dD]ate(.*)/', $selfRegValue)){
								$dob = $val;
							}
						}
					}
					//if there's no USPS info, don't bother trying to validate
					if ($uspsInfo){
						//Submit form to ILS if address is validated
						if (SystemUtils::validateAddress($streetAddress, $city, $state, $zip)){
							//Submit form to ILS if age is validated
							if (!empty($dob)) {
								if (SystemUtils::validateAge($library->minSelfRegAge, $dob)) {
									$result = $catalog->selfRegister();
									$interface->assign('selfRegResult', $result);
								}else {
									$ageMessage = translate([
										'text' => 'Age not valid.',
										'isPublicFacing' => true
									]);
									$interface->assign('ageMessage', $ageMessage);
								}
							} else {
								$result = $catalog->selfRegister();
								$interface->assign('selfRegResult', $result);
							}
						} else {
							$addressMessage = translate([
								'text' => 'The address you entered does not appear to be valid. Please check your address and try again.',
								'isPublicFacing' => true
							]);
							$interface->assign('addressMessage', $addressMessage);
						}
					} else {
						//Submit form to ILS if age is validated
						if (!empty($dob)) {
							if (SystemUtils::validateAge($library->minSelfRegAge, $dob)){
								$result = $catalog->selfRegister();
								$interface->assign('selfRegResult', $result);
							} else {
								$ageMessage = translate([
									'text' => 'Age should be at least' . $library->minSelfRegAge . ' years. Please enter a valid Date of Birth.',
									'isPublicFacing' => true
								]);
								$interface->assign('ageMessage', $ageMessage);
							}
						} else {
							$result = $catalog->selfRegister();
							$interface->assign('selfRegResult', $result);
						}
					}
				}
				// Pre-fill form with user supplied data
				foreach ($selfRegFields as &$property) {
					if ($property['type'] == 'section') {
						foreach ($property['properties'] as &$propertyInSection) {
							if (isset($_REQUEST[$propertyInSection['property']])) {
								$userValue = $_REQUEST[$propertyInSection['property']];
								$propertyInSection['default'] = $userValue;
							}
						}
					} else {
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
			if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
				$captchaCode = recaptcha_get_html($recaptcha->publicKey, 'selfReg');
				$interface->assign('captcha', $captchaCode);
				$interface->assign('captchaKey', $recaptcha->publicKey);
			}

			$interface->assign('tos', false);
			if ($catalog->accountProfile != null && ($catalog->accountProfile->ils == "symphony" || $catalog->accountProfile->ils == "carlx" || $catalog->accountProfile->ils == "sierra")){
				$selfRegTerms = $catalog->getSelfRegistrationTerms();
				if ($selfRegTerms != null){
					$interface->assign('tos', true);
					$interface->assign("selfRegTermsID", $selfRegTerms->id);
					$tosAccept = false;
					if (!empty($_REQUEST['tosAccept'])){
						$tosAccept = $_REQUEST['tosAccept'];
					}
					$interface->assign('tosAccept', $tosAccept);
				}
			}

			$interface->assign('isSelfRegistration', true);
			$interface->assign('formLabel', 'Self Registration');
			$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
			$interface->assign('selfRegForm', $fieldsForm);

			$interface->assign('selfRegistrationFormMessage', $library->selfRegistrationFormMessage);
			$interface->assign('selfRegistrationSuccessMessage', $library->selfRegistrationSuccessMessage);
			$interface->assign('promptForBirthDateInSelfReg', $library->promptForBirthDateInSelfReg);

			$this->display('selfReg.tpl', 'Register for a Library Card', '');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Register for a Library Card');
		return $breadcrumbs;
	}
}
