<?php

require_once ROOT_DIR . '/sys/NovelistFactory.php';

class GroupedWork_Series extends Action {
	private $seriesTitle;

	function launch() {
		global $interface;
		global $timer;
		global $logger;

		// Hide Covers when the user has set that setting on the Search Results Page
		$this->setShowCovers();

		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if (!$recordDriver->isValid()) {
			$interface->assign('id', $id);
			$logger->log("Did not find a record for id {$id} in solr.", Logger::LOG_DEBUG);
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}
		$timer->logTime('Initialized the Record Driver');

		$novelist = NovelistFactory::getNovelist();
		$seriesData = $novelist->getSeriesTitles($id, $recordDriver->getISBNs());

		if ($seriesData == null) {
			$interface->assign('error', translate([
				'text' => 'Could not load series data',
				'isPublicFacing' => true,
			]));
		} else {
			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			foreach ($groupedWorkDisplaySettings->showInSearchResultsMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}

			//Loading the series title is not reliable.  Do not try to load it.
			$this->seriesTitle = null;
			$seriesAuthors = [];
			$resourceList = [];
			$seriesTitles = $seriesData->getSeriesTitles();
			$recordIndex = 1;
			if (isset($seriesTitles) && is_array($seriesTitles)) {
				foreach ($seriesTitles as $key => $title) {
					if (isset($title['series']) && strlen($title['series']) > 0 && !(isset($seriesTitle))) {
						$this->seriesTitle = $title['series'];
						$interface->assign('seriesTitle', $this->seriesTitle);
					}
					if (isset($title['author'])) {
						$author = preg_replace('/[^\w]*$/i', '', $title['author']);
						$seriesAuthors[$author] = $author;
					}
					$interface->assign('recordIndex', $recordIndex);
					$interface->assign('resultIndex', $recordIndex++);
					if ($title['libraryOwned']) {
						/** @var GroupedWorkDriver $tmpRecordDriver */
						$tmpRecordDriver = $title['recordDriver'];
						$resourceList[] = $interface->fetch($tmpRecordDriver->getSearchResult('list'));
					} else {
						$interface->assign('record', $title);
						$resourceList[] = $interface->fetch('RecordDrivers/Index/nonowned_result.tpl');
					}
				}
				$interface->assign('recordEnd', count($seriesTitles));
				$interface->assign('recordCount', count($seriesTitles));
			} else {
				$interface->assign('recordEnd', 0);
				$interface->assign('recordCount', 0);
			}

			$interface->assign('seriesAuthors', $seriesAuthors);
			$interface->assign('recordSet', $seriesTitles);
			$interface->assign('resourceList', $resourceList);

			$interface->assign('recordStart', 1);


			$interface->assign('recordDriver', $recordDriver);

			$this->setShowCovers();
		}

		// Display Page
		$this->display('view-series.tpl', $this->seriesTitle, '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', $this->seriesTitle, false);
		return $breadcrumbs;
	}
}