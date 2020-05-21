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
	private $allEntries;
	private $idsBySource;
	private $defaultSort = 'dateAdded'; // initial setting (Use a userlist sorting option initially)
	private $sort;
	private $isUserListSort; // true for sorting options not done by Solr

	protected $userListSortOptions = array();
	protected $solrSortOptions = array('title', 'author'); // user list sorting options handled by Solr engine.

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
		$listEntries = $list->getListEntries($userListSort); // when using a user list sorting, rather than solr sorting, get results in order
		$this->allEntries = $listEntries['listEntries'];
		$this->idsBySource = $listEntries['idsBySource'];

		//Figure out the proper default sort
		if (!$userSpecifiedTheSort && !isset($list->defaultSort)) {
			$this->defaultSort    = 'title';
			$this->sort           = $this->defaultSort;
			$this->isUserListSort = false;
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

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$startRecord = ($page - 1) * $recordsPerPage + 1;
		if ($startRecord < 0){
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > count($this->allEntries)){
			$endRecord = count($this->allEntries);
		}
		$pageInfo = array(
			'resultTotal' => count($this->allEntries),
			'startRecord' => $startRecord,
			'endRecord'   => $endRecord,
			'perPage'     => $recordsPerPage
		);

		$sortOptions = $defaultSortOptions = array(
			'title' => 'Title',
			'dateAdded' => 'Date Added',
			'recentlyAdded' => 'Recently Added',
			'custom' => 'User Defined'
		);

		$interface->assign('sortList', $sortOptions);
		$interface->assign('defaultSortList', $defaultSortOptions);
		$interface->assign('defaultSort', $this->defaultSort);
		$interface->assign('userSort', ($this->getSort() == 'custom')); // switch for when users can sort their list

		$resourceList = $this->list->getListRecords($startRecord, $recordsPerPage, $this->allowEdit, 'html');
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
		if (count($this->allEntries) > 0) {
			$searchObject->setLimit(count($this->allEntries));
			$searchObject->setQueryIDs($this->allEntries);
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
	 * @return UserListEntry[]
	 */
	public function getFavorites()
	{
		return $this->allEntries;
	}
}