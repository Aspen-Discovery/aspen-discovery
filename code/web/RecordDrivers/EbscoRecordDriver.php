<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

class EbscoRecordDriver extends RecordInterface {
	private $recordData;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($recordData) {
		if (is_string($recordData)){
			require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
			$edsApi = EDS_API::getInstance();
			list($dbId, $an) = explode(':', $recordData);
			$this->recordData = $edsApi->retrieveRecord($dbId, $an);
		}else{
			$this->recordData = $recordData;
		}
	}

	public function isValid(){
		return true;
	}

	public function getBookcoverUrl($size = 'small') {
		if ($this->recordData->ImageInfo){
			return (string)$this->recordData->ImageInfo->CoverArt->Target;
		}else{
			return null;
		}

	}

    /**
     * Overridden because we are linking straight to EBSCO
     * @param bool $unscoped
     * @return string
     */
	public function getLinkUrl($unscoped = false) {
		return $this->getRecordUrl();
	}

    /**
     * Overridden because we are linking straight to EBSCO
     * @return string
     */
    public function getAbsoluteUrl() {
        return $this->getRecordUrl();
    }

	public function getRecordUrl() {
		//TODO: Switch back to an internal link once we do a full EBSCO implementation
		//global $configArray;
		//return $configArray['Site']['path'] . '/EBSCO/Home?id=' . urlencode($this->getUniqueID());
		return $this->recordData->PLink;
	}

	public function getEbscoUrl() {
		return $this->recordData->PLink;
	}

	public function getModule() {
		return 'EBSCO';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list') {
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());

		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('summHasFullText', $this->hasFullText());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/EBSCO/result.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCombinedResult() {
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());

		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('summHasFullText', $this->hasFullText());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/EBSCO/combinedResult.tpl';
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

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() {
		if (isset($this->recordData->RecordInfo->BibRecord->BibEntity)){
			return (string)$this->recordData->RecordInfo->BibRecord->BibEntity->Titles->Title->TitleFull;
		}else{
			return 'Unknown';
		}
	}

    /**
     * The Table of Contents extracted from the record.
     * Returns null if no Table of Contents is available.
     *
     * @access  public
     * @return  array              Array of elements in the table of contents
     */
	public function getTableOfContents() {
		return null;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() {
		return  (string)$this->recordData->Header->DbId . ':'. (string)$this->recordData->Header->An;
	}

	/**
	 * Does this record have searchable full text in the index?
	 *
	 * Note: As of this writing, searchable full text is not a VuFind feature,
	 *       but this method will be useful if/when it is eventually added.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasFullText() {
		return $this->recordData->FullText->Text->Availability == 1;
	}

	public function getFullText() {
		$fullText = (string)$this->recordData->FullText->Text->Value;
		$fullText = html_entity_decode($fullText);
		$fullText = preg_replace('/<anid>.*?<\/anid>/', '', $fullText);
		return $fullText;
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews() {
		return false;
	}

	public function getDescription() {
		return '';
	}

	public function getMoreDetailsOptions() {
		// TODO: Implement getMoreDetailsOptions() method.
	}

	public function getItemActions($itemInfo) {
		// TODO: Implement getItemActions() method.
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null) {
		// TODO: Implement getRecordActions() method.
	}

	public function getFormats() {
		return (string)$this->recordData->Header->PubType;
	}

	public function getCleanISSN() {
		return '';
	}

	public function getSourceDatabase() {
		return $this->recordData->Header->DbLabel;
	}

	public function getAuthor() {
		if (count($this->recordData->Items)){
			foreach ($this->recordData->Items->Item as $item){
				if ($item->Name == 'Author'){
					return strip_tags((string)$item->Data);
				}
			}
		}
		return "";
	}

	public function getExploreMoreInfo(){
		global $configArray;
		$exploreMoreOptions = array();
		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
			require_once ROOT_DIR . '/sys/ExploreMore.php';
			$exploreMore = new ExploreMore();
			$exploreMore->loadExploreMoreSidebar('ebsco', $this);
		}
		return $exploreMoreOptions;
	}

	public function getAllSubjectHeadings(){
		$subjectHeadings = array();
		if (count(@$this->recordData->RecordInfo->BibRecord->BibEntity->Subjects) != 0){
			foreach ($this->recordData->RecordInfo->BibRecord->BibEntity->Subjects->Subject as $subject){
				$subjectHeadings[] = (string)$subject->SubjectFull;
			}
		}
		return $subjectHeadings;
	}

	public function getPermanentId(){
		return $this->getUniqueID();
	}
}