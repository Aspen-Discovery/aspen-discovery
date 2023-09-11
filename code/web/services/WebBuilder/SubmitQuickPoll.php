<?php
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class WebBuilder_SubmitQuickPoll extends Action {
	private $quickPoll;

	function launch() {
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmission.php';
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmissionSelection.php';
		$id = strip_tags($_REQUEST['id']);
		$this->quickPoll = new QuickPoll();
		$this->quickPoll->id = $id;
		if (!$this->quickPoll->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		global $interface;
		$interface->assign('title', $this->quickPoll->title);
		if (isset($_REQUEST['submit'])) {
			$processQuickPoll = true;
			if (!UserAccount::isLoggedIn()) {
				if (!$this->quickPoll->requireLogin) {
					require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
					$recaptchaValid = RecaptchaSetting::validateRecaptcha();

					if (!$recaptchaValid) {
						$interface->assign('submissionError', 'The CAPTCHA response was incorrect, please try again.');
						$processQuickPoll = false;
					}
				} else {
					$interface->assign('submissionError', 'You must be logged in to submit a response, please login and try again.');
					$processQuickPoll = false;
				}
			}

			if ($processQuickPoll) {
				//Validate the poll
				$formDataIsValid = true;
				if (!empty($_REQUEST['name'])) {
					if (strip_tags($_REQUEST['name']) != $_REQUEST['name']) {
						$interface->assign('submissionError', 'Invalid name entered.');
						$formDataIsValid = false;
					}
					if (mb_strlen($_REQUEST['name']) > 255) {
						$interface->assign('submissionError', 'Invalid name entered.');
						$formDataIsValid = false;
					}
				} else if ($this->quickPoll->requireName) {
					$interface->assign('submissionError', 'Please enter your name.');
					$formDataIsValid = false;
				}
				if (!empty($_REQUEST['email'])) {
					if (strip_tags($_REQUEST['email']) != $_REQUEST['email']) {
						$interface->assign('submissionError', 'Invalid email entered.');
						$formDataIsValid = false;
					}
					if (mb_strlen($_REQUEST['email']) > 255) {
						$interface->assign('submissionError', 'Invalid email entered.');
						$formDataIsValid = false;
					}
				} else if ($this->quickPoll->requireEmail) {
					$interface->assign('submissionError', 'Please enter your email.');
					$formDataIsValid = false;
				}
				if (empty($_REQUEST['pollOption'])) {
					$interface->assign('submissionError', 'At least one option must be selected.');
					$formDataIsValid = false;
				} else {
					if (is_array($_REQUEST['pollOption'])) {
						foreach ($_REQUEST['pollOption'] as $selectedOption => $value) {
							if (!is_numeric($value)) {
								$interface->assign('submissionError', 'Invalid option selected.');
								$formDataIsValid = false;
							}
						}
					}else{
						if (!is_numeric($_REQUEST['pollOption'])) {
							$interface->assign('submissionError', 'Invalid option selected.');
							$formDataIsValid = false;
						}
					}
				}
				if ($formDataIsValid) {
					$submission = new QuickPollSubmission();
					$submission->pollId = $this->quickPoll->id;
					if (UserAccount::isLoggedIn()) {
						$submission->userId = UserAccount::getActiveUserId();
					}
					if (!empty($_REQUEST['name'])) {
						$submission->name = $_REQUEST['name'];
					}
					if (!empty($_REQUEST['email'])) {
						$submission->email = $_REQUEST['email'];
					}
					global $library;
					$submission->libraryId = $library->libraryId;
					$submission->dateSubmitted = time();
					$submission->insert();

					//Save the selected options
					if (is_array($_REQUEST['pollOption'])) {
						foreach ($_REQUEST['pollOption'] as $selectedOption => $value) {
							$submissionSelection = new QuickPollSubmissionSelection();
							$submissionSelection->pollSubmissionId = $submission->id;
							$submissionSelection->pollOptionId = $value;
							$submissionSelection->insert();
						}
					}else{
						$submissionSelection = new QuickPollSubmissionSelection();
						$submissionSelection->pollSubmissionId = $submission->id;
						$submissionSelection->pollOptionId = $_REQUEST['pollOption'];
						$submissionSelection->insert();
					}
				}
				//if (empty($this->quickPoll->submissionResultText)) {
					$interface->assign('submissionResultText', 'Thank you for your response.');
				//} else {
				//	$interface->assign('submissionResultText', $this->form->submissionResultText);
				//}
			}
		} else {
			$interface->assign('submissionError', 'The poll was not submitted correctly');
		}

		$this->display('quickPollResults.tpl', $this->quickPoll->title, '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->quickPoll->title . ' Submission', true);
		if (UserAccount::userHasPermission([
			'Administer All Quick Polls',
			'Administer Library Quick Polls',
		])) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls?id=' . $this->quickPoll->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}