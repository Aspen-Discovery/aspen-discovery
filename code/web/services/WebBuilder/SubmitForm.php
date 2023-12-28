<?php
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class WebBuilder_SubmitForm extends Action {
	private $form;

	function launch() {
		require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
		$id = strip_tags($_REQUEST['id']);
		$this->form = new CustomForm();
		$this->form->id = $id;
		if (!$this->form->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		global $interface;
		if (isset($_REQUEST['submit'])) {
			$processForm = true;
			if (!UserAccount::isLoggedIn()) {
				if (!$this->form->requireLogin) {
					require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
					$recaptchaValid = RecaptchaSetting::validateRecaptcha();

					if (!$recaptchaValid) {
						$interface->assign('submissionError', 'The CAPTCHA response was incorrect, please try again.');
						$processForm = false;
					}
				} else {
					$interface->assign('submissionError', 'You must be logged in to submit a response, please login and try again.');
					$processForm = false;
				}
			}

			if ($processForm) {
				//Get the form values
				$structure = $this->form->getFormStructure();
				require_once ROOT_DIR . '/sys/DB/UnsavedDataObject.php';
				$serializedData = new UnsavedDataObject();
				DataObjectUtil::updateFromUI($serializedData, $structure);

				//Convert the form values to JSON
				if ($this->form->includeIntroductoryTextInEmail) {
					require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
					$parsedown = AspenParsedown::instance();
					$parsedown->setBreaksEnabled(true);
					$introText = $parsedown->parse($this->form->introText);
					$htmlData = '<div>' . $introText . '</div>';
				} else {
					$htmlData = '';
					$introText = '';
				}
				$htmlData .= $serializedData->getPrintableHtmlData($structure);

				//Save the form values to the database
				global $library;
				require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';
				$submission = new CustomFormSubmission();
				$submission->formId = $this->form->id;
				$submission->libraryId = $library->libraryId;
				if (UserAccount::isLoggedIn()) {
					$submission->userId = UserAccount::getActiveUserId();
				} else {
					$submission->userId = 0;
				}
				$submission->submission = $htmlData;


                //Recover and save the selected values
                $dom = new DOMDocument;
                $dom->loadHTML($htmlData);
                $elements = $dom->getElementsByTagName('div');
                $count = $elements->count();
                $submissionValues [] = 'No values';
                for ($index = 0; $index < $count/2; $index++){
                    $value = $elements->item($index*2+1)->textContent;
                    $submissionValues[$index] = $value;
                }
                error_log("LGM SUBMISSION VALUES  : " . print_r($submissionValues,true));
               /* for ($index = 0; $index < $count; $index+=2){
                    $field = $elements->item($index)->textContent;
                    $value = $elements->item($index+1)->textContent;
                    $submissionValues[$field] = $value;
                }*/

                foreach ($submissionValues as $field => $value) {
                    $submissionSelection = new CustomFormSubmissionSelection();
                    $submissionSelection->formSubmissionId = $submission->id;
                    $submissionSelection->submissionFieldId = $field;
                    $submissionSelection->insert();
                }

				$submission->dateSubmitted = time();
				$submission->insert();

				if (!empty($this->form->emailResultsTo)) {
					global $interface;
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					if (UserAccount::isLoggedIn()) {
						$interface->assign('patronName', UserAccount::getUserDisplayName());
						$interface->assign('replyTo', UserAccount::getActiveUserObj()->email);
					}
					$mail = new Mailer();
					$interface->assign('formTitle', $this->form->title);
					$interface->assign('htmlData', $htmlData);

					$interface->assign('includeIntroductoryTextInEmail', $this->form->includeIntroductoryTextInEmail);
					$interface->assign('introductoryText', $introText);

					$emailBody = $interface->fetch('WebBuilder/customFormSubmissionEmail.tpl');
					$emailResult = $mail->send($this->form->emailResultsTo, $this->form->title . ' Submission', null, null, $emailBody);
					global $logger;
					if (($emailResult instanceof AspenError)) {
						$logger->log("Could not email form submission: {$emailResult->getMessage()}.", Logger::LOG_ERROR);
					} elseif ($emailResult === false) {
						$logger->log('Could not email form submission due to an unknown error.', Logger::LOG_ERROR);
					}
				}
				if (empty($this->form->submissionResultText)) {
					$interface->assign('submissionResultText', 'Thank you for your response.');
				} else {
					$interface->assign('submissionResultText', $this->form->submissionResultText);
				}
			}
		} else {
			$interface->assign('submissionError', 'The form was not submitted correctly');
		}

		$this->display('customFormResults.tpl', $this->form->title, '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->form->title . ' Submission', true);
		if (UserAccount::userHasPermission([
			'Administer All Custom Forms',
			'Administer Library Custom Forms',
		])) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms?id=' . $this->form->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}