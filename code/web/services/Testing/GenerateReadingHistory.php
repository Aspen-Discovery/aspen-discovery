<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Testing_GenerateReadingHistory extends Admin_Admin {
	function launch() {
		global $interface;

		if (isset($_REQUEST['generateReadingHistory'])) {
			set_time_limit(0);
			$results = [
				'success' => false,
				'message' => 'Unknown error generating reading history'
			];
			$patronBarcode = isset($_REQUEST['patronBarcode']) ? $_REQUEST['patronBarcode'] : '';
			if (empty($patronBarcode)) {
				$results['message'] = 'No patron barcode was supplied';
			}else{
				$user = new User();
				$user->ils_barcode = $patronBarcode;
				if ($user->find(true)) {
					require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
					global $aspen_db;
					$getGroupedWorkIdStmt = $aspen_db->prepare("SELECT * FROM grouped_work AS t1 JOIN (SELECT id FROM grouped_work ORDER BY RAND() LIMIT 1) as t2 ON t1.id=t2.id", [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);

					$numberOfYears = isset($_REQUEST['numberOfYears']) ? $_REQUEST['numberOfYears'] : 1;
					$minEntriesPerMonth = isset($_REQUEST['minEntriesPerMonth']) ? $_REQUEST['minEntriesPerMonth'] : 0;
					$maxEntriesPerMonth = isset($_REQUEST['maxEntriesPerMonth']) ? $_REQUEST['maxEntriesPerMonth'] : 10;
					$clearExistingReadingHistory = isset($_REQUEST['clearExistingReadingHistory']) ? $_REQUEST['clearExistingReadingHistory'] == 'on' : false;
					if ($minEntriesPerMonth > $maxEntriesPerMonth) {
						$tmp = $minEntriesPerMonth;
						$minEntriesPerMonth = $maxEntriesPerMonth;
						$maxEntriesPerMonth = $tmp;
					}
					$results['message'] = '';
					if ($clearExistingReadingHistory) {
						$readingHistoryDB = new ReadingHistoryEntry();
						$readingHistoryDB->userId = $user->id;
						$numDeleted = $readingHistoryDB->delete(true);
						$user->totalCostSavings = 0;
						$results['message'] = "Removed $numDeleted Reading History Entries.<br/>";
					}

					//Load Record Sources
					require_once ROOT_DIR . '/sys/Indexing/IndexedRecordSource.php';
					$recordSource = new IndexedRecordSource();
					$recordSource->find();
					$sources = [];
					while ($recordSource->fetch()) {
						if (empty($recordSource->subSource)) {
							$sources[$recordSource->id] = $recordSource->source;
						}else{
							$sources[$recordSource->id] = $recordSource->source . ':' . $recordSource->subSource;
						}
					}

					require_once ROOT_DIR . '/sys/Indexing/IndexedFormat.php';
					$format = new IndexedFormat();
					$formats = $format->fetchAll('id', 'format');

					require_once ROOT_DIR . '/sys/ReplacementCost.php';
					$replacementCosts = ReplacementCost::getReplacementCostsByFormat();

					//Start a search for random grouped works
					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWork = new GroupedWork();
					$groupedWork->orderBy('RAND()');
					$groupedWork->find();

					$currentYear = date('Y', time());
					$numEntriesGenerated = 0;
					//Loop through the years to generate
					for ($year = $currentYear; $year >= ($currentYear - $numberOfYears + 1); $year--) {
						//Loop through each month
						$maxMonth = 12;
						if ($year == $currentYear) {
							//Restrict the maximum month to this month
							$maxMonth = date('m', time());
						}
						for ($month = 1; $month <= $maxMonth; $month++) {
							$numEntriesToGenerate = rand($minEntriesPerMonth, $maxEntriesPerMonth);
							for ($entryNumber = 0; $entryNumber < $numEntriesToGenerate; $entryNumber++) {
								if ($groupedWork->fetch()){
									$readingHistoryEntry = new ReadingHistoryEntry();
									$readingHistoryEntry->userId = $user->id;
									$readingHistoryEntry->groupedWorkPermanentId =$groupedWork->permanent_id;
									$readingHistoryEntry->title = $groupedWork->full_title;
									$readingHistoryEntry->author = $groupedWork->author;
									//Grab a record at random from the grouped work
									require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
									$groupedWorkRecord = new GroupedWorkRecord();
									$groupedWorkRecord->groupedWorkId = $groupedWork->id;
									$groupedWorkRecord->orderBy('RAND()');
									$groupedWorkRecord->limit(0, 1);
									if ($groupedWorkRecord->find(true)) {
										$readingHistoryEntry->source = $sources[$groupedWorkRecord->sourceId];
										$readingHistoryEntry->sourceId = $groupedWorkRecord->recordIdentifier;
										if (array_key_exists($groupedWorkRecord->formatId, $formats)) {
											$readingHistoryEntry->format = $formats[$groupedWorkRecord->formatId];
										}else{
											//Hardcode something
											$readingHistoryEntry->format = 'Book';
										}
									}else{
										//Hardcode a format
										$readingHistoryEntry->format = 'Book';
									}
									$checkoutDay = rand(0, 28);
									$readingHistoryEntry->checkOutDate = strtotime("$year-$month-$checkoutDay");
									$readingHistoryEntry->checkInDate = $readingHistoryEntry->checkOutDate;
									$formatLower = strtolower($readingHistoryEntry->format);
									if (array_key_exists($formatLower, $replacementCosts)) {
										$readingHistoryEntry->costSavings = $replacementCosts[$formatLower];
										$user->totalCostSavings += $readingHistoryEntry->costSavings;
									}
									$readingHistoryEntry->insert();
									$numEntriesGenerated++;
									$getGroupedWorkIdStmt->closeCursor();
								}
							}
						}
					}
					$user->update();
					$results['success'] = true;
					$results['message'] .= "Successfully generated $numEntriesGenerated reading history entries.";
				}else{
					$results['message'] = 'That patron could not be found';
				}
			}
			$interface->assign('results', $results);
		}

		$this->display('generateReadingHistory.tpl', 'Generate Reading History', 'Greenhouse/greenhouse-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Testing/GenerateReadingHistory', 'Generate Reading History', true);
		return $breadcrumbs;
	}

	function canView() : bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}