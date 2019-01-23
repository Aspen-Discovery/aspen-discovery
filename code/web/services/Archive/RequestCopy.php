<?php

/**
 * Description goes here
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/21/2016
 * Time: 4:04 PM
 */
require_once ROOT_DIR . '/sys/Archive/ArchiveRequest.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';
class Archive_RequestCopy extends Action{
	function launch(){
		global $configArray;
		global $interface;

		if (!isset($_REQUEST['pid'])) {
			PEAR_Singleton::raiseError('No id provided, you must select which object you want a copy of');
		}

		$pid = $_REQUEST['pid'];

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$archiveObject = FedoraUtils::getInstance()->getObject($pid);
		$requestedObject = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('requestedObject', $requestedObject);

		//Find the owning library
		$owningLibrary = new Library();
		list($namespace) = explode(':', $pid);

		$owningLibrary->archiveNamespace = $namespace;
		if (!$owningLibrary->find(true) || $owningLibrary->N != 1){
			PEAR_Singleton::raiseError('Could not determine which library owns this object, cannot request a copy.');
		}
		$archiveRequestFields = $owningLibrary->getArchiveRequestFormStructure();
		$archiveRequestFields['pid']['default'] = $pid; // add pid to the form

		if (isset($_REQUEST['submit'])) {
			if (isset($configArray['ReCaptcha']['privateKey'])){
				$privatekey = $configArray['ReCaptcha']['privateKey'];
				$resp = recaptcha_check_answer ($privatekey,
					$_SERVER["REMOTE_ADDR"],
					$_POST["g-recaptcha-response"]);
				$recaptchaValid = $resp->is_valid;
			}else{
				$recaptchaValid = true;
			}

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

					$body = $interface->fetch('Emails/archive-request.tpl');

					//Find the owning library
					$owningLibrary = new Library();
					list($namespace) = explode(':', $newObject->pid);

					$owningLibrary->archiveNamespace = $namespace;
					if ($owningLibrary->find(true) && $owningLibrary->N == 1){
						//Send a copy of the request to the proper administrator
						if (strpos($body, 'http') === false && strpos($body, 'mailto') === false && $body == strip_tags($body)){
							$body .= $configArray['Site']['url'] . $requestedObject->getRecordUrl();
							require_once ROOT_DIR . '/sys/Mailer.php';
							$mail = new VuFindMailer();
							$subject = 'New Request for Copies of Archive Content';
							$emailResult = $mail->send($owningLibrary->archiveRequestEmail, $newObject->email, $subject, $body);

							if ($emailResult === true){
								$result = array(
									'result' => true,
									'message' => 'Your e-mail was sent successfully.'
								);
							} elseif (PEAR_Singleton::isError($emailResult)){
								$interface->assign('error', "Your request could not be sent: {$emailResult->message}.");
							} else {
								$interface->assign('error', "Your request could not be sent due to an unknown error.");
								global $logger;
								$logger->log("Mail List Failure (unknown reason), parameters: $owningLibrary->archiveRequestEmail, $newObject->email, $subject, $body", PEAR_LOG_ERR);
							}
						} else {
							$interface->assign('error', 'Please do not include html or links within your request');
							$newObject->delete();
						}
					} else {
						$interface->assign('error', "Your request could not be sent because the library does not accept requests for copies.");
					}


				}else{
					$interface->assign('error', $_SESSION['lastError']);
				}
			}
		}

		unset($archiveRequestFields['dateRequested']);

		$interface->assign('submitUrl', $configArray['Site']['path'] . '/Archive/RequestCopy');
		$interface->assign('structure', $archiveRequestFields);
		$interface->assign('saveButtonText', 'Submit Request');
		$interface->assign('archiveRequestMaterialsHeader', $owningLibrary->archiveRequestMaterialsHeader);

		// Set up captcha to limit spam self registrations
		if (isset($configArray['ReCaptcha']['publicKey'])) {
			$recaptchaPublicKey = $configArray['ReCaptcha']['publicKey'];
			$captchaCode        = recaptcha_get_html($recaptchaPublicKey);
			$interface->assign('captcha', $captchaCode);
		}

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('requestForm', $fieldsForm);

		$this->display('requestCopy.tpl', 'Archival Material Copy Request');
	}

	function insertObject($structure){
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';

		/** @var DB_DataObject $newObject */
		$newObject = new ArchiveRequest();
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
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, PEAR_LOG_DEBUG);
				$_SESSION['lastError'] = "An error occurred inserting {$this->getObjectType()} <br/>{$errorDescription}";
				$logger->log(mysql_error(), PEAR_LOG_DEBUG);
				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new object Archive Request ' . $errorDescription, PEAR_LOG_DEBUG);
			$_SESSION['lastError'] = "The information entered was not valid. <br/>" . implode('<br/>', $validationResults['errors']);
			return false;
		}
		return $newObject;
	}
}