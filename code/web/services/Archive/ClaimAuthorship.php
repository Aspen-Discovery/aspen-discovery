<?php

require_once ROOT_DIR . '/sys/Archive/ClaimAuthorshipRequest.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';
class Archive_ClaimAuthorship extends Action{
	/** @var IslandoraRecordDriver $requestedObject */
	private $requestedObject;
	function launch(){
		global $configArray;
		global $interface;

		$archiveRequestFields = ClaimAuthorshipRequest::getObjectStructure();

		if (!isset($_REQUEST['pid'])) {
			AspenError::raiseError('No id provided, you must select which object you want to claim authorship for');
		}

		$pid = $_REQUEST['pid'];
		$archiveRequestFields['pid']['default'] = $pid;

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$archiveObject = FedoraUtils::getInstance()->getObject($pid);

		$this->requestedObject = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('requestedObject', $this->requestedObject);

		//Find the owning library
		$owningLibrary = new Library();
		list($namespace) = explode(':', $pid);

		$owningLibrary->archiveNamespace = $namespace;
		if (!$owningLibrary->find(true) || $owningLibrary->getNumResults() != 1){
			AspenError::raiseError('Could not determine which library owns this object, cannot claim authorship.');
		}

		if (isset($_REQUEST['submit'])) {
			require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
			$recaptchaValid = RecaptchaSetting::validateRecaptcha();

			if (!$recaptchaValid) {
				$interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');

				// Pre-fill form with user supplied data
				foreach ($archiveRequestFields as &$property) {
					if (isset($_REQUEST[$property['property']])){
						$uservalue = $_REQUEST[$property['property']];
						$property['default'] = $uservalue;
					}
				}

			} else {
				$archiveRequestFields['dateRequested']['value'] = time();
				/** @var ArchiveRequest $newObject */
				$newObject = $this->insertObject($archiveRequestFields);
				$interface->assign('requestSubmitted', true);
				if ($newObject !== false){
					$interface->assign('requestResult', $newObject);

					$body = $interface->fetch('Emails/claim-authorship-request.tpl');

					//Find the owning library
					$owningLibrary = new Library();
					list($namespace) = explode(':', $newObject->pid);

					$owningLibrary->archiveNamespace = $namespace;
					if ($owningLibrary->find(true) && $owningLibrary->getNumResults() == 1){
						//Send a copy of the request to the proper administrator
						if (strpos($body, 'http') === false && strpos($body, 'mailto') === false && $body == strip_tags($body)){
							$body .= $configArray['Site']['url'] . $this->requestedObject->getRecordUrl();
							require_once ROOT_DIR . '/sys/Email/Mailer.php';
							$mail = new Mailer();
							$subject = 'New Authorship Claim for Archive Content';
							$emailResult = $mail->send($owningLibrary->archiveRequestEmail, $subject, $body);

							if ($emailResult === true){
								$result = array(
									'result' => true,
									'message' => 'Your email was sent successfully.'
								);
							} elseif (($emailResult instanceof AspenError)){
								$interface->assign('error', "Your request could not be sent: {$emailResult->getMessage()}.");
							} else {
								$interface->assign('error', "Your request could not be sent due to an unknown error.");
								global $logger;
								$logger->log("Mail List Failure (unknown reason), parameters: $owningLibrary->archiveRequestEmail, $newObject->email, $subject, $body", Logger::LOG_ERROR);
							}
						} else {
							$interface->assign('error', 'Please do not include html or links within your request');
							$newObject->delete();
						}
					} else {
						$interface->assign('error', "Your request could not be sent because the library does not accept authorship claims.");
					}


				}else{
					$interface->assign('error', $_SESSION['lastError']);
				}
			}
		}

		unset($archiveRequestFields['dateRequested']);

		$interface->assign('submitUrl', '/Archive/ClaimAuthorship');
		$interface->assign('structure', $archiveRequestFields);
		$interface->assign('saveButtonText', 'Submit Request');
		$interface->assign('claimAuthorshipHeader', $owningLibrary->claimAuthorshipHeader);
		$interface->assign('formLabel', 'Claim Authorship');

		// Set up captcha to limit spam self registrations
		require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
		$recaptchaValid = RecaptchaSetting::validateRecaptcha();

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('requestForm', $fieldsForm);

		$this->display('claimAuthorship.tpl', 'Claim Authorship');
	}

	function insertObject($structure){
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';

		$newObject = new ClaimAuthorshipRequest();
		//Check to see if we are getting default values from the
		DataObjectUtil::updateFromUI($newObject, $structure);
		$validationResults = DataObjectUtil::validateObject($structure, $newObject);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert();
			if (!$ret) {
				global $logger;
				if ($newObject->getLastError()) {
					$errorDescription = $newObject->getLastError()->getUserInfo();
				} else {
					$errorDescription = 'Unknown error';
				}
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, Logger::LOG_DEBUG);
				$_SESSION['lastError'] = "An error occurred inserting {$this->getObjectType()} <br/>{$errorDescription}";

				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new object Claim Authorship Request ' . $errorDescription, Logger::LOG_DEBUG);
			$_SESSION['lastError'] = "The information entered was not valid. <br/>" . implode('<br/>', $validationResults['errors']);
			return false;
		}
		return $newObject;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (!empty($this->requestedObject)){
			$breadcrumbs[] = new Breadcrumb($this->requestedObject->getRecordUrl(), $this->requestedObject->getTitle());
		}
		$breadcrumbs[] = new Breadcrumb('', 'Claim Authorship');
		return $breadcrumbs;
	}
}