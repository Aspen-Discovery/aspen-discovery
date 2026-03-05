<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';

class ListsRecordDriver extends IndexRecordDriver {
	private null|UserList|false $listObject = null;
	private bool $valid = true;

	public function __construct($record) {
		// Call the parent's constructor...
		if (is_string($record)) {
			/** @var SearchObject_ListsSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Lists');
			disableErrorHandler();
			try {
				$fields = $searchObject->getRecord($record);
				if ($fields == null) {
					$this->valid = false;
				} else {
					parent::__construct($fields);
				}
			} catch (Exception) {
				$this->valid = false;
			}
			enableErrorHandler();
		} else {
			parent::__construct($record);
		}
	}

	public function isValid() : bool {
		return $this->valid;
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false): string {
		global $configArray;
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$id = $this->getId();
		$listObject = $this->getListObject();
		$dateUpdated = $listObject instanceof UserList ? $listObject->dateUpdated : '';
		return $bookCoverUrl . "/bookcover.php?type=list&id=$id&size=$size&dateUpdated=$dateUpdated";
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @param string $view
	 * @param bool $showListsAppearingOn
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list', bool $showListsAppearingOn = true) : string {
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));
		$interface->assign('summShortId', $id);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		if (isset($this->fields['description'])) {
			$interface->assign('summDescription', $this->getDescription());
		} else {
			$interface->assign('summDescription', '');
		}
		if (isset($this->fields['num_titles'])) {
			$interface->assign('summNumTitles', $this->fields['num_titles']);
		} else {
			$interface->assign('summNumTitles', 0);
		}
		$listObject = $this->getListObject();
		$interface->assign('summDateUpdated', !$listObject ? '' : $listObject->dateUpdated);
		$interface->assign('summUrl', $this->getAbsoluteUrl());

		$interface->assign('displayListAuthor', !$listObject ? '' : $listObject->displayListAuthor);

		if ($showListsAppearingOn) {
			//Check to see if there are lists the record is on
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('Lists', $this->getId());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		return 'RecordDrivers/List/result.tpl';
	}

	public function getMoreDetailsOptions() : array {
		return [];
	}

	// initially taken From GroupedWorkDriver.php getBrowseResult();
	public function getBrowseResult() : string {
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = '/MyAccount/MyList/' . $id;

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get cover image size
		global $interface;
		$appliedTheme = $interface->getAppliedTheme();

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl());

		$accessibleBrowseCategories = 0;

		if ($appliedTheme != null) {
			if($appliedTheme->browseCategoryImageSize == 1) {
				$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
			} else {
				$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
			}
			$accessibleBrowseCategories = $appliedTheme->accessibleBrowseCategories;
		} else {
			$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		}

		$interface->assign('accessibleBrowseCategories', $accessibleBrowseCategories);


		return 'RecordDrivers/List/cover_result.tpl';
	}

	function getFormat() : array {
		// overwrites class IndexRecordDriver getFormat() so that getBookCoverURL() call will work without warning notices
		return ['List'];
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() : string {
		// Don't check for highlighted values if highlighting is disabled:
		if (isset($this->fields['title_display'])) {
			return $this->fields['title_display'];
		}
		return '';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes - or
	 *                              null to show tags/notes from all user's lists.
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true) : string {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		//Switch template
		return 'RecordDrivers/List/listEntry.tpl';
	}

	public function getModule(): string {
		return 'MyAccount/MyList';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  ?string              Name of Smarty template file to display.
	 */
	public function getStaffView() : ?string {
		return null;
	}

	public function getDescription() {
		return !empty($this->fields['description']) ? $this->fields['description'] : '';
	}

	private function getListObject() : UserList|false {
		if ($this->listObject == null) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$this->listObject = new UserList();
			$this->listObject->id = $this->getId();
			if (!$this->listObject->find(true)) {
				$this->listObject = false;
			}
		}
		return $this->listObject;
	}
}