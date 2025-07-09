<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class CourseReserve extends DataObject {
	public $__table = 'course_reserve';
	public $id;
	public $created;
	public $deleted;
	public $dateUpdated;
	public $courseLibrary;
	public $courseInstructor;
	public $courseNumber;
	public $courseTitle;

	public static function getSourceListsForBrowsingAndCarousels() {
		$courseReserves = new CourseReserve();
		$courseReserves->deleted = 0;
		$courseReserves->orderBy('courseNumber, courseTitle, courseInstructor');
		$courseReserves->find();
		$sourceLists = [];
		$sourceLists[-1] = 'Generate from search term and filters';
		while ($courseReserves->fetch()) {
			$numItems = $courseReserves->numTitlesOnReserve();
			if ($numItems > 0) {
				$sourceLists[$courseReserves->id] = "($courseReserves->id) {$courseReserves->getTitle()} - $numItems entries";
			}
		}
		return $sourceLists;
	}

	public function getTitle() {
		return $this->courseNumber . ' ' . $this->courseTitle . ' - ' . str_replace('|',', ', $this->courseInstructor);
	}

	public function getNumericColumnNames(): array {
		return ['deleted'];
	}

	function numTitlesOnReserve() {
		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserveEntry.php';
		$reserveEntries = new CourseReserveEntry();
		$reserveEntries->courseReserveId = $this->id;

		return $reserveEntries->count();
	}

	/**
	 * @var array An array of resources keyed by the course reserve id since we can iterate over multiple course reserves while fetching from the DB
	 */
	private $reserveTitles = [];

	/**
	 * @return array      of list entries
	 */
	function getTitles() {
		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserveEntry.php';
		$reserveEntry = new CourseReserveEntry();
		$reserveEntry->courseReserveId = $this->id;
		$reserveEntry->orderBy('title');

		// These conditions retrieve course reserve items with a valid groupedWorkId .
		// (This prevents list strangeness when our searches don't find the ID in the search indexes)

		$reserveEntries = [];
		$idsBySource = [];
		$reserveEntry->find();
		while ($reserveEntry->fetch()) {
			if (!array_key_exists($reserveEntry->source, $idsBySource)) {
				$idsBySource[$reserveEntry->source] = [];
			}
			$idsBySource[$reserveEntry->source][] = $reserveEntry->sourceId;
			$tmpListEntry = [
				'source' => $reserveEntry->source,
				'sourceId' => $reserveEntry->sourceId,
				'title' => $reserveEntry->title,
				'courseReserveEntryId' => $reserveEntry->id,
				'courseReserveEntry' => clone($reserveEntry),
			];

			$reserveEntries[] = $tmpListEntry;
		}
		$reserveEntry->__destruct();
		$reserveEntry = null;
		return [
			'courseReserveEntries' => $reserveEntries,
			'idsBySource' => $idsBySource,
		];
	}

	/**
	 * @return CourseReserveEntry[]|null
	 */
	function getReserveTitles() {
		if (isset($this->reserveTitles[$this->id])) {
			return $this->reserveTitles[$this->id];
		}
		$titles = $this->getTitles();
		$this->reserveTitles[$this->id] = [];
		foreach ($titles['courseReserveEntries'] as $reserveEntry) {
			$this->reserveTitles[$this->id][] = $reserveEntry['courseReserveEntry'];
		}

		return $this->reserveTitles[$this->id];
	}

	var $catalog;


	/**
	 * @param int $start position of first list item to fetch (0 based)
	 * @param int $numItems Number of items to fetch for this result
	 * @param string $format The format of the records, valid values are html, summary, recordDrivers, citation
	 * @param string $citationFormat How citations should be formatted
	 * @return array     Array of HTML to display to the user
	 */
	public function getCourseReserveRecords($start, $numItems, $format, $citationFormat = null) {
		//Get all entries for the list
		$courseReserveEntryInfo = $this->getTitles();

		//Trim to the number of records we want to return
		if ($numItems > 0) {
			$filteredReserveEntries = array_slice($courseReserveEntryInfo['courseReserveEntries'], $start, $numItems);
		} else {
			$filteredReserveEntries = $courseReserveEntryInfo['courseReserveEntries'];
		}

		$filteredIdsBySource = [];
		foreach ($filteredReserveEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)) {
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load the actual items from each source
		$listResults = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds) {
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			} else {
				$records = $searchObject->getRecords($sourceIds);
				if ($format == 'html') {
					$listResults = $listResults + $this->getResultListHTML($records, $filteredReserveEntries, $start);
				} elseif ($format == 'summary') {
					$listResults = $listResults + $this->getResultListSummary($records, $filteredReserveEntries);
				} elseif ($format == 'recordDrivers') {
					$listResults = $listResults + $this->getResultListRecordDrivers($records, $filteredReserveEntries);
				} elseif ($format == 'citations') {
					$listResults = $listResults + $this->getResultListCitations($records, $filteredReserveEntries, $citationFormat);
				} else {
					AspenError::raiseError("Unknown display format $format in getCourseReserveRecords");
				}
			}
		}

		if ($format == 'html') {
			//Add in non-owned results for anything that is left
			global $interface;
			foreach ($filteredReserveEntries as $listPosition => $courseReserveEntryInfo) {
				if (!array_key_exists($listPosition, $listResults)) {
					$interface->assign('recordIndex', $listPosition + 1);
					$interface->assign('resultIndex', $listPosition + $start + 1);
					$interface->assign('courseReserveEntryId', $courseReserveEntryInfo['courseReserveEntryId']);
					if (!empty($courseReserveEntryInfo['title'])) {
						$interface->assign('deletedEntryTitle', $courseReserveEntryInfo['title']);
					} else {
						$interface->assign('deletedEntryTitle', '');
					}
					$listResults[$listPosition] = $interface->fetch('MyAccount/deletedListEntry.tpl');
				}
			}
		}

		ksort($listResults);
		return $listResults;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param RecordInterface[] $records Records retrieved from the getRecords method of a SolrSearcher
	 * @param array $allListEntryIds optional list of IDs to re-order the records by (ie User List sorts)
	 * @param int $startRecord The first record being displayed
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getCourseReserveResultListHTML($records, $allListEntryIds, $startRecord = 0) {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var CourseReservesRecordDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$recordDriver->setListNotes($currentId['notes']);
					$recordDriver->setListEntryId($currentId['courseReserveEntryId']);
					$current = $recordDriver;
					break;
				}
			}
			$interface->assign('recordIndex', $listPosition + 1);
			$interface->assign('resultIndex', $listPosition + $startRecord + 1);

			if (!empty($current)) {
				//Get information from list entry
				$interface->assign('listEntryId', $current->getListEntryId());

				$interface->assign('recordDriver', $current);
				$html[$listPosition] = $interface->fetch($current->getCourseReserveEntry($this->id));
			}
		}
		return $html;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param RecordInterface[] $records Records retrieved from the getRecords method of a SolrSearcher
	 * @param array $allListEntryIds optional list of IDs to re-order the records by (ie User List sorts)
	 * @param int $startRecord The first record being displayed
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getResultListHTML($records, $allListEntryIds, $startRecord = 0) {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var GroupedWorkDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$recordDriver->setListEntryId($currentId['courseReserveEntryId']);
					$current = $recordDriver;
					break;
				}
			}
			$interface->assign('recordIndex', $listPosition + 1);
			$interface->assign('resultIndex', $listPosition + $startRecord + 1);

			if (!empty($current)) {
				//Get information from list entry
				$interface->assign('courseReserveEntryId', $current->getListEntryId());

				$interface->assign('recordDriver', $current);
				$html[$listPosition] = $interface->fetch($current->getCourseReserveEntry($this->id));
			}
		}
		return $html;
	}

	private function getResultListSummary($records, $allListEntryIds) {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var CourseReservesRecordDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var int $docIndex
			 * @var CourseReservesRecordDriver $recordDriver
			 */
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (!empty($current)) {
				$results[$listPosition] = $current->getSummaryInformation();
			}
		}
		return $results;
	}

	private function getResultListCitations($records, $allListEntryIds, $format) {
		global $interface;
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var CourseReservesRecordDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (!empty($current)) {
				$results[$listPosition] = $interface->fetch($current->getCitation($format));
			}
		}
		return $results;
	}

	private function getResultListRecordDrivers($records, $allListEntryIds) {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var int $docIndex
			 * @var IndexRecordDriver $recordDriver
			 */
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (!empty($current)) {
				$results[$listPosition] = $current;
			}
		}
		return $results;
	}

	/**
	 * @param int $start position of first list item to fetch
	 * @param int $numItems Number of items to fetch for this result
	 * @return array     Array of HTML to display to the user
	 */
	public function getBrowseRecords($start, $numItems) {
		//Get all entries for the list
		$listEntryInfo = $this->getTitles();

		//Trim to the number of records we want to return
		$filteredListEntries = array_slice($listEntryInfo['courseReserveEntries'], $start, $numItems);

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)) {
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load catalog items
		$browseRecords = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds) {
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			} else {
				$records = $searchObject->getRecords($sourceIds);
				$browseRecords = array_merge($browseRecords, $this->getBrowseRecordHTML($records, $listEntryInfo['courseReserveEntries'], $start));
			}
		}

		//Properly sort items
		ksort($browseRecords);

		return $browseRecords;
	}

	/**
	 * @param int $start position of first list item to fetch
	 * @param int $numItems Number of items to fetch for this result
	 * @return array     Array of HTML to display to the user
	 */
	public function getBrowseRecordsRaw($start, $numItems) {
		//Get all entries for the list
		$listEntryInfo = $this->getTitles();

		//Trim to the number of records we want to return
		$filteredListEntries = array_slice($listEntryInfo['courseReserveEntries'], $start, $numItems);

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)) {
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load catalog items
		$browseRecords = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds) {
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			} else {
				$records = $searchObject->getRecords($sourceIds);
				foreach ($records as $key => $record) {
					if ($record instanceof ListsRecordDriver) {
						$browseRecords[$key] = $record->getFields();
					} else {
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$groupedWorkDriver = new GroupedWorkDriver($key);
						if ($groupedWorkDriver->isValid()) {
							$browseRecords[$key]['id'] = $groupedWorkDriver->getPermanentId();
							$browseRecords[$key]['title_display'] = $groupedWorkDriver->getShortTitle();
						} else {
							$browseRecords[$key] = $record;
						}

					}
				}
			}
		}

		//Properly sort items
		ksort($browseRecords);

		return $browseRecords;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param RecordInterface[] $records Records retrieved from the getRecords method of a SolrSearcher
	 * @param array $allListEntryIds
	 * @param int $start
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getBrowseRecordHTML($records, $allListEntryIds, $start) {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (empty($current)) {
				continue; // In the case the record wasn't found, move on to the next record
			} else {
				$interface->assign('recordIndex', $listPosition + 1);
				$interface->assign('resultIndex', $listPosition + $start + 1);
				$html[$listPosition] = $interface->fetch($recordDriver->getBrowseResult());
			}
		}
		return $html;
	}

	/**
	 * @return array
	 */
	public static function getCourseReserveSortOptions() {
		return CourseReserve::$__courseReserveSortOptions;
	}

	public function getSpotlightTitles(CollectionSpotlight $collectionSpotlight): array {
		$allEntries = $this->getReserveTitles();
		$results = [];
		$index = 0;
		foreach ($allEntries as $entry) {
			$recordDriver = $entry->getRecordDriver();
			if ($recordDriver !== null && $recordDriver->isValid()) {
				$results[$index] = $recordDriver->getSpotlightResult($collectionSpotlight, $index);
				$index++;
				if ($index >= $collectionSpotlight->numTitlesToShow) {
					break;
				}
			}
		}
		return $results;
	}

	public static function getUserListsForSaveForm($source, $sourceId) {
		global $interface;
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		//Get a list of all lists for the user
		$containingLists = [];
		$nonContainingLists = [];

		$user = UserAccount::getActiveUserObj();

		$userLists = new CourseReserve();
		$userLists->user_id = UserAccount::getActiveUserId();
		$userLists->whereAdd('deleted = 0');
		$userLists->orderBy('title');
		$userLists->find();
		while ($userLists->fetch()) {
			//Check to see if the user has already added the title to the list.
			$userListEntry = new UserListEntry();
			$userListEntry->listId = $userLists->id;
			$userListEntry->source = $source;
			$userListEntry->sourceId = $sourceId;
			if ($userListEntry->find(true)) {
				$containingLists[] = [
					'id' => $userLists->id,
					'title' => $userLists->title,
				];
			} else {
				$selected = $user->lastListUsed == $userLists->id;
				$nonContainingLists[] = [
					'id' => $userLists->id,
					'title' => $userLists->title,
					'selected' => $selected,
				];
			}
		}

		$interface->assign('containingLists', $containingLists);
		$interface->assign('nonContainingLists', $nonContainingLists);

		return [
			'containingLists' => $containingLists,
			'nonContainingLists' => $nonContainingLists,
		];
	}

	public static function getUserListsForRecord($source, $sourceId) {
		$userLists = [];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->source = $source;
		$userListEntry->sourceId = $sourceId;
		$userListEntry->find();
		while ($userListEntry->fetch()) {
			//Check to see if the user has access to the list
			$userList = new CourseReserve();
			$userList->id = $userListEntry->listId;
			if ($userList->find(true)) {
				$okToShow = false;
				$key = '';
				if (!$userList->deleted) {
					if (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $userList->user_id) {
						$okToShow = true;
						$key = 0 . strtolower($userList->title);
					} elseif ($userList->public == 1 && $userList->searchable == 1) {
						$okToShow = true;
						$key = 1 . strtolower($userList->title);
					}
				}
				if ($okToShow) {
					$userLists[$key] = [
						'link' => '/MyAccount/MyList/' . $userList->id,
						'title' => $userList->title,
					];
				}
			}
		}
		ksort($userLists);
		return $userLists;
	}
}