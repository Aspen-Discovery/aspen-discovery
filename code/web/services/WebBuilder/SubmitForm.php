<?php

class WebBuilder_SubmitForm extends Action
{
	private $form;
	function launch()
	{
		require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
		$id = strip_tags($_REQUEST['id']);
		$this->form = new CustomForm();
		$this->form->id = $id;
		if (!$this->form->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}
		global $interface;
		if (isset($_REQUEST['submit'])) {
			//Get the form values
			$structure = $this->form->getFormStructure();
			require_once ROOT_DIR . '/sys/DB/UnsavedDataObject.php';
			$serializedData = new UnsavedDataObject();
			DataObjectUtil::updateFromUI($serializedData, $structure);

			//Convert the form values to JSON
			$htmlData = $serializedData->getPrintableHtmlData($structure);

			//Save the form values to the database
			global $library;
			require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';
			$submission = new CustomFormSubmission();
			$submission->formId = $this->form->id;
			$submission->libraryId = $library->libraryId;
			if (UserAccount::isLoggedIn()) {
				$submission->userId = UserAccount::getActiveUserId();
			}else{
				$submission->userId = 0;
			}
			$submission->submission = $htmlData;
			$submission->dateSubmitted = time();
			$submission->insert();

			if (!empty($this->form->emailResultsTo)){
				global $interface;
				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$replyTo = null;
				if (UserAccount::isLoggedIn()){
					$replyTo = UserAccount::getActiveUserObj()->email;
					$interface->assign('patronName', UserAccount::getUserDisplayName());
				}
				$mail = new Mailer();
				$interface->assign('formTitle', $this->form->title);
				$interface->assign('htmlData', $htmlData);

				$emailBody = $interface->fetch('WebBuilder/customFormSubmissionEmail.tpl');
				$emailResult = $mail->send($this->form->emailResultsTo, $this->form->title . ' Submission', $emailBody, $replyTo, true);
				global $logger;
				if (($emailResult instanceof AspenError)){
					$logger->log("Could not email form submission: {$emailResult->getMessage()}.", Logger::LOG_ERROR);
				}elseif ($emailResult === false){
					$logger->log('Could not email form submission due to an unknown error.', Logger::LOG_ERROR);
				}
			}

			$interface->assign('submissionResultText', $this->form->submissionResultText);
		}else{
			$interface->assign('submissionError', 'The form was not submitted correctly');
		}

		$this->display('customFormResults.tpl', $this->form->title, '', false);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->form->title . ' Submission', true);
		if (UserAccount::userHasPermission(['Administer All Custom Forms', 'Administer Library Custom Forms'])){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms?id=' . $this->form->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}