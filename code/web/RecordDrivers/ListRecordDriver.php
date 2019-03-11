<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';

/**
 * List Record Driver
 *
 * This class is designed to handle List records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class ListRecordDriver extends IndexRecordDriver
{
	public function __construct($record)
	{
		// Call the parent's constructor...
		parent::__construct($record);
	}

    function getBookcoverUrl($size = 'small'){
	    //TODO: Construct a book cover for the list
        return null;
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
		$interface->assign('summShortId', substr($id, 4)); //Trim the list prefix for the short id
		$interface->assign('summTitle', $this->getTitle(true));
		$interface->assign('summAuthor', $this->getPrimaryAuthor(true));
		if (isset($this->fields['description'])){
			$interface->assign('summDescription', $this->getDescription(true));
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
			}else if (isset($this->fields['_highlighting']['title_full'][0])){
				return $this->fields['_highlighting']['title_full'][0];
			}
		}

		if (isset($this->fields['title_display'])){
			return $this->fields['title_display'];
		}else{
			if (isset($this->fields['title_full'])){
				if (is_array($this->fields['title_full'])){
					return reset($this->fields['title_full']);
				}else{
					return $this->fields['title_full'];
				}
			}else{
				return '';
			}
		}
	}

	function getDescriptionFast($useHighlighting = false) {

		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['display_description'][0])) {
				return $this->fields['_highlighting']['display_description'][0];
			}
		}else{
			return $this->fields['display_description'];
		}
	}

	function getMoreInfoLinkUrl() {
		return $this->getLinkUrl();
	}
}