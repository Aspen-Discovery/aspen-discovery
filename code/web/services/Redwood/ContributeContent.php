<?php

require_once ROOT_DIR . '/sys/Redwood/UserContribution.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';
class Redwood_ContributeContent extends Action{
	function launch(){
		global $configArray;
		global $interface;

		$objectFields = UserContribution::getObjectStructure();


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

				// Pre-fill form with user supplied data
				foreach ($objectFields as &$property) {
					if (isset($_REQUEST[$property['property']])){
						$userValue = $_REQUEST[$property['property']];
						$property['default'] = $userValue;
					}
				}

			} else {
				$objectFields['dateRequested']['value'] = time();
				/** @var ArchiveRequest $newObject */
				$newObject = $this->insertObject($objectFields);
				$interface->assign('requestSubmitted', true);
				if ($newObject !== false){
					$interface->assign('requestResult', $newObject);
				}else{
					$interface->assign('error', $_SESSION['lastError']);
				}
			}
		}

		unset($objectFields['dateRequested']);

		$interface->assign('submitUrl', '/Redwood/ContributeContent');
		$interface->assign('structure', $objectFields);
		$interface->assign('saveButtonText', 'Submit Content');

		// Set up captcha to limit spam self registrations
		if (isset($configArray['ReCaptcha']['publicKey'])) {
			$recaptchaPublicKey = $configArray['ReCaptcha']['publicKey'];
			$captchaCode        = recaptcha_get_html($recaptchaPublicKey);
			$interface->assign('captcha', $captchaCode);
		}

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('requestForm', $fieldsForm);

		$this->display('contributeContent.tpl', 'Contribute Content');
	}

	function insertObject($structure){
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';

		/** @var DB_DataObject $newObject */
		$newObject = new UserContribution();
		//Check to see if we are getting default values from the
		DataObjectUtil::updateFromUI($newObject, $structure);
		$validationResults = DataObjectUtil::validateObject($structure, $newObject);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert();
			if (!$ret) {
				global $logger;
				if ($newObject->_lastError) {
					$errorDescription = $newObject->_lastError->getUserInfo();
				} else {
					$errorDescription = 'Unknown error';
				}
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, Logger::LOG_DEBUG);
				$_SESSION['lastError'] = "An error occurred inserting the contribution <br/>{$errorDescription}";
				$logger->log(mysql_error(), Logger::LOG_DEBUG);
				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new Content Contribution ' . $errorDescription, Logger::LOG_DEBUG);
			$_SESSION['lastError'] = "The information entered was not valid. <br/>" . implode('<br/>', $validationResults['errors']);
			return false;
		}
		return $newObject;
	}
}