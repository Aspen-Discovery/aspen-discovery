<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserList extends DataObject
{
	public $__table = 'user_list';												// table name
	public $id;															// int(11)	not_null primary_key auto_increment
	public $user_id;													// int(11)	not_null multiple_key
	public $title;														// string(200)	not_null
	public $description;											// string(500)
	public $created;													// datetime(19)	not_null binary
	public $public;													// int(11)	not_null
	public $deleted;
	public $dateUpdated;
	public $defaultSort; // string(20) null

	public static function getSourceListsForBrowsingAndCarousels()
	{
		$userLists = new UserList();
		$userLists->public = 1;
		$userLists->orderBy('title asc');
		$userLists->find();
		$sourceLists = array();
		$sourceLists[-1] = 'Generate from search term and filters';
		while ($userLists->fetch()) {
			$numItems = $userLists->numValidListItems();
			if ($numItems > 0) {
				$sourceLists[$userLists->id] = "($userLists->id) $userLists->title - $numItems entries";
			}
		}
		return $sourceLists;
	}

	public function getNumericColumnNames()
	{
		return ['public', 'deleted'];
	}

	// Used by FavoriteHandler as well//
	protected $__userListSortOptions = array(
		// URL_value => SQL code for Order BY clause
		'dateAdded' => 'dateAdded ASC',
		'recentlyAdded' => 'dateAdded DESC',
		'custom' => 'weight ASC',  // this puts items with no set weight towards the end of the list
		//								'custom' => 'weight IS NULL, weight ASC',  // this puts items with no set weight towards the end of the list
	);


    static function getObjectStructure(){
		return array(
			'id' => array(
				'property'=>'id',
				'type'=>'label',
				'label'=>'Id',
				'description'=>'The unique id of the user list.',
				'storeDb' => true,
				'storeSolr' => false,
			),
			'title' => array(
				'property' => 'title',
				'type' => 'text',
				'size' => 100,
				'maxLength'=>255,
				'label' => 'Title',
				'description' => 'The title of the item.',
				'required'=> true,
				'storeDb' => true,
				'storeSolr' => true,
			),
			'description' => array(
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'rows'=>3,
				'cols'=>80,
				'description' => 'A brief description of the file for indexing and display if there is not an existing record within the catalog.',
				'required'=> false,
				'storeDb' => true,
				'storeSolr' => true,
			),
		);
	}

	function numValidListItems() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

		// These conditions retrieve list items with a valid groupedWorkID or archive ID.
		// (This prevents list strangeness when our searches don't find the ID in the search indexes)
		$listEntry->whereAdd(
			'(
			(user_list_entry.source = \'GroupedWork\' AND user_list_entry.sourceId NOT LIKE "%:%" AND user_list_entry.sourceId IN (SELECT permanent_id FROM grouped_work) )
			OR
			(user_list_entry.source = \'Islandora\' AND user_list_entry.sourceId LIKE "%:%" AND user_list_entry.sourceId IN (SELECT pid FROM islandora_object_cache) )
			OR
			user_list_entry.source NOT IN (\'GroupedWork\', \'Islandora\')
			)'
		);

		return $listEntry->count();
	}

	function insert($createNow = true){
		if ($createNow) {
			$this->created     = time();
			if (empty($this->dateUpdated)) {
				$this->dateUpdated = time();
			}
		}
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return parent::insert();
	}
	function update(){
		if ($this->created == 0){
			$this->created = time();
		}
		$this->dateUpdated = time();
		$result            = parent::update();
		if ($result) {
			$this->flushUserListBrowseCategory();
			global $memCache;
			$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		}

		return $result;
	}
	function delete($useWhere = false){
		$this->deleted = 1;
		$this->dateUpdated = time();
		$ret = parent::update();

		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $ret;
	}

	/**
	 * @var array An array of resources keyed by the list id since we can iterate over multiple lists while fetching from the DB
	 */
	private $listTitles = array();

	/**
	 * @param null $sort  optional SQL for the query's ORDER BY clause
	 * @return array      of list entries
	 */
	function getListEntries($sort = null){
		//TODO: implement the sort here

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

		if (!empty($sort)) $listEntry->orderBy($sort);

		// These conditions retrieve list items with a valid groupedWorkId or archive ID.
		// (This prevents list strangeness when our searches don't find the ID in the search indexes)
		$listEntry->whereAdd(
			'(
			(user_list_entry.source = \'GroupedWork\' AND user_list_entry.sourceId NOT LIKE "%:%" AND user_list_entry.sourceId IN (SELECT permanent_id FROM grouped_work) )
			OR
			(user_list_entry.source = \'Islandora\' AND user_list_entry.sourceId LIKE "%:%" AND user_list_entry.sourceId IN (SELECT pid FROM islandora_object_cache) )
			OR
			user_list_entry.source NOT IN (\'GroupedWork\', \'Islandora\')
			)'
		);

		$listEntries = [];
		$idsBySource = [];
		$listEntry->find();
		while ($listEntry->fetch()){
			if (!array_key_exists($listEntry->source, $idsBySource)){
				$idsBySource[$listEntry->source] = [];
			}
			$idsBySource[$listEntry->source][] = $listEntry->sourceId;
			$listEntries[] = [
				'source' => $listEntry->source,
				'sourceId' => $listEntry->sourceId
			];
		}
		$listEntry->__destruct();
		$listEntry = null;

		return [
			'listEntries' => $listEntries,
			'idsBySource' => $idsBySource
		];
	}

	/**
	 * @return UserListEntry[]|null
	 */
	function getListTitles()
	{
		if (isset($this->listTitles[$this->id])){
			return $this->listTitles[$this->id];
		}
		$listTitles = array();

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;
		$listEntry->find();

		while ($listEntry->fetch()){
			$cleanedEntry = $this->cleanListEntry(clone($listEntry));
			if ($cleanedEntry != false){
				$listTitles[] = $cleanedEntry;
			}
		}
		$listEntry->__destruct();
		$listEntry = null;

		$this->listTitles[$this->id] = $listTitles;
		return $this->listTitles[$this->id];
	}

	var $catalog;

	/**
	 * @param UserListEntry $listEntry - The resource to be cleaned
	 * @return UserListEntry|bool
	 */
	function cleanListEntry($listEntry){
		//Filter list information for bad words as needed.
		if (!UserAccount::isLoggedIn() || $this->user_id != UserAccount::getActiveUserId()){
			//Load all bad words.
			global $library;
			require_once ROOT_DIR . '/Drivers/marmot_inc/BadWord.php';
			$badWords = new BadWord();

			//Determine if we should censor bad words or hide the comment completely.
			$censorWords = $library->getGroupedWorkDisplaySettings()->hideCommentsWithBadWords == 0;
			if ($censorWords){
				//Filter Title
				$titleText = $badWords->censorBadWords($this->title);
				$this->title = $titleText;

				//Filter description
				$descriptionText = $badWords->censorBadWords($this->description);
				$this->description = $descriptionText;

				//Filter notes
				$notesText = $badWords->censorBadWords($listEntry->notes);
				$listEntry->notes = $notesText;
			}else{
				//Check for bad words in the title or description
				$titleText = $this->title;
				if (isset($listEntry->description)){
					$titleText .= ' ' . $listEntry->description;
				}
				//Filter notes
				$titleText .= ' ' . $listEntry->notes;

				if ($badWords->hasBadWords($titleText)) return false;
			}
		}
		return $listEntry;
	}

	/**
	 * @param String $workToRemove
	 * @param bool $updateBrowseCategories
	 */
	function removeListEntry($workToRemove, $updateBrowseCategories = true)
	{
		// Remove the Saved List Entry
		if ($workToRemove instanceof UserListEntry){
			$workToRemove->delete(false, $updateBrowseCategories);
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
			$listEntry = new UserListEntry();
			$listEntry->source = 'grouped_work';
			$listEntry->sourceId = $workToRemove;
			$listEntry->listId = $this->id;
			$listEntry->delete(true);
		}

		unset($this->listTitles[$this->id]);

		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
	}

	/**
	 * remove all resources within this list
	 * @param bool $updateBrowseCategories
	 */
	function removeAllListEntries($updateBrowseCategories = true){
		$allListEntries = $this->getListTitles();
		foreach ($allListEntries as $listEntry){
			$this->removeListEntry($listEntry, $updateBrowseCategories);
		}
	}

	/**
	 * @param int $start position of first list item to fetch (1 based)
	 * @param int $numItems Number of items to fetch for this result
	 * @param boolean $allowEdit whether or not the list should be editable
	 * @param string $sort How the list should be sorted
	 * @return array     Array of HTML to display to the user
	 */
	public function getListRecordsAsHtml($start, $numItems, $allowEdit) {
		$sort = in_array($this->defaultSort, array_keys($this->__userListSortOptions)) ? $this->__userListSortOptions[$this->defaultSort] : null;

		//Get all entries for the list
		$listEntryInfo = $this->getListEntries($sort);

		//Trim to the number of records we want to return
		$filteredListEntries = array_slice($listEntryInfo['listEntries'], $start - 1, $numItems);

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)){
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load catalog items
		$listResultsHtml = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds){
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false){
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			}else{
				$records = $searchObject->getRecords($sourceIds);
				$listResultsHtml = array_merge($listResultsHtml, $this->getResultListHTML($records, $filteredListEntries, $allowEdit));
			}
		}

		return $listResultsHtml;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param RecordInterface[] $records Records retrieved from the getRecords method of a SolrSearcher
	 * @param bool $allowEdit
	 * @param array $allListEntryIds optional list of IDs to re-order the records by (ie User List sorts)
	 * @param int $page The current page being viewed
	 * @param int $numRecordsPerPage the number of records being shown
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getResultListHTML($records, $allListEntryIds, $allowEdit, $page = 1, $numRecordsPerPage = 20)
	{
		global $interface;
		$html = array();
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
				$interface->assign('resultIndex', $listPosition + 1 + (($page - 1) * $numRecordsPerPage));
				$interface->assign('recordDriver', $recordDriver);
				$html[$listPosition] = $interface->fetch($recordDriver->getListEntry($this->id, $allowEdit));
			}
		}
		return $html;
	}

	/**
	 * @param int $start     position of first list item to fetch
	 * @param int $numItems  Number of items to fetch for this result
	 * @return array     Array of HTML to display to the user
	 */
	public function getBrowseRecords($start, $numItems) {
		//Get all entries for the list
		$listEntryInfo = $this->getListEntries();

		//Trim to the number of records we want to return
		$filteredListEntries = array_slice($listEntryInfo['listEntries'], $start, $numItems);

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)){
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load catalog items
		$browseRecords = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds){
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false){
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			}else{
				$records = $searchObject->getRecords($sourceIds);
				$browseRecords = array_merge($browseRecords, $this->getBrowseRecordHTML($records, $listEntryInfo['listEntries'], $start));
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
	private function getBrowseRecordHTML($records, $allListEntryIds, $start)
	{
		global $interface;
		$html = array();
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
	public function getUserListSortOptions()
	{
		return $this->__userListSortOptions;
	}

	public function getSpotlightTitles(CollectionSpotlight $collectionSpotlight)
	{
		$allEntries = $this->getListTitles();

		$results = [];
		/**
		 * @var string $key
		 * @var UserListEntry $entry */
		foreach ($allEntries as $key => $entry){
			if ($entry->source == 'GroupedWork'){
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
				$groupedWork = new GroupedWorkDriver($entry->sourceId);
				if ($groupedWork->isValid()){
					$results[$key] = $groupedWork->getSpotlightResult($collectionSpotlight, $key);
				}
			}elseif ($entry->source == 'OpenArchives'){
				require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
				$recordDriver = new OpenArchivesRecordDriver($entry->sourceId);
				if ($recordDriver->isValid()){
					$results[$key] = $recordDriver->getSpotlightResult($collectionSpotlight, $key);
				}
			}elseif ($entry->source == 'Lists'){
				require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
				$recordDriver = new ListsRecordDriver($entry->sourceId);
				if ($recordDriver->isValid()){
					$results[$key] = $recordDriver->getSpotlightResult($collectionSpotlight, $key);
				}
			}else{
				$results[$key] = [
					'title' => 'Unhandled Source ' . $entry->source,
					'author' => '',
					'formattedTextOnlyTitle' => '<div id="scrollerTitle" class="scrollerTitle"><span class="scrollerTextOnlyListTitle">' . 'Unhandled Source ' . $entry->source . '</span></div>',
					'formattedTitle' => '<div id="scrollerTitle" class="scrollerTitle"><span class="scrollerTextOnlyListTitle">' . 'Unhandled Source ' . $entry->source . '</span></div>',
				];
			}

			if (count($results) == $collectionSpotlight->numTitlesToShow){
				break;
			}
		}

		return $results;
	}
	private function flushUserListBrowseCategory(){
		// Check if the list is a part of a browse category and clear the cache.
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$userListBrowseCategory = new BrowseCategory();
		$userListBrowseCategory->sourceListId = $this->id;
		if ($userListBrowseCategory->find()) {
			while ($userListBrowseCategory->fetch()) {
				$userListBrowseCategory->deleteCachedBrowseCategoryResults();
			}
		}
		$userListBrowseCategory->__destruct();
		$userListBrowseCategory = null;
	}

	public static function getUserListsForSaveForm($source, $sourceId){
		global $interface;
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';

		//Get a list of all lists for the user
		$containingLists = array();
		$nonContainingLists = array();

		$user = UserAccount::getActiveUserObj();

		$userLists = new UserList();
		$userLists->user_id = UserAccount::getActiveUserId();
		$userLists->whereAdd('deleted = 0');
		$userLists->orderBy('title');
		$userLists->find();
		while ($userLists->fetch()){
			//Check to see if the user has already added the title to the list.
			$userListEntry = new UserListEntry();
			$userListEntry->listId = $userLists->id;
			$userListEntry->source = $source;
			$userListEntry->sourceId = $sourceId;
			if ($userListEntry->find(true)){
				$containingLists[] = array(
					'id' => $userLists->id,
					'title' => $userLists->title
				);
			}else{
				$selected = $user->lastListUsed == $userLists->id;
				$nonContainingLists[] = array(
					'id' => $userLists->id,
					'title' => $userLists->title,
					'selected' => $selected
				);
			}
		}

		$interface->assign('containingLists', $containingLists);
		$interface->assign('nonContainingLists', $nonContainingLists);

		return [
			'containingLists' => $containingLists,
			'nonContainingLists' => $nonContainingLists
		];
	}

	public static function getUserListsForRecord($source, $sourceId)
	{
		$userLists = [];
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->source = $source;
		$userListEntry->sourceId = $sourceId;
		$userListEntry->find();
		while ($userListEntry->fetch()){
			//Check to see if the user has access to the list
			$userList = new UserList();
			$userList->id = $userListEntry->listId;
			if ($userList->find(true)){
				$okToShow = false;
				$key = '';
				if (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $userList->user_id){
					$okToShow = true;
					$key = 0 . strtolower($userList->title);
				}else if ($userList->public){
					$okToShow = true;
					$key = 1 . strtolower($userList->title);
				}
				if ($okToShow) {
					$userLists[$key] = [
						'link' => '/MyAccount/MyList/' . $userList->id,
						'title' => $userList->title
					];
				}
			}
		}
		ksort($userLists);
		return $userLists;
	}
}
