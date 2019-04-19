<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';

class ListsRecordDriver extends IndexRecordDriver
{
	public function __construct($record)
	{
		// Call the parent's constructor...
		parent::__construct($record);
	}

    function getBookcoverUrl($size = 'small'){
        global $configArray;
        $id = $this->getId();
        $bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?type=list&amp;id={$id}&amp;size={$size}";
        return $bookCoverUrl;
    }

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list'){
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));
		$interface->assign('summShortId', $id);
		$interface->assign('summTitle', $this->getTitle(true));
		$interface->assign('summAuthor', $this->getPrimaryAuthor(true));
		if (isset($this->fields['description'])){
			$interface->assign('summDescription', $this->getDescription());
		}else{
			$interface->assign('summDescription', '');
		}
		if (isset($this->fields['num_titles'])){
			$interface->assign('summNumTitles', $this->fields['num_titles']);
		}else{
			$interface->assign('summNumTitles', 0);
		}

		// Obtain and assign snippet (highlighting) information:
		$snippets = $this->getHighlightedSnippets();
		$interface->assign('summSnippets', $snippets);

		return 'RecordDrivers/List/result.tpl';
	}

	public function getMoreDetailsOptions(){
		return array();
	}

	// initally taken From GroupedWorkDriver.php getBrowseResult();
	public function getBrowseResult(){
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$shortId = substr($id, 4);  // Trim the list prefix for the short id
//		$interface->assign('summShortId', $shortId);

		$url ='/MyAccount/MyList/'.$shortId;

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
//		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get Rating
//		$interface->assign('ratingData', $this->getRatingData());
		//TODO: list image. (list.png added in template)
//		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
//		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));


		return 'RecordDrivers/List/cover_result.tpl';
//		return 'RecordDrivers/GroupedWork/browse_result.tpl';
	}

	function getFormat() {
		// overwrites class IndexRecordDriver getFormat() so that getBookCoverURL() call will work without warning notices
		return array('List');
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle($useHighlighting = false) {
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['title_display'][0])){
				return $this->fields['_highlighting']['title_display'][0];
			}
		}

		if (isset($this->fields['title_display'])){
			return $this->fields['title_display'];
		}
		return '';
	}

	function getDescriptionFast($useHighlighting = false) {

		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['description'][0])) {
				return $this->fields['_highlighting']['description'][0];
			}
		}else{
			return $this->fields['description'];
		}
	}

	function getMoreInfoLinkUrl() {
		return $this->getLinkUrl();
	}

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display a summary of the item suitable for use in
     * user's favorites list.
     *
     * @access  public
     * @param object $user User object owning tag/note metadata.
     * @param int $listId ID of list containing desired tags/notes (or
     *                              null to show tags/notes from all user's lists).
     * @param bool $allowEdit Should we display edit controls?
     * @return  string              Name of Smarty template file to display.
     */
    public function getListEntry($user, $listId = null, $allowEdit = true)
    {
        // TODO: Implement getListEntry() method.
    }

    public function getModule()
    {
        return 'MyAccount/MyList';
    }

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display the full record information on the Staff
     * View tab of the record view page.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getStaffView()
    {
        return null;
    }

    public function getDescription()
    {
        return $this->fields['description'];
    }

    public function getItemActions($itemInfo)
    {
        return [];
    }

    public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null)
    {
        return [];
    }
}