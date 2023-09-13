<?php
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class WebBuilder_SubmitQuickPoll extends Action {
	private $quickPoll;

	function launch() {
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmission.php';
		require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmissionSelection.php';
		$id = strip_tags($_REQUEST['id']);
		$submissionErrors = [];
		$submissionSuccess = false;
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
						$submissionErrors[] = translate([
							'text' => 'The CAPTCHA response was incorrect, please try again.',
							'isPublicFacing' => true,
						]);
						$processQuickPoll = false;
					}
				} else {
					$submissionErrors[] = translate([
						'text' => 'You must be logged in to submit a response, please login and try again.',
						'isPublicFacing' => true,
					]);
					$processQuickPoll = false;
				}
			}

			if ($processQuickPoll) {
				//Validate the poll
				$formDataIsValid = true;
				if (!empty($_REQUEST['name'])) {
					if (strip_tags($_REQUEST['name']) != $_REQUEST['name']) {
						$submissionErrors[] = translate([
							'text' => 'Invalid name entered.',
							'isPublicFacing' => true,
						]);
						$formDataIsValid = false;
					}
					if (mb_strlen($_REQUEST['name']) > 255) {
						$submissionErrors[] = translate([
							'text' => 'Invalid name entered.',
							'isPublicFacing' => true,
						]);
						$formDataIsValid = false;
					}
				} else if ($this->quickPoll->requireName) {
					$submissionErrors[] = translate([
						'text' => 'Please enter your name.',
						'isPublicFacing' => true,
					]);
					$formDataIsValid = false;
				}
				if (!empty($_REQUEST['email'])) {
					if (strip_tags($_REQUEST['email']) != $_REQUEST['email']) {
						$submissionErrors[] = translate([
							'text' => 'Invalid email entered.',
							'isPublicFacing' => true,
						]);
						$formDataIsValid = false;
					}
					if (mb_strlen($_REQUEST['email']) > 255) {
						$submissionErrors[] = translate([
							'text' => 'Invalid email entered.',
							'isPublicFacing' => true,
						]);
						$formDataIsValid = false;
					}
				} else if ($this->quickPoll->requireEmail) {
					$submissionErrors[] = translate([
						'text' => 'Please enter your email.',
						'isPublicFacing' => true,
					]);
					$formDataIsValid = false;
				}
				if (empty($_REQUEST['pollOption'])) {
					$submissionErrors[] = translate([
						'text' => 'At least one option must be selected.',
						'isPublicFacing' => true,
					]);
					$formDataIsValid = false;
				} else {
					if (is_array($_REQUEST['pollOption'])) {
						foreach ($_REQUEST['pollOption'] as $selectedOption => $value) {
							if (!is_numeric($value)) {
								$submissionErrors[] = translate([
									'text' => 'Invalid option selected.',
									'isPublicFacing' => true,
								]);
								$formDataIsValid = false;
							}
						}
					}else{
						if (!is_numeric($_REQUEST['pollOption'])) {
							$submissionErrors[] = translate([
								'text' => 'Invalid option selected.',
								'isPublicFacing' => true,
							]);
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

					$submissionSuccess = translate([
						'text' => 'Thank you for your response.',
						'isPublicFacing' => true,
					]);

					if (!empty($this->quickPoll->submissionResultText)) {
						$submissionSuccess = $this->quickPoll->submissionResultText;
					}
				}

			}
		} else {
			$submissionErrors[] = translate([
				'text' => 'The poll was not submitted correctly',
				'isPublicFacing' => true,
			]);
		}

		$interface->assign('submissionError', $submissionErrors);
		$interface->assign('submissionResultText', $submissionSuccess);
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