<?php

require_once ROOT_DIR . '/JSON_Action.php';

class CourseReserves_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	function getAddBrowseCategoryFromCourseReservesForm() {
		global $interface;

		// Select List Creation using Object Editor functions
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$temp = SubBrowseCategories::getObjectStructure('');
		$temp['subCategoryId']['values'] = [0 => 'Select One'] + $temp['subCategoryId']['values'];
		// add default option that denotes nothing has been selected to the options list
		// (this preserves the keys' numeric values (which is essential as they are the Id values) as well as the array's order)
		// btw addition of arrays is kinda a cool trick.
		$interface->assign('propName', 'addAsSubCategoryOf');
		$interface->assign('property', $temp['subCategoryId']);

		// Display Page
		$interface->assign('reserveId', strip_tags($_REQUEST['reserveId']));
		return [
			'title' => translate([
				'text' => 'Add as Browse Category to Home Page',
				'isAdminFacing' => 'true',
			]),
			'modalBody' => $interface->fetch('Browse/newBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>" . translate([
					'text' => 'Create Category',
					'isAdminFacing' => 'true',
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function sendEmail() {
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['reserveId']) && ctype_digit($_REQUEST['reserveId'])) { // validly formatted List Id
			$reserveId = $_REQUEST['reserveId'];
			$to = $_REQUEST['to'];
			$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : '';
			$message = $_REQUEST['message'];

			//Load the course reserve
			require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
			$courseReserve = new CourseReserve();
			$courseReserve->id = $reserveId;
			if ($courseReserve->find(true)) {
				// Build List
				$listEntries = $courseReserve->getTitles();
				$interface->assign('listEntries', $listEntries);

				$titleDetails = $courseReserve->getCourseReserveRecords(0, -1, 'recordDrivers');
				// get all titles for email list, not just a page's worth
				$interface->assign('titles', $titleDetails);
				$interface->assign('list', $courseReserve);

				if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)) {
					$interface->assign('from', $from);
					$interface->assign('message', $message);
					$body = $interface->fetch('Emails/course-reserve.tpl');

					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mail = new Mailer();
					$subject = $courseReserve->getTitle();
					$emailResult = $mail->send($to, $subject, $body);

					if ($emailResult === true) {
						$result = [
							'result' => true,
							'message' => 'Your email was sent successfully.',
						];
					} elseif (($emailResult instanceof AspenError)) {
						$result = [
							'result' => false,
							'message' => "Your email message could not be sent: {$emailResult->getMessage()}.",
						];
					} else {
						$result = [
							'result' => false,
							'message' => 'Your email message could not be sent due to an unknown error.',
						];
						global $logger;
						$logger->log("Mail List Failure (unknown reason), parameters: $to, $from, $subject, $body", Logger::LOG_ERROR);
					}
				} else {
					$result = [
						'result' => false,
						'message' => 'Sorry, we can&apos;t send emails with html or other data in it.',
					];
				}
			}
		} else { // Invalid listId
			$result = [
				'result' => false,
				'message' => "Invalid Course Reserve Id. Your email message could not be sent.",
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEmailCourseReserveForm() {
		global $interface;
		if (isset($_REQUEST['reserveId']) && ctype_digit($_REQUEST['reserveId'])) {
			$reserveId = $_REQUEST['reserveId'];

			$interface->assign('reserveId', $reserveId);
			return [
				'title' => translate([
					'text' => 'Email Course Reserve',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('CourseReserves/emailCourseReservePopup.tpl'),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailCourseReserveForm\').submit();">' . translate([
						'text' => 'Send Email',
						'isPublicFacing' => true,
					]) . '</span>',
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must provide the id of the course reserve to email',
					'isPublicFacing' => true,
				]),
			];
		}
	}
}
