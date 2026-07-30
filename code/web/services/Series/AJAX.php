<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Series_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Series');
		parent::launch($method);
	}

	/** @noinspection PhpUnused */
	function sendEmail() : array {
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['seriesId']) && ctype_digit($_REQUEST['seriesId'])) { // validly formatted Series ID
			$seriesId = $_REQUEST['seriesId'];
			$to = $_REQUEST['to'];
			$from = $_REQUEST['from'] ?? '';
			$message = $_REQUEST['message'];

			//Load the series
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

				if (!str_contains($message, 'http') && !str_contains($message, 'mailto') && $message == strip_tags($message)) {
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
			} else {
				$result = [
					'result' => false,
					'message' => 'Sorry, we could not find that series.',
				];
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
	function getEmailSeriesForm() : array {
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

	/** @noinspection PhpUnused */
	function getGroupSeriesSearchForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission('Administer Series');
		$this->checkRequiredParameters(['id']);

		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isAdminFacing' => true,
			]),
		];

		require_once ROOT_DIR . '/sys/Series/Series.php';
		$series = new Series();
		$id = $_REQUEST['id'];
		$series->seriesPermanentId = $id;
		if ($series->find(true)) {
			global $interface;
			$interface->assign('id', $id);
			$interface->assign('series', $series);

			$searchId = $_REQUEST['searchId'];
			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			$searchObject = $searchObject->restoreSavedSearch($searchId, false);

			if (!empty($_REQUEST['page'])) {
				$searchObject->setPage($_REQUEST['page']);
			}

			$searchResults = $searchObject->processSearch(false, false);
			$availableSeries = [];
			$availableSeries[-1] = translate([
				'text' => "Select the primary series",
				'isAdminFacing' => true,
			]);
			$recordIndex = ($searchObject->getPage() - 1) * $searchObject->getLimit();
			foreach ($searchResults['response']['docs'] as $doc) {
				$recordIndex++;
				if ($doc['id'] != $id) {
					$primarySeries = new Series();
					$primarySeries->seriesPermanentId = $doc['id'];
					if ($primarySeries->find(true)) {
						$availableSeries[$doc['id']] = "$recordIndex) $primarySeries->groupedWorkSeriesTitle $primarySeries->author";
					}
				}
			}
			$interface->assign('availableSeries', $availableSeries);

			$results = [
				'success' => true,
				'title' => translate([
					'text' => "Group this series with another",
					'isAdminFacing' => true,
				]),
				'modalBody' => $interface->fetch("Series/groupSeriesSearchForm.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Series.processGroupSeriesForm()'>" . translate([
						'text' => "Group",
						'isAdminFacing' => true,
					]) . "</button>",
			];
		} else {
			$results['message'] = translate([
				'text' => "Could not find a series with that id",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function processGroupSeriesForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission('Administer Series');
		$this->checkRequiredParameters(['id']);

		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/sys/Series/Series.php';
		$originalSeries = new Series();
		$originalSeries->seriesPermanentId = $id;

		if (!empty($id) && $originalSeries->find(true)) {
			$seriesToGroupWithId = $_REQUEST['groupSeriesId'];
			$seriesToGroupWith = new Series();
			$seriesToGroupWith->seriesPermanentId = $seriesToGroupWithId;
			if (!empty($seriesToGroupWithId) && $seriesToGroupWith->find(true)) {
				if (empty($seriesToGroupWith->seriesToGroupWithId)){
					$originalSeries->getSeriesMembers();
					$originalSeries->seriesToGroupWithId = $seriesToGroupWithId;
					$originalSeries->isIndexed = 0;
					$originalSeries->dateUpdated = time();
					$originalSeries->update();

					$results['success'] = true;
					$results['message'] = translate([
						'text' => "Your series have been grouped successfully, the index will update shortly.",
						'isAdminFacing' => true,
					]);
				} else {
					$results['message'] = translate([
						'text' => "The series you are trying to group to is already grouped to another series. Please wait for the index to update.",
						'isAdminFacing' => true,
					]);
				}

			} else {
				$results['message'] = translate([
					'text' => "Could not find series to group with.",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "Could not find series for original id.",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function ungroupSeries(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission('Administer Series');
		$this->checkRequiredParameters(['id']);

		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$seriesToUngroupId = $_REQUEST['id'];
		$seriesGroupedOntoId = $_REQUEST['groupedWithSeriesId'];

		require_once ROOT_DIR . '/sys/Series/Series.php';
		$seriesToUngroup = new Series();
		$seriesToUngroup->seriesPermanentId = $seriesToUngroupId;

		if (!empty($seriesToUngroupId) && $seriesToUngroup->find(true)) {
			$seriesGroupedOnto = new Series();
			$seriesGroupedOnto->seriesPermanentId = $seriesGroupedOntoId;
			if (!empty($seriesGroupedOntoId) && $seriesGroupedOnto->find(true)) {
				$seriesToUngroup->getSeriesMembers();
				$seriesToUngroup->seriesToGroupWithId = '';
				$seriesToUngroup->isIndexed = 1;
				$seriesToUngroup->dateUpdated = time();
				$seriesToUngroup->update();

				$results['success'] = true;
				$results['message'] = translate([
					'text' => "Your series have been ungrouped successfully, the index will update shortly.",
					'isAdminFacing' => true,
				]);
			} else {
				$results['message'] = translate([
					'text' => "Could not find series to ungroup.",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "Could not find series for original id.",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}
}
