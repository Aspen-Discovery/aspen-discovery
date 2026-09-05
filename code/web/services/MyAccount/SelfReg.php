<?php

require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class SelfReg extends Action {
	function launch(): void {
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Pragma: no-cache");
		header("Expires: 0");
		global $interface;
		global $library;
		global $activeLanguage;

		if (isset($_SESSION['selfRegResult'])) {
			$interface->assign('selfRegResult', $_SESSION['selfRegResult']);
			unset($_SESSION['selfRegResult']);
		}
		if (isset($_SESSION['selfRegError'])) {
			$errorType = $_SESSION['selfRegError'];
			switch ($errorType) {
				case 'captcha':
					$interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
					break;
				case 'email':
					$emailMessage = translate([
						'text' => 'Please enter a valid email address.',
						'isPublicFacing' => true
					]);
					$interface->assign('emailMessage', $emailMessage);
					break;
				case 'phone':
					$phoneMessage = translate([
						'text' => 'Please enter a valid phone number.',
						'isPublicFacing' => true
					]);
					$interface->assign('phoneMessage', $phoneMessage);
					break;
				case 'address':
					$addressMessage = translate([
						'text' => 'The address you entered does not appear to be valid. Please check your address and try again.',
						'isPublicFacing' => true
					]);
					$interface->assign('addressMessage', $addressMessage);
					break;
				case 'age':
					$text = $_SESSION['selfRegAgeText'] ?? 'Age not valid.';
					$ageMessage = translate([
						'text' => $text,
						'isPublicFacing' => true
					]);
					$interface->assign('ageMessage', $ageMessage);
					unset($_SESSION['selfRegAgeText']);
					break;
			}
			unset($_SESSION['selfRegError']);
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegFields = $catalog->getSelfRegistrationFields();
		if ($library->enableSelfRegistration == 0) {
			$this->display('selfRegistrationNotAllowed.tpl', 'Register for a Library Card', '');
		} elseif ($library->enableSelfRegistration == 2) {
			if (!empty($library->selfRegistrationUrl)) {
				header("Location: {$library->selfRegistrationUrl}");
				exit;
			}
			$this->display('selfRegistrationNotAllowed.tpl', 'Register for a Library Card', '');
		} else {
			if (isset($_REQUEST['submit'])) {
				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				if (!RecaptchaSetting::validateRecaptcha()) {
					$_SESSION['selfRegError'] = 'captcha';
					$_SESSION['selfRegFormData'] = $_REQUEST;
					header("Location: /MyAccount/SelfReg");
					exit;
				}

				$outcome = self::validateAndRegister($catalog, $selfRegFields);
				if ($outcome['success']) {
					$_SESSION['selfRegResult'] = $outcome['result'];
					header("Location: /MyAccount/SelfReg");
					exit;
				}

				$_SESSION['selfRegError'] = $outcome['errorType'];
				if (!empty($outcome['ageText'])) {
					$_SESSION['selfRegAgeText'] = $outcome['ageText'];
				}
				$_SESSION['selfRegFormData'] = $_REQUEST;
				header("Location: /MyAccount/SelfReg");
				exit;
			}

			// Pre-fill form with user supplied data from session (after POST/Redirect/GET).
			if (isset($_SESSION['selfRegFormData'])) {
				foreach ($selfRegFields as &$property) {
					if ($property['type'] == 'section') {
						foreach ($property['properties'] as &$propertyInSection) {
							if (isset($_SESSION['selfRegFormData'][$propertyInSection['property']])) {
								$userValue = $_SESSION['selfRegFormData'][$propertyInSection['property']];
								$propertyInSection['default'] = $userValue;
							}
						}
					} else {
						if (isset($_SESSION['selfRegFormData'][$property['property']])) {
							$userValue = $_SESSION['selfRegFormData'][$property['property']];
							$property['default'] = $userValue;
						}
					}
				}
				unset($_SESSION['selfRegFormData']);
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
					$interface->assign('showTOSFirst', $selfRegTerms->showTOSFirst);
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

			$languageCode = 'en';
			if (isset($activeLanguage) && !empty($activeLanguage->code)) {
				$languageCode = $activeLanguage->code;
			}
			$selfRegistrationFormMessage = $library->getTextBlockTranslation('selfRegistrationFormMessage', $languageCode);
			if (empty($selfRegistrationFormMessage)) {
				$selfRegistrationFormMessage = $library->selfRegistrationFormMessage;
			}
			$interface->assign('selfRegistrationFormMessage', $selfRegistrationFormMessage);
			$interface->assign('selfRegistrationSuccessMessage', self::getSelfRegistrationSuccessMessage($library));
			$interface->assign('promptForBirthDateInSelfReg', $library->promptForBirthDateInSelfReg);

			$this->display('selfReg.tpl', 'Register for a Library Card', '');
		}
	}

	public static function buildMinimalSelfRegForm(CatalogConnection $catalog, ?string $introText = null, ?string $footerText = null, ?string $onSubmissionJS = null): string {
		global $interface;
		$selfRegFields = $catalog->getILSRegistrationFormStructure(AbstractIlsDriver::ILS_REG_MODE_MINIMAL_SELF);
		if (empty($selfRegFields)) {
			return '';
		}

		$interface->assign('submitUrl', '/MyAccount/SelfReg');
		$interface->assign('saveButtonText', 'Register');
		$interface->assign('isSelfRegistration', true);
		$interface->assign('formLabel', 'Self Registration');
		$interface->assign('structure', $selfRegFields);
		$interface->assign('onSubmissionJS', $onSubmissionJS);
		$interface->assign('minimalSelfRegForm', $interface->fetch('DataObjectUtil/objectEditForm.tpl'));
		$interface->assign('introText', $introText);
		$interface->assign('footerText', $footerText);
		return $interface->fetch('MyAccount/minimalSelfRegForm.tpl');
	}

	public static function getSelfRegistrationSuccessMessage(Library $library): string {
		global $activeLanguage;
		$languageCode = 'en';
		if (isset($activeLanguage) && !empty($activeLanguage->code)) {
			$languageCode = $activeLanguage->code;
		}
		$message = $library->getTextBlockTranslation('selfRegistrationSuccessMessage', $languageCode);
		if (empty($message)) {
			$message = $library->selfRegistrationSuccessMessage;
		}
		return $message;
	}

	public static function validateAndRegister(CatalogConnection $catalog, array $selfRegFields): array {
		global $library;
		require_once ROOT_DIR . '/sys/Administration/USPS.php';
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$uspsInfo = USPS::getUSPSInfo();
		$streetAddress = '';
		$city = '';
		$state = '';
		$zip = '';
		$dob = '';

		$invalidContactInfo = false;
		$errorType = null;
		if (!empty($_REQUEST['email']) && !filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
			$errorType = 'email';
			$invalidContactInfo = true;
		}
		if (!empty($_REQUEST['phone']) && !SystemUtils::validatePhoneNumber($_REQUEST['phone'])) {
			$errorType = 'phone';
			$invalidContactInfo = true;
		}

		//get the correct _REQUEST names as they differ across ILSes
		foreach ($_REQUEST as $selfRegValue => $val) {
			if (!(preg_match('/(.*?)address2(.*)|(.*?)borrower_B(.*)|(.*?)borrower_alt(.*)/', $selfRegValue))) {
				if (preg_match('/(.*?)address|street(.*)/', $selfRegValue)) {
					$streetAddress = $val;
				} elseif (preg_match('/(.*?)city(.*)/', $selfRegValue)) {
					$city = $val;
				} elseif (preg_match('/(.*?)state(.*)/', $selfRegValue)) {
					//USPS does not accept anything other than 2 character state codes but will use the ZIP to fill in the blank
					if (strlen($val) == 2) {
						$state = $val;
					}
				} elseif (preg_match('/(.*?)zip(.*)/', $selfRegValue)) {
					$zip = $val;
				} elseif (preg_match('/(.*?)dob|dateofbirth|birth[dD]ate(.*)/', $selfRegValue)) {
					$dob = $val;
				}
			}
		}

		//if there's no USPS info or email or phone are invalid, don't bother trying to validate
		if ($uspsInfo && !$invalidContactInfo) {
			if (!SystemUtils::validateAddress($streetAddress, $city, $state, $zip)) {
				return ['success' => false, 'errorType' => 'address'];
			}
			if (!empty($dob) && !SystemUtils::validateAge($library->minSelfRegAge, $dob)) {
				return ['success' => false, 'errorType' => 'age'];
			}
			return ['success' => true, 'result' => $catalog->selfRegister()];
		}

		if (!empty($dob)) {
			$maxSelfRegAge = $selfRegFields['identitySection']['properties']['borrower_dateofbirth']['maxAgeForSelfReg'] ?? null;
			if (!SystemUtils::validateAge($library->minSelfRegAge, $dob, $maxSelfRegAge)) {
				if ((int)$library->minSelfRegAge > 0 && !empty($maxSelfRegAge) && (int)$maxSelfRegAge > 0) {
					$ageText = "You must be at least $library->minSelfRegAge and no older than $maxSelfRegAge years old. Please enter a valid date of birth.";
				} elseif ($library->minSelfRegAge > 0) {
					$ageText = "You must be at least $library->minSelfRegAge years old. Please enter a valid date of birth.";
				} elseif (!empty($maxSelfRegAge) && (int)$maxSelfRegAge > 0) {
					$ageText = "You must be no older than $maxSelfRegAge years old. Please enter a valid date of birth.";
				} else {
					$ageText = "Please enter a valid date of birth";
				}
				return ['success' => false, 'errorType' => 'age', 'ageText' => $ageText];
			}
			if ($invalidContactInfo) {
				return ['success' => false, 'errorType' => $errorType];
			}
			return ['success' => true, 'result' => $catalog->selfRegister()];
		}

		if ($invalidContactInfo) {
			return ['success' => false, 'errorType' => $errorType];
		}
		return ['success' => true, 'result' => $catalog->selfRegister()];
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Register for a Library Card');
		return $breadcrumbs;
	}
}
