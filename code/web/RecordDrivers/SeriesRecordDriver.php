<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';

class SeriesRecordDriver extends IndexRecordDriver {
	private Series|null|false $seriesObject = null;
	private bool $valid = true;

	public function __construct($record) {
		// Call the parent's constructor...
		if (is_string($record)) {

			$searchObject = SearchObjectFactory::initSearchObject('Series');
			disableErrorHandler();
			try {
				$fields = $searchObject->getRecord($record);
				if ($fields == null) {
					$this->valid = false;
				} else {
					parent::__construct($fields);
				}
			} catch (Exception $e) {
				$this->valid = false;
			}
			enableErrorHandler();
		} else {
			parent::__construct($record);
		}
	}

	public function isValid() {
		return $this->valid;
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false, $seriesMember = false, $memberId = 0) {
		global $configArray;
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		if (!$seriesMember) {
			$id = $this->getId();
			$bookCoverUrl = $bookCoverUrl . "/bookcover.php?type=series&id={$id}&size={$size}";
		} else {
			$id = $memberId;
			$bookCoverUrl = $bookCoverUrl . "/bookcover.php?type=seriesMember&id={$id}&size={$size}";
		}
		return $bookCoverUrl;
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
	public function getSearchResult($view = 'list', $showListsAppearingOn = true) {
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));
		$interface->assign('summShortId', $id);
		$interface->assign('summTitle', $this->getTitle(true));
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		if (isset($this->fields['description'])) {
			$interface->assign('summDescription', $this->getDescription());
		} else {
			$interface->assign('summDescription', '');
		}
		if (isset($this->fields['audience'])) {
			$interface->assign('summAudience', $this->getAudience());
		} else {
			$interface->assign('summAudience', '');
		}
		if (isset($this->fields['num_titles'])) {
			$interface->assign('summNumTitles', $this->fields['num_titles']);
		} else {
			$interface->assign('summNumTitles', 0);
		}
		$interface->assign('summUrl', $this->getAbsoluteUrl());

		global $solrScope;
		if ($this->fields["local_time_since_added_$solrScope"]) {
			$interface->assign('isNew', $this->checkIfContainsNewTitles());
		} else {
			$interface->assign('isNew', false);
		}

		$listObject = $this->getSeriesObject();

		if ($showListsAppearingOn) {
			//Check to see if there are lists the record is on
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('Series', $this->getId());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		$interface->assign('source', $this->fields['source'] ?? '');

		return 'RecordDrivers/Series/result.tpl';
	}

	public function getMoreDetailsOptions() {
		return [];
	}

	public function checkIfContainsNewTitles() {
		global $solrScope;
		$series = $this->getSeriesObject();
		$records = $series->getSeriesRecords(0, 1, 'recordDrivers', 'id desc', false);
		foreach ($records as $record) {
			if ($record->fields && isset($record->fields["local_time_since_added_$solrScope"])) {
				if (in_array('Week', $record->fields["local_time_since_added_$solrScope"])) {
					return true;
				}
			}
		}
		return false;
	}

	// initially taken From GroupedWorkDriver.php getBrowseResult();
	public function getBrowseResult() {
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = '/Series/' . $id;

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get cover image size
		global $interface;
		$appliedTheme = $interface->getAppliedTheme();

		global $solrScope;
		if ($this->fields["local_time_since_added_$solrScope"]) {
			$interface->assign('isNew', in_array('Week', $this->fields["local_time_since_added_$solrScope"]));
		} else {
			$interface->assign('isNew', false);
		}

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));

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


		return 'RecordDrivers/Series/cover_result.tpl';
	}

	function getFormat() {
		// overwrites class IndexRecordDriver getFormat() so that getBookCoverURL() call will work without warning notices
		return ['Series'];
	}

	/**
	 * Get the full title of the record.
	 *
	 * @param bool $useHighlighting
	 * @return  string
	 */
	public function getTitle($useHighlighting = false) {
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['title_display'][0])) {
				return $this->fields['_highlighting']['title_display'][0];
			}
		}

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
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true) {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		//Switch template
		return 'RecordDrivers/Series/listEntry.tpl';
	}

	public function getModule(): string {
		return 'Series';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView() {
		return null;
	}

	public function getDescription() {
		return !empty($this->fields['description']) ? $this->fields['description'] : '';
	}

	public function getAudience() {
		return !empty($this->fields['audience']) ? $this->fields['audience'] : '';
	}

	private function getSeriesObject() {
		if ($this->seriesObject == null) {
			require_once ROOT_DIR . '/sys/Series/Series.php';
			$this->seriesObject = new Series();
			$this->seriesObject->id = $this->getId();
			if (!$this->seriesObject->find(true)) {
				$this->seriesObject = false;
			}
		}
		return $this->seriesObject;
	}
}