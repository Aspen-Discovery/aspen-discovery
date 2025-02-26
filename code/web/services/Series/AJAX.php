<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Series_AJAX extends JSON_Action {

	/** @noinspection PhpUnused */
	function sendEmail() {
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['seriesId']) && ctype_digit($_REQUEST['seriesId'])) { // validly formatted List Id
			$seriesId = $_REQUEST['seriesId'];
			$to = $_REQUEST['to'];
			$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : '';
			$message = $_REQUEST['message'];

			//Load the course reserve
			require_once ROOT_DIR . '/sys/Series/Series.php';
			$series = new Series();
			$series->id = $seriesId;
			if ($series->find(true)) {
				// Build List
				$listEntries = $series->getTitles();
				$interface->assign('listEntries', $listEntries);

				$titleDetails = $series->getSeriesRecords(0, -1, 'recordDrivers', $_REQUEST['sort'] ?? 'volume asc');
				// get all titles for email list, not just a page's worth
				$interface->assign('titles', $titleDetails);
				$interface->assign('list', $series);

				if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)) {
					$interface->assign('from', $from);
					$interface->assign('message', $message);
					$body = $interface->fetch('Emails/series.tpl');

					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mail = new Mailer();
					$subject = "Series: " . $series->displayName;
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
				'message' => "Invalid Series Id. Your email message could not be sent.",
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEmailSeriesForm() {
		global $interface;
		if (isset($_REQUEST['seriesId']) && ctype_digit($_REQUEST['seriesId'])) {
			$seriesId = $_REQUEST['seriesId'];

			$interface->assign('seriesId', $seriesId);
			return [
				'title' => translate([
					'text' => 'Email Series',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('Series/emailSeriesPopup.tpl'),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailSeriesForm\').submit();">' . translate([
						'text' => 'Send Email',
						'isPublicFacing' => true,
					]) . '</span>',
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must provide the id of the series to email',
					'isPublicFacing' => true,
				]),
			];
		}
	}
}
