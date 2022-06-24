<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';

class CourseReservesRecordDriver extends IndexRecordDriver
{
	private $courseReservesObject;
	private $valid = true;
	public function __construct($record)
	{
		// Call the parent's constructor...
		if (is_string($record)) {
			/** @var SearchObject_CourseReservesSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('CourseReserves');
			disableErrorHandler();
			try {
				$fields = $searchObject->getRecord($record);
				if ($fields == null) {
					$this->valid = false;
				}else {
					parent::__construct($fields);
				}
			}catch (Exception $e){
				$this->valid = false;
			}
			enableErrorHandler();
		}else {
			parent::__construct($record);
		}
	}

	public function isValid(){
		return $this->valid;
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false)
	{
		global $configArray;
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$id = $this->getId();
		$bookCoverUrl = $bookCoverUrl . "/bookcover.php?type=course_reserves&amp;id={$id}&amp;size={$size}";
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
	public function getSearchResult($view = 'list', $showListsAppearingOn = true){
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
		if (isset($this->fields['num_titles'])){
			$interface->assign('summNumTitles', $this->fields['num_titles']);
		}else{
			$interface->assign('summNumTitles', 0);
		}
		$interface->assign('summDateUpdated', $this->getCourseReserve()->dateUpdated);

		return 'RecordDrivers/CourseReserve/result.tpl';
	}

	public function getMoreDetailsOptions(){
		return array();
	}

	// initially taken From GroupedWorkDriver.php getBrowseResult();
	public function getBrowseResult(){
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url ='/CourseReserves/'.$id;

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

        //Get cover image size
        global $interface;
        $appliedTheme = $interface->getAppliedTheme();

        $interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));

        if ($appliedTheme != null && $appliedTheme->browseCategoryImageSize == 0) {
            $interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
        }
        else {
            $interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
        }

		return 'RecordDrivers/CourseReserve/cover_result.tpl';
	}

	function getFormat() {
		// overwrites class IndexRecordDriver getFormat() so that getBookCoverURL() call will work without warning notices
		return array('Course Reserve');
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
			if (isset($this->fields['_highlighting']['title_display'][0])){
				return $this->fields['_highlighting']['title_display'][0];
			}
		}

		if (isset($this->fields['title_display'])){
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
	public function getListEntry($listId = null, $allowEdit = true)
	{
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		//Switch template
		return 'RecordDrivers/CourseReserve/listEntry.tpl';
	}

	public function getModule() : string
	{
		return 'CourseReserve';
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
		return '';
	}

	private function getCourseReserve()
	{
		if ($this->courseReservesObject == null){
			require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
			$this->courseReservesObject = new CourseReserve();
			$this->courseReservesObject->id = $this->getId();
			if (!$this->courseReservesObject->find(true)){
				$this->courseReservesObject = false;
			}
		}
		return $this->courseReservesObject;
	}

	/**
	 * Get the main author of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	public function getPrimaryAuthor()
	{
		return isset($this->fields['instructor_display']) ? $this->fields['instructor_display'] : '';
	}
}