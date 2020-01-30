<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * FavoriteHandler Class
 *
 * This class contains shared logic for displaying lists of favorites (based on
 * earlier logic duplicated between the MyAccount/Home and MyAccount/MyList
 * actions).
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class FavoriteHandler
{
    /** @var UserList */
	private $list;
	/** @var User */
	private $user;
	private $listId;
	private $allowEdit;
	private $favorites = array();
	private $ids = array();  //TODO: replace all uses of $this->ids with $this->favorites
	private $catalogIds = array();
	private $archiveIds = array();
	private $defaultSort = 'dateAdded'; // initial setting (Use a userlist sorting option initially)
	private $sort;
	private $isUserListSort; // true for sorting options not done by Solr
	private $isMixedUserList = false; // Flag for user lists that have both catalog & archive items (and eventually other type of items)

	protected $userListSortOptions = array();
	protected $solrSortOptions = array('title', 'author'); // user list sorting options handled by Solr engine.
	protected $islandoraSortOptions = array('fgs_label_s'); // user list sorting options handled by the Islandora Solr engine.

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   UserList   $list        User List Object.
	 * @param   User       $user        User object owning tag/note metadata.
	 * @param   bool       $allowEdit   Should we display edit controls?
	 */
	public function __construct($list, $user, $allowEdit = true)
	{
		$this->list                = $list;
		$this->user                = $user;
		$this->listId              = $list->id;
		$this->allowEdit           = $allowEdit;
		$this->userListSortOptions = $list->getUserListSortOptions(); // Keep the UserList Sort options in the UserList class since it used there as well.

		// Determine Sorting Option //
		if (isset($list->defaultSort)){
			$this->defaultSort = $list->defaultSort; // when list as a sort setting use that
		}
		if (isset($_REQUEST['sort']) && (in_array($_REQUEST['sort'], $this->solrSortOptions) || in_array($_REQUEST['sort'], array_keys($this->userListSortOptions))) ) {
			// if URL variable is a valid sort option, set the list's sort setting
			$this->sort = $_REQUEST['sort'];
			$userSpecifiedTheSort = true;
		} else {
			$this->sort = $this->defaultSort;
			$userSpecifiedTheSort = false;
		}

		$this->isUserListSort = in_array($this->sort, array_keys($this->userListSortOptions));

		// Get the Favorites //
		$userListSort = $this->isUserListSort ? $this->userListSortOptions[$this->sort] : null;
		list($this->favorites, $this->catalogIds, $this->archiveIds) = $list->getListEntries($userListSort); // when using a user list sorting, rather than solr sorting, get results in order
		// we start with a default userlist sorting until we determine whether the userlist is Mixed items or not.

		$this->ids = $this->favorites; // TODO: Remove references to this->ids and use $this->favorites instead
		$hasCatalogItems = !empty($this->catalogIds);
		$hasArchiveItems = !empty($this->archiveIds);

		// Determine if this UserList mixes catalog & archive Items
		if ($hasArchiveItems && $hasCatalogItems) {
			$this->isMixedUserList = true;
		} elseif ($hasArchiveItems && !$hasCatalogItems){
			// Archive Only Lists
			if (!$userSpecifiedTheSort && !isset($list->defaultSort)) {
				// If no actual sorting settings were set, reset default to an Islandora Sort
				$this->defaultSort    = $this->islandoraSortOptions[0];
				$this->sort           = $this->defaultSort;
				$this->isUserListSort = false;
			}
		} elseif ($hasCatalogItems && !$hasArchiveItems) {
			// Catalog Only Lists
			if (!$userSpecifiedTheSort && !isset($list->defaultSort)) {
				// If no actual sorting settings were set, reset default to an Solr Sort
				$this->defaultSort    = $this->solrSortOptions[0];
				$this->sort           = $this->defaultSort;
				$this->isUserListSort = false;
			}
		}

	}

	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 */
	public function buildListForDisplay()
	{
		global $interface;

		$this->list->description = strip_tags($this->list->description, '<p><b><em><strong><i><br>');

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$startRecord = ($page - 1) * $recordsPerPage + 1;
		if ($startRecord < 0){
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > count($this->favorites)){
			$endRecord = count($this->favorites);
		}
		$pageInfo = array(
			'resultTotal' => count($this->favorites),
			'startRecord' => $startRecord,
			'endRecord'   => $endRecord,
			'perPage'     => $recordsPerPage
		);

		$sortOptions = $defaultSortOptions = array();

		/*			Use Cases:
			Only Catalog items, user sort
			Only Catalog items, solr sort
			Only Archive items, user sort
			Only Archive items, islandora sort
			Mixed Items, user sort
		*/

		// Catalog Search
		$catalogResourceList = array();
		if (count($this->catalogIds) > 0) {
			// Initialise from the current search globals
			/** @var SearchObject_GroupedWorkSearcher $catalogSearchObject */
			$catalogSearchObject = SearchObjectFactory::initSearchObject();
			$catalogSearchObject->init();
			$catalogSearchObject->disableScoping();
			$catalogSearchObject->setLimit($recordsPerPage); //MDN 3/30 this was set to 200, but should be based off the page size

			if (!$this->isUserListSort && !$this->isMixedUserList) { // is a solr sort
				$catalogSearchObject->setSort($this->sort); // set solr sort. (have to set before retrieving solr sort options below)
			}
			if (!$this->isMixedUserList) {
				$SolrSortList = $catalogSearchObject->getSortList(); // get all the search sort options (retrieve after setting solr sort option)
				//TODO: There is no longer an author sort option
				foreach ($this->solrSortOptions as $option) { // extract just the ones we want
					if (isset ($SolrSortList[$option])) {
						$sortOptions[$option]        = $SolrSortList[$option];
						$defaultSortOptions[$option] = $SolrSortList[$option]['desc'];
					}
				}
			}
			foreach ($this->userListSortOptions as $option => $value_ignored) { // Non-Solr options
				$sortOptions[$option]        = array(
					'sortUrl'  => $catalogSearchObject->renderLinkWithSort($option),
					'desc'     => "sort_{$option}_userlist", // description in translation dictionary
					'selected' => ($option == $this->sort)
				);
				$defaultSortOptions[$option] = "sort_{$option}_userlist";
			}

			// Catalog Only Searches //
			if (!$this->isMixedUserList) {
				// User Sorted Catalog Only Search
				if ($this->isUserListSort) {
					$this->catalogIds = array_slice($this->catalogIds, $startRecord - 1, $recordsPerPage);
					$catalogSearchObject->setPage(1); // set to the first page for the search only

					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
					$catalogResult = $catalogSearchObject->processSearch();
					$catalogSearchObject->setPage($page); // Set back to the actual page of the list now that search was processed
					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				} // Solr Sorted Catalog Only Search //
				else {
					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
					$catalogSearchObject->setPage($page); // restore the actual sort page //TODO: Page needs set before processSearch() call?
					$catalogResult       = $catalogSearchObject->processSearch();
					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
				}
			}

			// Mixed Items Searches (All User Sorted) //
			else {
				// Removed all catalog items from previous page searches
				$totalItemsFromPreviousPages = $recordsPerPage * ($page - 1);
				for ($i = 0; $i < $totalItemsFromPreviousPages; $i++ ) {
					$IdToTest = $this->favorites[$i];
					$key      = array_search($IdToTest, $this->catalogIds);
					if ($key !== false) {
						unset($this->catalogIds[$key]);
					}
				}
				$this->catalogIds = array_slice($this->catalogIds, 0, $recordsPerPage);
				if (!empty($this->catalogIds)) {
					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
					$catalogSearchObject->setPage(1); // set to the first page for the search only
					$catalogResult = $catalogSearchObject->processSearch();
					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites, $this->isMixedUserList);
				}
			}
		}

		// Archive Search
		$archiveResourceList = array();
		if (count($this->archiveIds) > 0) {
			// Initialise from the current search globals
			/** @var SearchObject_IslandoraSearcher $archiveSearchObject */
			$archiveSearchObject = SearchObjectFactory::initSearchObject('Islandora');
			$archiveSearchObject->init();
			$archiveSearchObject->setPrimarySearch(true);
			$archiveSearchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$archiveSearchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
			$archiveSearchObject->setLimit($recordsPerPage); //MDN 3/30 this was set to 200, but should be based off the page size

			if (!$this->isUserListSort && !$this->isMixedUserList) { // is a solr sort
				$archiveSearchObject->setSort($this->sort); // set solr sort. (have to set before retrieving solr sort options below)
			}
			if (!$this->isMixedUserList) {
				$IslandoraSortList = $archiveSearchObject->getSortList(); // get all the search sort options (retrieve after setting solr sort option)
				foreach ($this->islandoraSortOptions as $option) { // extract just the ones we want
					if (isset ($IslandoraSortList[$option])) {
						$sortOptions[$option]        = $IslandoraSortList[$option];
						$defaultSortOptions[$option] = $IslandoraSortList[$option]['desc'];
					}
				}
			}
			foreach ($this->userListSortOptions as $option => $value_ignored) { // Non-Solr options
				if (!isset($sortOptions[$option])) { // Skip if already done by the catalog searches above
					$sortOptions[$option]        = array(
						'sortUrl'  => $archiveSearchObject->renderLinkWithSort($option),
						'desc'     => "sort_{$option}_userlist", // description in translation dictionary
						'selected' => ($option == $this->sort)
					);
					$defaultSortOptions[$option] = "sort_{$option}_userlist";
				}
			}


			// Archive Only Searches //
			if (!$this->isMixedUserList) {
				// User Sorted Archive Only Searches
				if ($this->isUserListSort) {
					$this->archiveIds = array_slice($this->archiveIds, $startRecord - 1, $recordsPerPage);
					$archiveSearchObject->setPage(1); // set to the first page for the search only

					$archiveSearchObject->setQueryIDs($this->archiveIds); // do solr search by Ids
					$archiveResult = $archiveSearchObject->processSearch();
					$archiveSearchObject->setPage($page); // Set back to the actual page of the list now that search was processed
					$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				}

				// Islandora Sorted Archive Only Searches
				else {
					$archiveSearchObject->setQueryIDs($this->archiveIds); // do Islandora search by Ids
					$archiveSearchObject->setPage($page); // set to the first page for the search only
					$archiveResult       = $archiveSearchObject->processSearch();
					$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit/*, $this->favorites*/);
				}
			}

		 // Mixed Items Searches (All User Sorted) //
			else {
				// Remove all archive items from previous page searches
				$totalItemsFromPreviousPages = $recordsPerPage * ($page - 1);
				for ($i = 0; $i < $totalItemsFromPreviousPages; $i++) {
					$IdToTest = $this->favorites[$i];
					$key      = array_search($IdToTest, $this->archiveIds);
					if ($key !== false) {
						unset($this->archiveIds[$key]);
					}
				}
				$this->archiveIds = array_slice($this->archiveIds, 0, $recordsPerPage);
				if (!empty($this->archiveIds)) {
					$archiveSearchObject->setPage(1); // set to the first page for the search only

					$archiveSearchObject->setQueryIDs($this->archiveIds); // do solr search by Ids
					$archiveResult = $archiveSearchObject->processSearch();
					$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites, $this->isMixedUserList);
				}
			}

		}

		$interface->assign('sortList', $sortOptions);
		$interface->assign('defaultSortList', $defaultSortOptions);
		$interface->assign('defaultSort', $this->defaultSort);
		$interface->assign('userSort', ($this->getSort() == 'custom')); // switch for when users can sort their list


		$resourceList = array();
		if ($this->isMixedUserList) {
			$resourceList = $catalogResourceList + $archiveResourceList;
			// Depends on numbered indexing reflect each item's position in the list
			//$resourceListAlt = array_replace($catalogResourceList, $archiveResourceList); // Equivalent of above
			ksort($resourceList, SORT_NUMERIC); // Requires re-ordering to display in the correct order
			$resourceList = array_slice($resourceList, 0, $recordsPerPage); // reduce to the correct page size
		} else {
			if (count($this->catalogIds) > 0) {
				$resourceList = $catalogResourceList;
			}
			if (count($this->archiveIds) > 0) {
				$resourceList = $archiveResourceList;
			}
		}

		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd',   $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

		$link = $_SERVER['REQUEST_URI'];
		if (preg_match('/[&?]page=/', $link)){
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		}else if (strpos($link, "?") > 0){
			$link .= "&page=%d";
		}else{
			$link .= "?page=%d";
		}
		$options = array('totalItems' => $pageInfo['resultTotal'],
		                 'perPage' => $pageInfo['perPage'],
		                 'fileName' => $link,
		                 'append'    => false);
		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

	}

	function getTitles($numListEntries){
		// Currently only used by AJAX call for emailing lists

		$catalogRecordSet = $archiveRecordSet = array();
		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->catalogIds) > 0) {
			// Initialise from the current search globals
			/** @var SearchObject_GroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			// these are added for emailing list  plb 10-8-2014
			$searchObject->disableScoping(); // get title data regardless of scope
			$searchObject->setLimit($numListEntries); // only get results for each item

			$searchObject->setQueryIDs($this->catalogIds);
			$searchObject->processSearch();
			$catalogRecordSet = $searchObject->getResultRecordSet();
			//TODO: user list sorting here
		}
		if (count($this->archiveIds) > 0) {
			// Initialise from the current search globals
			/** @var SearchObject_IslandoraSearcher $archiveSearchObject */
			$archiveSearchObject = SearchObjectFactory::initSearchObject('Islandora');
			$archiveSearchObject->init();
			$archiveSearchObject->setPrimarySearch(true);
			$archiveSearchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$archiveSearchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
			$archiveSearchObject->setQueryIDs($this->archiveIds);
			$archiveSearchObject->processSearch();
			$archiveRecordSet = $archiveSearchObject->getResultRecordSet();


		}
		return array_merge($catalogRecordSet, $archiveRecordSet);
	}

	function getCitations($citationFormat){
		// Initialise from the current search globals
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->ids) > 0) {
			$searchObject->setLimit(count($this->ids));
			$searchObject->setQueryIDs($this->ids);
			$searchObject->processSearch();
			return $searchObject->getCitations($citationFormat);
		}else{
			return array();
		}
	}

	/**
	 * @return string
	 */
	public function getSort()
	{
		return $this->sort;
	}

	/**
	 * @return array
	 */
	public function getCatalogIds()
	{
		return $this->catalogIds;
	}

	/**
	 * @return array
	 */
	public function getArchiveIds()
	{
		return $this->archiveIds;
	}

	/**
	 * @return boolean
	 */
	public function isMixedUserList()
	{
		return $this->isMixedUserList;
	}

	/**
	 * @return UserListEntry[]
	 */
	public function getFavorites()
	{
		return $this->favorites;
	}
}