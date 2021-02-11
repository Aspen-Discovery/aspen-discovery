<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserList extends DataObject
{
	public $__table = 'user_list';
	public $id;
	public $user_id;
	public $title;
	public $description;
	public $created;
	public $public;
	public $searchable;
	public $deleted;
	public $dateUpdated;
	public $defaultSort;

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
		return ['user_id', 'public', 'deleted', 'searchable'];
	}

	// Used by FavoriteHandler as well//
	private static $__userListSortOptions = array(
		// URL_value => SQL code for Order BY clause
		'title' => 'title ASC',
		'dateAdded' => 'dateAdded ASC',
		'recentlyAdded' => 'dateAdded DESC',
		'custom' => 'weight ASC',  // this puts items with no set weight towards the end of the list
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
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

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
		$result = parent::update();
		if ($result) {
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
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

		//Sort the list appropriately
		if (!empty($sort) && $sort != 'title') $listEntry->orderBy(UserList::getSortOptions()[$sort]);

		// These conditions retrieve list items with a valid groupedWorkId or archive ID.
		// (This prevents list strangeness when our searches don't find the ID in the search indexes)

		$listEntries = [];
		$idsBySource = [];
		$listEntry->find();
		while ($listEntry->fetch()){
			if (!array_key_exists($listEntry->source, $idsBySource)){
				$idsBySource[$listEntry->source] = [];
			}
			$idsBySource[$listEntry->source][] = $listEntry->sourceId;
			$tmpListEntry = [
				'source' => $listEntry->source,
				'sourceId' => $listEntry->sourceId,
				'notes' => $listEntry->notes,
				'listEntryId' => $listEntry->id,
				'listEntry' => $this->cleanListEntry(clone($listEntry)),
			];
			if ($sort == 'title') {
				if ($listEntry->getRecordDriver() != null){
					$tmpListEntry['title'] = strtolower($listEntry->getRecordDriver()->getSortableTitle());
				}else{
					if ($tmpListEntry['source'] == 'GroupedWork'){
						require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
						$groupedWork = new GroupedWork();
						$groupedWork->permanent_id = $tmpListEntry['sourceId'];
						if ($groupedWork->find(true)){
							$tmpListEntry['title'] = $groupedWork->full_title;
						}else{
							$tmpListEntry['title'] = 'Unknown title';
						}
					}else {
						$tmpListEntry['title'] = 'Unknown title';
					}
				}
			}
			$listEntries[] = $tmpListEntry;
		}
		$listEntry->__destruct();
		$listEntry = null;

		if ($sort == 'title') {
			usort($listEntries, 'compareListEntryTitles');
		}

		return [
			'listEntries' => $listEntries,
			'idsBySource' => $idsBySource
		];
	}

	/**
	 * @param string $sortName How records should be sorted, if no sort is provided, will use the default for the list
	 * @return UserListEntry[]|null
	 */
	function getListTitles($sortName = null)
	{
		if (isset($this->listTitles[$this->id])){
			return $this->listTitles[$this->id];
		}
		if ($sortName == null){
			$sortName = $this->defaultSort;
		}
		$listEntries = $this->getListEntries($sortName);
		$this->listTitles[$this->id] = [];
		foreach ($listEntries['listEntries'] as $listEntry){
			$this->listTitles[$this->id][] = $listEntry['listEntry'];
		}

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
	 * @param String $listEntryToRemove
	 * @param bool $updateBrowseCategories
	 */
	function removeListEntry($listEntryToRemove, $updateBrowseCategories = true)
	{
		// Remove the Saved List Entry
		if ($listEntryToRemove instanceof UserListEntry){
			$listEntryToRemove->delete(false, $updateBrowseCategories);
		}else{
			require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
			$listEntry = new UserListEntry();
			$listEntry->id = $listEntryToRemove;
			$listEntry->delete(true);
		}

		unset($this->listTitles[$this->id]);

		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
	}

	private $_cleanDescription = null;

	/** @noinspection PhpUnused */
	function getCleanDescription(){
		if ($this->_cleanDescription == null){
			$this->_cleanDescription = strip_tags($this->description, '<p><b><em><strong><i><br>');;
		}
		return $this->_cleanDescription;
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
	 * @param int $start position of first list item to fetch (0 based)
	 * @param int $numItems Number of items to fetch for this result
	 * @param boolean $allowEdit whether or not the list should be editable
	 * @param string $format The format of the records, valid values are html, summary, recordDrivers, citation
	 * @param string $citationFormat How citations should be formatted
	 * @param string $sortName How records should be sorted, if no sort is provided, will use the default for the list
	 * @return array     Array of HTML to display to the user
	 */
	public function getListRecords($start, $numItems, $allowEdit, $format, $citationFormat = null, $sortName = null) {
		//Get all entries for the list
		if ($sortName == null) {
			$sortName = $this->defaultSort;
		}
		$listEntryInfo = $this->getListEntries($sortName);

		//Trim to the number of records we want to return
		if ($numItems > 0){
			$filteredListEntries = array_slice($listEntryInfo['listEntries'], $start, $numItems);
		}else{
			$filteredListEntries = $listEntryInfo['listEntries'];
		}

		$filteredIdsBySource = [];
		foreach ($filteredListEntries as $listItemEntry) {
			if (!array_key_exists($listItemEntry['source'], $filteredIdsBySource)){
				$filteredIdsBySource[$listItemEntry['source']] = [];
			}
			$filteredIdsBySource[$listItemEntry['source']][] = $listItemEntry['sourceId'];
		}

		//Load the actual items from each source
		$listResults = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds){
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false){
				AspenError::raiseError("Unknown List Entry Source $sourceType");
			}else{
				$records = $searchObject->getRecords($sourceIds);
				if ($format == 'html') {
					$listResults = $listResults + $this->getResultListHTML($records, $filteredListEntries, $allowEdit, $start);
				}elseif ($format == 'summary') {
					$listResults = $listResults + $this->getResultListSummary($records, $filteredListEntries);
				}elseif ($format == 'recordDrivers') {
					$listResults = $listResults + $this->getResultListRecordDrivers($records, $filteredListEntries);
				}elseif ($format == 'citations') {
					$listResults = $listResults + $this->getResultListCitations($records, $filteredListEntries, $citationFormat);
				}else{
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
	private function getResultListHTML($records, $allListEntryIds, $allowEdit, $startRecord = 0)
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
					$recordDriver->setListNotes($currentId['notes']);
					$recordDriver->setListEntryId($currentId['listEntryId']);
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
				$interface->assign('listEditAllowed', $allowEdit);

				$interface->assign('recordDriver', $current);
				$html[$listPosition] = $interface->fetch($current->getListEntry($this->id, $allowEdit));
			}
		}
		return $html;
	}

	private function getResultListSummary($records, $allListEntryIds)
	{
		$results = array();
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
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

	private function getResultListCitations($records, $allListEntryIds, $format){
		global $interface;
		$results = array();
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
			if (!empty($current)) {
				$results[$listPosition] = $interface->fetch($current->getCitation($format));
			}
		}
		return $results;
	}

	private function getResultListRecordDrivers($records, $allListEntryIds)
	{
		$results = array();
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
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
	public static function getSortOptions()
	{
		return UserList::$__userListSortOptions;
	}

	public function getSpotlightTitles(CollectionSpotlight $collectionSpotlight)
	{
		$allEntries = $this->getListTitles();

		$results = [];
		/**
		 * @var string $key
		 * @var UserListEntry $entry */
		foreach ($allEntries as $key => $entry){
			$recordDriver = $entry->getRecordDriver();
			if ($recordDriver == null){
				//Don't show this result because it no lonnger exists in teh catalog.
				/*$results[$key] = [
					'title' => 'Unhandled Source ' . $entry->source,
					'author' => '',
					'formattedTextOnlyTitle' => '<div id="scrollerTitle" class="scrollerTitle"><span class="scrollerTextOnlyListTitle">' . 'Unhandled Source ' . $entry->source . '</span></div>',
					'formattedTitle' => '<div id="scrollerTitle" class="scrollerTitle"><span class="scrollerTextOnlyListTitle">' . 'Unhandled Source ' . $entry->source . '</span></div>',
				];*/
			}else{
				if ($recordDriver->isValid()){
					$results[$key] = $recordDriver->getSpotlightResult($collectionSpotlight, $key);
				}
			}

			if (count($results) == $collectionSpotlight->numTitlesToShow){
				break;
			}
		}

		return $results;
	}

	public static function getUserListsForSaveForm($source, $sourceId){
		global $interface;
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

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
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
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
				if (!$userList->deleted) {
					if (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $userList->user_id) {
						$okToShow = true;
						$key = 0 . strtolower($userList->title);
					} else if ($userList->public == 1 && $userList->searchable == 1) {
						$okToShow = true;
						$key = 1 . strtolower($userList->title);
					}
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

function compareListEntryTitles($listEntry1, $listEntry2){
	return strcasecmp($listEntry1['title'], $listEntry2['title']);
}