<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

class UserList extends DataObject {
	public $__table = 'user_list';
	public $id;
	public $user_id;
	public $title;
	public $description;
	public $created;
	public $public;
	public $searchable;
	public $displayListAuthor;
	public $deleted;
	public $dateUpdated;
	public $deleteFromIndex;
	public $defaultSort;
	public $importedFrom;
	public $nytListModified;
	public $dateDeleted;
	public $deletedBy;

	public function getUniquenessFields(): array {
		return ['id'];
	}

	public static function getSourceListsForBrowsingAndCarousels() : array {
		$userLists = new UserList();
		$userLists->public = 1;
		$userLists->deleted = 0;
		$userLists->orderBy('title asc');
		$userLists->find();
		$sourceLists = [];
		$sourceLists[-1] = 'Generate from search term and filters';
		while ($userLists->fetch()) {
			$numItems = $userLists->numValidListItems();
			if ($numItems > 0) {
				$sourceLists[$userLists->id] = "($userLists->id) $userLists->title - $numItems entries";
			}
		}
		return $sourceLists;
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'user_id',
			'public',
			'deleted',
			'searchable',
			'displayListAuthor',
			'deleteFromIndex',
		];
	}


	// Used by FavoriteHandler as well//
	private static $__userListSortOptions = [
		// URL_value => SQL code for Order BY clause
		'title' => 'title ASC',
		'dateAdded' => 'dateAdded ASC',
		'recentlyAdded' => 'dateAdded DESC',
		'custom' => 'weight ASC', // Puts items with no set weight towards the end of the list.
		'author' => '',
	];

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the user list.',
				'storeDb' => true,
				'storeSolr' => false,
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'size' => 100,
				'maxLength' => 255,
				'label' => 'Title',
				'description' => 'The title of the item.',
				'required' => true,
				'storeDb' => true,
				'storeSolr' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'rows' => 3,
				'cols' => 80,
				'description' => 'A brief description of the file for indexing and display if there is not an existing record within the catalog.',
				'required' => false,
				'storeDb' => true,
				'storeSolr' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function numValidListItems() {
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

		return $listEntry->count();
	}

	function numValidListItemsForLiDA($version) {
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;
		if ($version < 24.02) {
			$listEntry->whereAdd("source <> 'Events'");
		}

		return $listEntry->count();
	}

	public function insert(string $context = '') : int|bool {
		if (empty($this->created)) {
			$this->created = time();
		}
		if (empty($this->dateUpdated)) {
			$this->dateUpdated = time();
		}
		if ($this->public == 0) {
			$this->searchable = 0;
			$this->displayListAuthor = 0;
		}
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return parent::insert();
	}

	public function update(string $context = '') : int|bool {
		if ($this->created == 0) {
			$this->created = time();
		}
		if ($this->public == 0) {
			$this->searchable = 0;
			$this->displayListAuthor = 0;
		}
		$this->dateUpdated = time();
		$result = parent::update();
		if ($result) {
			global $memCache;
			$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		}

		return $result;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		if ($hardDelete && !empty($this->id) && $this->id >= 1) {
			// Hard delete by marking for index cleanup and updating deletion information.
			$this->deleteFromIndex = 1;
			$this->deleted = 1;
			$this->dateDeleted = time();
			$this->deletedBy = UserAccount::getActiveUserId();
			$ret = $this->update();
			if ($ret) {
				require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
				$listEntry = new UserListEntry();
				$listEntry->listId = $this->id;
				$listEntry->delete(true);
			}
		} else {
			$ret = parent::delete($useWhere, $hardDelete);
		}

		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $ret;
	}

	/**
	 * @var array An array of resources keyed by the list id since we can iterate over multiple lists while fetching from the DB
	 */
	private $listTitles = [];

	/**
	 * @param null $sort optional SQL for the query's ORDER BY clause
	 * @return array      of list entries
	 */
	function getListEntries($sort = null, $forLiDA = false, $appVersion = 0, $start = 0, $numItems = 0) : array {
		global $interface;
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;
		if ($forLiDA){
			if($appVersion < 24.02) {
				$listEntry->whereAdd("source <> 'Events'");
			}
		}

		//Sort the list appropriately
		if (!empty($sort)) {
			$sortOptions = UserList::getSortOptions();
			if (array_key_exists($sort, $sortOptions)) {
				$listEntry->selectAdd();
				$listEntry->selectAdd("user_list_entry.*");
				if ($sort == "title") {
					//set cases for what to use as sorting title
					$listEntry->selectAdd('CASE WHEN user_list_entry.source = "GroupedWork" THEN groupedWork.full_title WHEN user_list_entry.source = "Lists" THEN userList.title WHEN user_list_entry.source = "Series" THEN series.groupedWorkSeriesTitle ELSE user_list_entry.title END AS ItemTitle');

					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWorkInfo = new GroupedWork();
					$listEntry->joinAdd($groupedWorkInfo, "LEFT", 'groupedWork', 'sourceId', 'permanent_id');

					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$userListInfo = new UserList();
					$listEntry->joinAdd($userListInfo, "LEFT", 'userList', 'sourceId', 'id');

					require_once ROOT_DIR . '/sys/Series/Series.php';
					$seriesInfo = new Series();
					$listEntry->joinAdd($seriesInfo, "LEFT", 'series', 'sourceId', 'id');
					$listEntry->orderBy("CASE WHEN ItemTitle IS NULL THEN 1 ELSE 0 END ASC, ItemTitle");
				} elseif ($sort == "author") {
					$listEntry->selectAdd('CASE WHEN user_list_entry.source = "GroupedWork" THEN groupedWork.author WHEN user_list_entry.source = "Series" THEN series.author ELSE NULL END AS ItemAuthor');
					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWorkInfo = new GroupedWork();
					$listEntry->joinAdd($groupedWorkInfo, "LEFT", 'groupedWork', 'sourceId', 'permanent_id');

					require_once ROOT_DIR . '/sys/Series/Series.php';
					$seriesInfo = new Series();
					$listEntry->joinAdd($seriesInfo, "LEFT", 'series', 'sourceId', 'id');
					$listEntry->orderBy("CASE WHEN ItemAuthor IS NULL THEN 1 ELSE 0 END ASC, ItemAuthor");
				} else {
					$listEntry->orderBy($sortOptions[$sort]);
				}
			}
		}

		if ($numItems > 0) {
			$listEntry->limit($start, $numItems);
		}

		// These conditions retrieve list items with a valid groupedWorkId or archive ID.
		// (This prevents list strangeness when our searches don't find the ID in the search indexes)

		$listEntries = [];
		$idsBySource = [];
		$listEntry->find();
		$entryPosition = $start; // Track the actual position.
		while ($listEntry->fetch()) {
			$entryPosition++;
			if (!array_key_exists($listEntry->source, $idsBySource)) {
				$idsBySource[$listEntry->source] = [];
			}
			$idsBySource[$listEntry->source][] = $listEntry->sourceId;
			$tmpListEntry = [
				'source' => $listEntry->source,
				'sourceId' => $listEntry->sourceId,
				'title' => $listEntry->title,
				'notes' => $listEntry->getNotes(),
				'listEntryId' => $listEntry->id,
				'listEntry' => $this->cleanListEntry(clone($listEntry)),
				'weight' => $listEntry->weight,
			];

			$listEntries[] = $tmpListEntry;
		}
		$listEntry->__destruct();
		$listEntry = null;

		if (($interface != null) && (($entryPosition != '') || ($entryPosition != null))) {
			$interface->assign('listEntryCount', $entryPosition);
		}

		return [
			'listEntries' => $listEntries,
			'idsBySource' => $idsBySource,
		];
	}

	/**
	 * @param ?string $sortName How records should be sorted, if no sort is provided, will use the default for the list
	 * @return UserListEntry[]
	 */
	function getListTitles(?string $sortName = null) : array {
		if (isset($this->listTitles[$this->id])) {
			return $this->listTitles[$this->id];
		}
		if ($sortName == null) {
			$sortName = $this->defaultSort;
		}
		$listEntries = $this->getListEntries($sortName);
		$this->listTitles[$this->id] = [];
		foreach ($listEntries['listEntries'] as $listEntry) {
			$this->listTitles[$this->id][] = $listEntry['listEntry'];
		}

		return $this->listTitles[$this->id];
	}

	var $catalog;

	/**
	 * @param UserListEntry $listEntry - The resource to be cleaned
	 * @return UserListEntry
	 */
	function cleanListEntry(UserListEntry $listEntry) : UserListEntry {
		//Filter list information for bad words as needed.
		if (!UserAccount::isLoggedIn() || $this->user_id != UserAccount::getActiveUserId()) {
			//Load all bad words.
			global $library;
			require_once ROOT_DIR . '/sys/LocalEnrichment/BadWord.php';
			$badWords = new BadWord();

			//Determine if we should censor bad words or hide the comment completely.
			$censorWords = $library->getGroupedWorkDisplaySettings()->hideCommentsWithBadWords == 0;
			//Filter Title
			$titleText = $badWords->censorBadWords($this->title);
			$this->title = $titleText;
			if ($censorWords) {
				//Filter description
				$descriptionText = $badWords->censorBadWords($this->description);
				$this->description = $descriptionText;

				//Filter notes
				$notesText = $badWords->censorBadWords($listEntry->notes);
				$listEntry->notes = $notesText;
			} else {
				//Check for bad words in the title or description
				if (isset($this->description)) {
					if ($badWords->hasBadWords($this->description)) {
						$this->description = '';
					}
				}
				//Filter notes
				if ($badWords->hasBadWords($listEntry->notes)) {
					$listEntry->notes = '';
				}
			}
		}
		return $listEntry;
	}

	/**
	 * @param String $listEntryToRemove
	 * @param bool $updateBrowseCategories
	 */
	function removeListEntry($listEntryToRemove, $updateBrowseCategories = true) {
		// Remove the Saved List Entry
		if ($listEntryToRemove instanceof UserListEntry) {
			$listEntryToRemove->delete(false, $updateBrowseCategories);
		} else {
			require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
			$listEntry = new UserListEntry();
			$listEntry->id = $listEntryToRemove;

			// update weights
			if ($listEntry->find(true)) {
				$userLists = new UserListEntry();
				$userLists->listId = $listEntry->listId;
				$userLists->find();
				$entries = [];
				while ($userLists->fetch()) {
					$entries[] = clone $userLists;
				}

				$entryIndex = $listEntry->weight;
				foreach ($entries as $entry) {
					$weight = $entry->weight;
					if ($weight > $entryIndex) {
						$weight--;
						$entry->weight = $weight;
						$entry->update();
					}
				}

			}

			$listEntry->delete(true);
		}

		unset($this->listTitles[$this->id]);

		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
	}

	private $_cleanDescription = null;

	/** @noinspection PhpUnused */
	function getCleanDescription(): ?string {
		if ($this->_cleanDescription == null) {
			$this->_cleanDescription = strip_tags($this->description, '<p><b><em><strong><i><br>');
		}
		return $this->_cleanDescription;
	}

	function getListAuthor(): ?string {
		if ($this->user_id != null) {
			$user = new User();
			$user->id = $this->user_id;
			if ($user->find(true)) {
				return $user->getDisplayName();
			}
		}
		return null;
	}

	/**
	 * remove all resources within this list
	 * @param bool $updateBrowseCategories
	 */
	function removeAllListEntries(bool $updateBrowseCategories = true) : void {
		$allListEntries = $this->getListTitles();
		foreach ($allListEntries as $listEntry) {
			$this->removeListEntry($listEntry, $updateBrowseCategories);
		}
	}

	/**
	 * @param int $start Position of first list item to fetch (0 based)
	 * @param int $numItems Number of items to fetch for this result.
	 * @param boolean $allowEdit Whether or not the list should be editable.
	 * @param string $format The format of the records; valid values are html, summary, recordDrivers, and citation.
	 * @param string|null $citationFormat How citations should be formatted.
	 * @param string|null $sortName How records should be sorted, if no sort is provided, will use the default for the list.
	 * @param boolean $forLiDA Whether or not the records are being requested by Aspen LiDA.
	 * @param float|int $appVersion If LiDA, include the version to ensure proper filtering when needed.
	 * @return array Array of HTML to display to the user.
	 */
	public function getListRecords(
		int $start, int $numItems, bool $allowEdit, string $format, string $citationFormat = null,
		string $sortName = null, bool $forLiDA = false, float|int $appVersion = 0
	): array {
		if ($sortName == null) {
			$sortName = $this->defaultSort;
		}

		// Because the DB does not persist an "author" column on the list itself,
		// pull it from the underlying record when the sort of list is rendered.
		/*if ($sortName === 'author') {
			$allEntriesInfo = $this->getListEntries(null, $forLiDA, $appVersion, 0, 0);
			$allEntries = $allEntriesInfo['listEntries'];
			$idsBySource = $allEntriesInfo['idsBySource'];
			$authorMap = [];
			foreach ($idsBySource as $sourceType => $sourceIds) {
				$searchObject = SearchObjectFactory::initSearchObject($sourceType);
				if ($searchObject !== false) {
					$records = $searchObject->getRecords($sourceIds);
					foreach ($records as $recordDriver) {
						$authorMap[$sourceType][$recordDriver->getId()] = trim($recordDriver->getPrimaryAuthor() ?? '');
					}
				}
			}

			$emptyFirst = [];
			$authorList = [];
			foreach ($allEntries as $idx => $entry) {
				$src = $entry['source'];
				$id = $entry['sourceId'];
				$auth = $authorMap[$src][$id] ?? '';
				$authorList[$idx] = $auth;
				$emptyFirst[$idx] = ($auth === '') ? 1 : 0;
			}

			array_multisort(
				$emptyFirst, SORT_ASC, SORT_NUMERIC,
				$authorList, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE,
				$allEntries
			);

			if ($numItems > 0) {
				$filteredListEntries = array_slice($allEntries, $start, $numItems);
			} else {
				$filteredListEntries = $allEntries;
			}
		} else {*/
			$listEntryInfo = $this->getListEntries($sortName, $forLiDA, $appVersion, $start, $numItems);
			$filteredListEntries = $listEntryInfo['listEntries'];
		//}

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
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
					$listResults = $listResults + $this->getResultListHTML($records, $filteredListEntries, $allowEdit, $start);
				} elseif ($format == 'summary') {
					$listResults = $listResults + $this->getResultListSummary($records, $filteredListEntries);
				} elseif ($format == 'recordDrivers') {
					$listResults = $listResults + $this->getResultListRecordDrivers($records, $filteredListEntries);
				} elseif ($format == 'citations') {
					$listResults = $listResults + $this->getResultListCitations($records, $filteredListEntries, $citationFormat);
				} else {
					AspenError::raiseError("Unknown display format $format in getListRecords");
				}
			}
		}

		if ($format == 'html') {
			//Add in non-owned results for anything that is left
			global $interface;
			foreach ($filteredListEntries as $listPosition => $listEntryInfo) {
				if (!array_key_exists($listPosition, $listResults)) {
					$interface->assign('recordIndex', $listPosition + 1);
					$interface->assign('resultIndex', $listPosition + $start + 1);
					$interface->assign('listEntryId', $listEntryInfo['listEntryId']);
					$interface->assign('listEntrySource', $listEntryInfo['source']);
					$interface->assign('bookCoverUrl', '');

					if ($listEntryInfo['source'] = "Events"){ //get covers for past events
						if (preg_match('`^communico`', $listEntryInfo['sourceId'])){
							$id = explode("communico_1_", $listEntryInfo['sourceId']);
							$id = $id[1];
							$coverUrl = "/bookcover.php?id={$id}&size=medium&type=communico_event";

							$interface->assign('bookCoverUrl', $coverUrl);
						} elseif (preg_match('`^libcal`', $listEntryInfo['sourceId'])){
							$id = explode("libcal_1_", $listEntryInfo['sourceId']);
							$id = $id[1];
							$coverUrl = "/bookcover.php?id={$id}&size=medium&type=springshare_libcal_event";

							$interface->assign('bookCoverUrl', $coverUrl);
						} elseif (preg_match('`^lc_`', $listEntryInfo['sourceId'])){
							$id = explode("lc_1_", $listEntryInfo['sourceId']);
							$id = $id[1];
							$coverUrl = "/bookcover.php?id={$id}&size=medium&type=library_calendar_event";

							$interface->assign('bookCoverUrl', $coverUrl);
						} elseif (preg_match('`^assabet`', $listEntryInfo['sourceId'])){
							$id = explode("assabet_1_", $listEntryInfo['sourceId']);
							$id = $id[1];
							$coverUrl = "/bookcover.php?id={$id}&size=medium&type=assabet_event";

							$interface->assign('bookCoverUrl', $coverUrl);
						}
					}

					if (!empty($listEntryInfo['title'])) {
						$interface->assign('deletedEntryTitle', $listEntryInfo['title']);
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
	 * @param bool $allowEdit
	 * @param array $allListEntryIds optional list of IDs to re-order the records by (ie User List sorts)
	 * @param int $startRecord The first record being displayed
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getResultListHTML($records, $allListEntryIds, $allowEdit, $startRecord = 0): array {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentListEntry) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentListEntry['sourceId']) {
					$recordDriver->setListNotes($currentListEntry['notes']);
					$recordDriver->setListEntryId($currentListEntry['listEntryId']);
					$recordDriver->setListEntryWeight($currentListEntry['weight']);
					$current = $recordDriver;
					break;
				}
			}
			$interface->assign('recordIndex', $listPosition + 1);
			$interface->assign('resultIndex', $listPosition + $startRecord + 1);

			if (!empty($current)) {
				//Get information from list entry
				$interface->assign('listEntryNotes', $current->getListNotes());
				$interface->assign('listEntryId', $current->getListEntryId());
				$interface->assign('listEntryWeight', $current->getListEntryWeight());
				$interface->assign('listEditAllowed', $allowEdit);

				$interface->assign('recordDriver', $current);
				$html[$listPosition] = $interface->fetch($current->getListEntry($this->id, $allowEdit));
			}
		}
		return $html;
	}

	private function getResultListSummary($records, $allListEntryIds): array {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var IndexRecordDriver $recordDriver
			 */
			foreach ($records as $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$recordDriver->setListNotes($currentId['notes']);
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

	private function getResultListCitations($records, $allListEntryIds, $format): array {
		global $interface;
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
				$results[$listPosition] = $interface->fetch($current->getCitation($format));
			}
		}
		return $results;
	}

	private function getResultListRecordDrivers($records, $allListEntryIds): array {
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
			foreach ($records as $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$recordDriver->setListNotes($currentId['notes']);
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
	public function getBrowseRecords($start, $numItems): array {
		$listEntryInfo = $this->getListEntries($this->defaultSort, false, 0, $start, $numItems);
		$filteredListEntries = $listEntryInfo['listEntries'];

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
				$browseRecords = array_merge($browseRecords, $this->getBrowseRecordHTML($records, $listEntryInfo['listEntries'], $start));
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
	public function getBrowseRecordsRaw($start, $numItems, $forLiDA = false, $appVersion = 0): array {
		global $configArray;

		//Get all entries for the list
		$listEntryInfo = $this->getListEntries($this->defaultSort, $forLiDA, $appVersion, $start, $numItems);

		//Trim to the number of records we want to return
		$filteredListEntries = $listEntryInfo['listEntries'];

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)) {
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		$lmBypass = false;
		$lmAddToList = false;
		$commmunicoBypass = false;
		$communicoAddToList = false;
		$springshareBypass = false;
		$springshareAddToList = false;
		$assabetBypass = false;
		$assabetAddToList = false;

		$libraryEventSettings = [];

		//Load catalog items
		$browseRecords = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds) {
			if($sourceType == 'Events') {
				require_once ROOT_DIR . '/sys/SolrConnector/EventsSolrConnector.php';
				$searchLibrary = Library::getSearchLibrary(null);
				require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
				$libraryEventsSetting = new LibraryEventsSetting();
				$libraryEventsSetting->libraryId = $searchLibrary->libraryId;
				$libraryEventSettings = $libraryEventsSetting->fetchAll();

				foreach($libraryEventSettings as $setting) {
					$source = $setting->settingSource;
					$id = $setting->settingId;
					if($source == 'library_market') {
						require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
						$eventSetting = new LMLibraryCalendarSetting();
						$eventSetting->id = $id;
						if($eventSetting->find(true)) {
							$lmBypass = $eventSetting->bypassAspenEventPages;
							$lmAddToList = $eventSetting->eventsInLists;
						}
					} else if ($source == 'communico') {
						require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
						$eventSetting = new CommunicoSetting();
						$eventSetting->id = $id;
						if($eventSetting->find(true)) {
							$commmunicoBypass = $eventSetting->bypassAspenEventPages;
							$commmunicoAddToList = $eventSetting->eventsInLists;
						}
					} else if ($source == 'springshare') {
						require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
						$eventSetting = new SpringshareLibCalSetting();
						$eventSetting->id = $id;
						if($eventSetting->find(true)) {
							$springshareBypass = $eventSetting->bypassAspenEventPages;
							$springshareAddToList = $eventSetting->eventsInLists;
						}
					} else if ($source == 'assabet') {
						require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
						$eventSetting = new AssabetSetting();
						$eventSetting->id = $id;
						if($eventSetting->find(true)) {
							$assabetBypass = $eventSetting->bypassAspenEventPages;
							$assabetAddToList = $eventSetting->eventsInLists;
						}
					}else {
						// invalid event source
					}
				}

			} else {
				require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			}
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			} else {
				$records = $searchObject->getRecords($sourceIds);
				foreach ($records as $record) {
					//Figure out the key (list position) for the record
					$key = null;
					foreach ($filteredListEntries as $listPosition => $currentId) {
						if ($currentId['source'] == $sourceType && $currentId['sourceId'] == $record->getId()) {
							$key = $listPosition;
							break;
						}
					}
					if ($key === null) {
						//We didn't find the key
						continue;
					}
					if ($record instanceof ListsRecordDriver) {
						$browseRecords[$key] = $record->getFields();
					} elseif ($sourceType == 'Events') {
						if(str_starts_with($record->getId(), 'lc')) {
							$eventSource = 'library_calendar';
							$bypass = $lmBypass;
							$addToList = $lmAddToList;
						} else if (str_starts_with($record->getId(), 'communico')) {
							$eventSource = 'communico';
							$bypass = $commmunicoBypass;
							$addToList = $communicoAddToList;
						} else if (str_starts_with($record->getId(), 'libcal')) {
							$eventSource = 'springshare_libcal';
							$bypass = $springshareBypass;
							$addToList = $springshareAddToList;
						} else if (str_starts_with($record->getId(), 'assabet')) {
							$eventSource = 'assabet';
							$bypass = $assabetBypass;
							$addToList = $assabetAddToList;
						} else {
							$eventSource = 'unknown';
							$bypass = false;
							$addToList = false;
						}
						$user = false;
						if($forLiDA) {
							if (isset($_POST['username']) && isset($_POST['password'])) {
								$username = $_POST['username'];
								$password = $_POST['password'];
								$user = UserAccount::validateAccount($username, $password);
								if ($user !== false && $user->source == 'admin') {
									$user = false;
								}
							}
						} else {
							$user = UserAccount::getActiveUserObj();
						}

						$locationInfo = null;
						if($record->getBranch()) {
							$branch = $record->getBranch();
							require_once ROOT_DIR . '/services/API/EventAPI.php';
							$eventApi = new EventAPI();
							$locationInfo = $eventApi->getDiscoveryBranchDetails($branch[0]);
						}

						$browseRecords[$key]['key'] = $record->getId();
						$browseRecords[$key]['source'] = $eventSource;
						$browseRecords[$key]['title'] = $record->getTitle();
						$browseRecords[$key]['image'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $record->getId() . '&size=medium&type=' . $eventSource . '_event';
						$browseRecords[$key]['registration_required'] = $record->isRegistrationRequired();
						$browseRecords[$key]['location'] = $locationInfo;
						$browseRecords[$key]['start_date'] = $record->getStartDate();
						$browseRecords[$key]['end_date'] = $record->getEndDate();
						$browseRecords[$key]['url'] = $record->getExternalUrl();
						$browseRecords[$key]['bypass'] = $bypass;
						$browseRecords[$key]['canAddToList'] = false;
						$browseRecords[$key]['userIsRegistered'] = false;
						$browseRecords[$key]['inUserEvents'] = false;
						$browseRecords[$key]['type'] = 'event';
						if ($user && !($user instanceof AspenError)) {
							$browseRecords[$key]['canAddToList'] = $user->isAllowedToAddEventsToList($record->getSource());
							$browseRecords[$key]['userIsRegistered'] = $user->isRegistered($record->getId());
							$browseRecords[$key]['inUserEvents'] = $user->inUserEvents($record->getId());
						}
					} else {
						if ($record->isValid()) {
							$browseRecords[$key]['id'] = $record->getPermanentId();
							$browseRecords[$key]['title_display'] = $record->getShortTitle();
							$browseRecords[$key]['author_display'] = $record->getPrimaryAuthor();
							$browseRecords[$key]['format'] = $record->getFormatsArray();
							$browseRecords[$key]['language'] = $record->getLanguage();
							$browseRecords[$key]['type'] = 'grouped_work';
							// $browseRecords[$key]['placesOfPublication'] = $groupedWorkDriver->getPlacesOfPublication();
						} else {
							//not a valid record, skip it
							continue;
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
	private function getBrowseRecordHTML($records, $allListEntryIds, $start): array {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $recordDriver) {
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
				$html[$listPosition] = $interface->fetch($current->getBrowseResult());
			}
		}
		return $html;
	}

	/**
	 * @return array
	 */
	public static function getSortOptions(): array {
		return UserList::$__userListSortOptions;
	}

	public function getSpotlightTitles(CollectionSpotlight $collectionSpotlight): array {
		$allEntries = $this->getListTitles();
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

	public static function getUserListsForSaveForm($source, $sourceId): array {
		global $interface;
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		//Get a list of all lists for the user
		$containingLists = [];
		$nonContainingLists = [];

		$user = UserAccount::getActiveUserObj();

		$userLists = new UserList();
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

	public static function getUserListsForRecord($source, $sourceId): array {
		global $searchSource;
		$searchLibrary = Library::getSearchLibrary($searchSource);
		$searchLocation = Location::getSearchLocation($searchSource);
		$userLists = [];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->source = $source;
		$userListEntry->sourceId = $sourceId;
		$userListIds = $userListEntry->fetchAll('listId', 'listId');

		//Check to see if the user has access to the list
		$userList = new UserList();
		$userList->whereAddIn('id', $userListIds, false);
		$userList->find();
		while ($userList->fetch()) {
			$okToShow = false;
			$key = '';
			if (!$userList->deleted) {
				if (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $userList->user_id) {
					$okToShow = true;
					$key = 0 . strtolower($userList->title);
				} elseif ($userList->public == 1 && $userList->searchable == 1) {
					//Check restrictions for this list to be sure we should show it in this interface
					$ownedByLibrary = false;
					if ($searchLocation != null) {
						if ($searchLocation->publicListsToInclude == 0) {
							//Include no lists
							/** @noinspection PhpConditionAlreadyCheckedInspection */
							$okToShow = false;
						}elseif ($searchLocation->publicListsToInclude == 2) {
							//Lists from this location (no NYT)
							$owningLocationId = $userList->getOwningLocationId();
							$okToShow = $owningLocationId == $searchLocation->locationId;
							$ownedByLibrary = $owningLocationId == $searchLocation->locationId;
						}elseif ($searchLocation->publicListsToInclude == 5) {
							//Lists from list publishers at this location Only (includes NYT)
							$owningLocationId = $userList->getOwningLocationId();
							$okToShow = $owningLocationId == null || $owningLocationId == -1 || $owningLocationId == $searchLocation->locationId;
							$ownedByLibrary = $owningLocationId == $searchLocation->locationId;
						}elseif ($searchLocation->publicListsToInclude == 1) {
							//Lists from this library (no NYT)
							$owningLibraryId = $userList->getOwningLibraryId();
							$okToShow = $owningLibraryId == $searchLibrary->libraryId;
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
						}elseif ($searchLocation->publicListsToInclude == 4) {
							//Lists from library list publishers Only (includes NYT)
							$owningLibraryId = $userList->getOwningLibraryId();
							$okToShow = $owningLibraryId == null || $owningLibraryId == -1 || $owningLibraryId == $searchLibrary->libraryId;
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
						}elseif ($searchLocation->publicListsToInclude == 3 || $searchLocation->publicListsToInclude == 6) {
							//All lists
							$owningLibraryId = $userList->getOwningLibraryId();
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
							$okToShow = true;
						}
					}else{
						if ($searchLibrary->publicListsToInclude == 0) {
							//Include no lists
							/** @noinspection PhpConditionAlreadyCheckedInspection */
							$okToShow = false;
						}else if ($searchLibrary->publicListsToInclude == 1) {
							//Lists from this library (no NYT)
							$owningLibraryId = $userList->getOwningLibraryId();
							$okToShow = $owningLibraryId == $searchLibrary->libraryId;
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
						}else if ($searchLibrary->publicListsToInclude == 3) {
							//Lists from this library (includes NYT)
							$owningLibraryId = $userList->getOwningLibraryId();
							$okToShow = $owningLibraryId == null || $owningLibraryId == -1 || $owningLibraryId == $searchLibrary->libraryId;
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
						}else if ($searchLibrary->publicListsToInclude == 2 || $searchLibrary->publicListsToInclude == 4) {
							//All Lists (these 2 options are equivalent since we restrict to only searchable lists)
							$owningLibraryId = $userList->getOwningLibraryId();
							$ownedByLibrary = $owningLibraryId == $searchLibrary->libraryId;
							$okToShow = true;
						}
					}
					if ($ownedByLibrary) {
						$key = 1 . strtolower($userList->title);
					}else{
						$key = 2 . strtolower($userList->title);
					}

				}
			}
			if ($okToShow) {
				$userLists[$key] = [
					'link' => '/MyAccount/MyList/' . $userList->id,
					'title' => $userList->title,
				];
			}
		}

		ksort($userLists);
		return $userLists;
	}

	static $libraryIdsForUsers = [];

	private function getOwningLibraryId() {
		if (!isset(self::$libraryIdsForUsers[$this->user_id])){
			$owningUser = new User();
			$owningUser->selectAdd();
			$owningUser->selectAdd('homeLocationId');
			$owningUser->selectAdd('libraryId');
			$location = new Location();
			$location->selectAdd();
			$location->selectAdd('libraryId');
			$location->selectAdd('locationId');
			$owningUser->joinAdd($location, 'LEFT', 'userLocation', 'homeLocationId', 'locationId');
			$owningUser->id = $this->user_id;
			if ($owningUser->find(true)) {
				/** @noinspection PhpUndefinedFieldInspection */
				self::$libraryIdsForUsers[$this->user_id] = $owningUser->libraryId;
			}else{
				self::$libraryIdsForUsers[$this->user_id] = -1;
			}
		}
		return self::$libraryIdsForUsers[$this->user_id];
	}

	private function getOwningLocationId() : int {
		$owningUser = new User();
		$owningUser->selectAdd();
		$owningUser->selectAdd('homeLocationId');
		$owningUser->id = $this->user_id;
		if ($owningUser->find(true)) {
			return $owningUser->homeLocationId;
		}else{
			return -1;
		}
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['user_id']);
		return $return;
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		$user = new User();
		$user->id = $this->user_id;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || in_array($user->homeLocationId, $selectedFilters['locations'])) {
				$okToExport = true;
			}
		}
		return $okToExport;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->user_id;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}

		$userListEntries = [];
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->listId = $this->id;
		$userListEntry->find();
		while ($userListEntry->fetch()) {
			$userListEntryArray = $userListEntry->toArray(false, true);
			$userListEntryArray['links'] = $userListEntry->getLinksForJSON();
			$userListEntries[] = $userListEntryArray;
		}

		$links['userListEntries'] = $userListEntries;
		return $links;
	}

	public function loadObjectPropertiesFromJSON($jsonData, $mappings) : void {
		parent::loadObjectPropertiesFromJSON($jsonData, $mappings);
		//Need to load ID for lists since we link to a list based on the id
		$this->id = (int)$jsonData['id'];
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting') : void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->user_id = $user->id;
			}
		}
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (array_key_exists('userListEntries', $jsonData)) {
			//Remove any list entries that we already have for this list
			$tmpListEntry = new UserListEntry();
			$tmpListEntry->listId = $this->id;
			$tmpListEntry->delete(true);
			foreach ($jsonData['userListEntries'] as $listEntry) {
				require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $this->id;
				unset($listEntry['listId']);
				$userListEntry->loadFromJSON($listEntry, $mappings, $overrideExisting);
			}
			$result = true;
		}
		return $result;
	}

	public function isDismissed($appUser = null): bool {
		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
		if (UserAccount::isLoggedIn() || $appUser != null) {
			if (is_null($appUser)) {
				$user = UserAccount::getActiveUserObj();
			} else {
				$user = $appUser;
			}
			$savedSearchDismissal = new BrowseCategoryDismissal();
			$savedSearchDismissal->browseCategoryId = 'system_user_lists_' . $this->id;
			$savedSearchDismissal->userId = $user->id;
			if ($savedSearchDismissal->find(true)) {
				return true;
			}
		}
		return false;
	}

	public function isValidForDisplay() {
		if ($this->isDismissed()) {
			return false;
		}
		return true;
	}

	public function fixWeights() {
		$changeMade = false;

		$listEntries = new UserListEntry();
		$listEntries->listId = $this->id;
		$listEntries->orderBy('weight');
		/** @var UserListEntry[] $allListEntries */
		$allListEntries = $listEntries->fetchAll();
		$curIndex = 1;
		foreach ($allListEntries as $listEntry) {
			if ($listEntry->weight != $curIndex) {
				$listEntry->weight = $curIndex;
				$listEntry->update();
				$changeMade = true;
			}
			$curIndex++;
		}

		if ($changeMade) {
			$this->update();
		}
	}

	/**
	 * Turn our results into a csv document
	 * @param null|array $result
	 */
	public function buildCSV() : void {
		try {
			$titleDetails = $this->getListRecords(0, 1000, false, 'recordDrivers'); // get all titles for email list, not just a page's worth

			//Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="UserList.csv"');
			$fp = fopen('php://output', 'w');

			$fields = array('Link', 'Title', 'Author', 'Publisher', 'Publish Date', 'Format', 'Location & Call Number', 'ISBN', 'UPC');
			fputcsv($fp, $fields);

			foreach ($titleDetails as $curDoc) {
				if ($curDoc instanceof GroupedWorkDriver) {
					if ($curDoc->isValid()) {
						// Hyperlink to title
						$link = $curDoc->getLinkUrl(true) ?? '';

						// Title
						$title = $curDoc->getTitle() ?? '';

						// Author
						$author = $curDoc->getPrimaryAuthor() ?? '';

						// Publisher list
						$publishers = $curDoc->getPublishers();
						if (is_array($publishers)){
							$publishers = implode(', ', $publishers);
						}

						// $placesOfPublication = $curDoc->getPlacesOfPublication();
						// if (is_array($placesOfPublication)){
						// 	$placesOfPublication = implode(', ', $placesOfPublication);
						// }
						// Publication dates: min - max
						if (!is_array($curDoc->getPublicationDates())) {
							$publishDates = [$curDoc->getPublicationDates()];
						} else {
							$publishDates = $curDoc->getPublicationDates();
						}
						$publishDate = '';
						if (count($publishDates) == 1) {
							$publishDate = $publishDates[0];
						} elseif (count($publishDates) > 1) {
							$publishDate = min($publishDates) . ' - ' . max($publishDates);
						}

						// Formats
						if (!is_array($curDoc->getFormats())) {
							$formats = [$curDoc->getFormats()];
						} else {
							$formats = $curDoc->getFormats();
						}
						$uniqueFormats = array_unique($formats);
						$uniqueFormats = implode(', ', $formats);

						// Format / Location / Call number, max 3 records
						//Get the Grouped Work Driver so we can get information about the formats and locations within the record
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$output = [];
						foreach ($curDoc->getRelatedManifestations() as $relatedManifestation) {
							//Manifestation gives us Format & Format Category
							if (!$relatedManifestation->isHideByDefault()) {
								$format = $relatedManifestation->format;
								//Variation gives us the sort
								foreach ($relatedManifestation->getVariations() as $variation) {
									if (!$variation->isHideByDefault()) {
										//Record will give us the call number, and location
										//Only do up to 3 records per format?
										foreach ($variation->getRecords() as $record) {
											if ($record->isLocallyOwned() || $record->isLibraryOwned()) {
												$copySummary = $record->getItemSummary();
												foreach ($copySummary as $item) {
													$output[] = $format . "::" . $item['description'];
												}
												$output = array_unique($output);
												$output = array_slice($output, 0, 3);
												if (count($output) == 0) {
													$output[] = "No copies currently owned by this library";
												}
											}
										}
									}
								}
							}
						}

						$isbn = $curDoc->getPrimaryIsbn() ?? '';
						$upc = $curDoc->getCleanUPC() ?? '';
					}else{
						$link = "No Link Available";
						$title = $curDoc['title_display'];
						$author = '';
						$publishers = '';
						$publishDate = '';
						$uniqueFormats = '';
						$output = ["No copies currently owned by this library"];
						$isbn = '';
						$upc = '';
					}
				} elseif ($curDoc instanceof ListsRecordDriver) {
					// Hyperlink to title
					$link = $curDoc->getLinkUrl();
					// Title
					$title = $curDoc->getTitle() ?? '';
					// Author
					$fields = $curDoc->getFields();
					$author = $fields['author_display'] ?? '';
					//Set other values to empty string
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = '';
					$upc = '';
					$output = [''];
				} elseif ($curDoc instanceof PersonRecord) {
					// Hyperlink to Person Record
					$link = $curDoc->getLinkUrl() ?? '';
					// Person Name
					$title = $curDoc->getName() ?? '';
					//Set other values to empty string
					$author = '';
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = '';
					$upc = '';
					$output = [''];
				} elseif ($curDoc instanceof OpenArchivesRecordDriver) {
					// Hyperlink to Open Archive target
					$link = $curDoc->getLinkUrl();
					// Title
					$title = $curDoc->getTitle() ?? '';
					//Set other values to empty string
					$author = '';
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = '';
					$upc = '';
					$output = [''];
				} elseif ($curDoc instanceof EbscohostRecordDriver) {
					// Hyperlink to EBSCOHost record
					$link = $curDoc->getLinkUrl() ?? '';
					// Title
					$title = $curDoc->getTitle() ?? '';
					// Primary Author
					$author = $curDoc->getPrimaryAuthor() ?? '';
					//Set other values to empty string
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = $curDoc->getPrimaryISBN() ?? '';
					$upc = '';
					$output = [''];

				} elseif ($curDoc instanceof EbscoRecordDriver) {
					// Hyperlink to EBSCO record
					$link = $curDoc->getLinkUrl() ?? '';
					// Title
					$title = $curDoc->getTitle() ?? '';
					// Primary Author
					$author = $curDoc->getPrimaryAuthor() ?? '';
					//Set other values to empty string
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = $curDoc->getPrimaryISBN() ?? '';
					$upc = '';
					$output = [''];

				} elseif ($curDoc instanceof SummonRecordDriver) {
					// Hyperlink to Summon record
					$link = $curDoc->getLinkUrl() ?? '';
					// Title
					$title = $curDoc->getTitle() ?? '';
					// Primary Author
					$author = $curDoc->getPrimaryAuthor() ?? '';
					//Set other values to empty string
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = $curDoc->getPrimaryISBN() ?? '';
					$upc = '';
					$output = [''];

				} elseif ($curDoc instanceof WebsitePageRecordDriver) {
					// Hyperlink
					$link = $curDoc->getLinkUrl() ?? '';
					// Title
					$title = $curDoc->getTitle() ?? '';
					//Set other values to empty string
					$author = '';
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = '';
					$upc = '';
					$output = [''];

				} elseif ($curDoc instanceof WebResourceRecordDriver) {
					// Hyperlink
					$link = $curDoc->getLinkUrl() ?? '';
					// Title
					$title = $curDoc->getTitle() ?? '';
					//Set other values to empty string
					$author = '';
					$publishers = '';
					$publishDate = '';
					$uniqueFormats = '';
					$isbn = '';
					$upc = '';
					$output = [''];
				}

				$output = implode(', ', $output);
				$row = array ($link, $title, $author, $publishers, $publishDate, $uniqueFormats, $output, $isbn, $upc);
				fputcsv($fp, $row);
			}
			exit();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create csv file " . $e, Logger::LOG_ERROR);
		}
	}

	public function buildRIS() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		try {
			$titleDetails = $this->getListRecords(0, 1000, false, 'recordDrivers'); // get all titles for export, not just a page's worth

			$risCitations = array();

			foreach ($titleDetails as $curDoc) {
				//check document type
				if ($curDoc instanceof GroupedWorkDriver && $curDoc->isValid()) {
					$risCitation =$curDoc-> formatGroupedWorkCitation();
				} else {
					continue;
				}
				// Add formated entry to citation array
				if (!empty($risCitation)) {
					$risCitations[] = $risCitation;
				}
			}

			// Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: text/plain; charset=utf-8');
			header('Content-Disposition: attachment; filename="UserList.ris"');


			echo implode("\n", $risCitations);
			exit();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create RIS file " . $e, Logger::LOG_ERROR);
		}
	}

	public function supportsSoftDelete(): bool {
		return true;
	}
}
