<?php
/**
 * Record Driver to handle loading data for Hoopla Records
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/18/14
 * Time: 10:50 AM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
class HooplaRecordDriver extends MarcRecord {
	/**
	 * Constructor.  We build the object using data from the Hoopla records stored on disk.
	 * Will be similar to a MarcRecord with slightly different functionality
	 *
	 * @param array|File_MARC_Record|string $record
	 * @param  GroupedWork $groupedWork;
	 * @access  public
	 */
	public function __construct($record, $groupedWork = null) {
		if ($record instanceof File_MARC_Record){
			$this->marcRecord = $record;
		}elseif (is_string($record)){
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->profileType = 'hoopla';
			$this->id = $record;

			$this->valid = MarcLoader::marcExistsForHooplaId($record);

			// Code block taken from MarcRecord.php, to set the indexing profile so that method getAbsoluteURL() works correctly. pascal 4-27-2017
			global $indexingProfiles;
			if (array_key_exists($this->profileType, $indexingProfiles)) {
				$this->indexingProfile = $indexingProfiles[$this->profileType];
			} else {
//				//Try to infer the indexing profile from the module
//				global $activeRecordProfile;
//				if ($activeRecordProfile) {
//					$this->indexingProfile = $activeRecordProfile;
//				} else {
//					$this->indexingProfile = $indexingProfiles['ils'];
//				}
			}


		}else{
			// Call the parent's constructor...
			parent::__construct($record, $groupedWork);

			// Also process the MARC record:
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if (!$this->marcRecord) {
				$this->valid = false;
			}
		}
		if ($groupedWork == null){
			parent::loadGroupedWork();
		}else{
			$this->groupedWork = $groupedWork;
		}
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getShortId()
	{
		return $this->id;
	}

	/**
	 * @return File_MARC_Record
	 */
	public function getMarcRecord(){
		if ($this->marcRecord == null){
			$this->marcRecord = MarcLoader::loadMarcRecordByHooplaId($this->id);
			global $timer;
			$timer->logTime("Finished loading marc record for hoopla record {$this->id}");
		}
		return $this->marcRecord;
	}

	/**
	 * @param $actions
	 * @return array
	 * @throws File_MARC_Exception
	 */
	public function getAccessLink($actions = null)
	{
		$title      = translate('hoopla_url_action');
		$marcRecord = $this->getMarcRecord();
		/** @var File_MARC_Data_Field[] $linkFields */
		$linkFields = $marcRecord->getFields('856');
		$fileOrUrl  = null;
		foreach ($linkFields as $linkField) {
			if ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 0) {
				$linkSubfield = $linkField->getSubfield('u');
				$fileOrUrl    = $linkSubfield->getData();
				break;
			}
		}
		if ($fileOrUrl != null) {
			$actions[] = array(
				'url' => $fileOrUrl,
				'title' => $title,
				'requireLogin' => false,
			);
		}
		return $actions;
	}

	protected function getRecordType(){
		return 'hoopla';
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/Hoopla/' . $recordId;
	}

	function getActions() {
		//TODO: If this is added to the related record, pass in the value
		$actions = array();

		/** @var Library $searchLibrary */
		$searchLibrary = Library::getSearchLibrary();
		if ($searchLibrary->hooplaLibraryID > 0) { // Library is enabled for Hoopla patron action integration
			$id = $this->getId();
			$title = translate('hoopla_checkout_action');
			$actions[] = array(
				'onclick' => "return VuFind.Hoopla.getHooplaCheckOutPrompt('$id')",
				'title'   => $title
			);

		} else {
			$actions = $this->getAccessLink($actions);
		}

		return $actions;
	}

	public function getItemActions($itemInfo){
		return array();
	}

	function getRecordActions($recordAvailable, $recordHoldable, $recordBookable, $relatedUrls = null){
		$actions = array();

		/** @var Library $searchLibrary */
		$searchLibrary = Library::getSearchLibrary();
		if ($searchLibrary->hooplaLibraryID > 0) { // Library is enabled for Hoopla patron action integration
			$id = $this->getId();
			$title = translate('hoopla_checkout_action');
			$actions[] = array(
				'onclick' => "return VuFind.Hoopla.getHooplaCheckOutPrompt('$id')",
				'title'   => $title
			);

		} else {
			$title = translate('hoopla_url_action');
			foreach ($relatedUrls as $url){
				$actions[] = array(
					'url' => $url['url'],
					'title' => $title,
					'requireLogin' => false,
				);
			}

		}

		return $actions;
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTOC();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1){
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$moreDetailsOptions['otherEditions'] = array(
				'label' => 'Other Editions and Formats',
				'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
				'hideByDefault' => false
			);
		}

		$notes = $this->getNotes();
		if (count($notes) > 0){
			$interface->assign('notes', $notes);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('Hoopla/view-more-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
			'label' => 'Subjects',
			'body' => $interface->fetch('Record/view-subjects.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false){
		$id = $this->getUniqueID();
		$formatCategory = $this->getFormatCategory();
		if (is_array($formatCategory)){
			$formatCategory = reset($formatCategory);
		}
		$formats = $this->getFormat();
		$format = reset($formats);
		global $configArray;
		if ($absolutePath){
			$bookCoverUrl = $configArray['Site']['url'];
		}else{
			$bookCoverUrl = $configArray['Site']['path'];
		}
		$bookCoverUrl .= "/bookcover.php?id={$id}&amp;size={$size}&amp;category=" . urlencode($formatCategory) . "&amp;format=" . urlencode($format) . "&amp;type=hoopla";
		$isbn = $this->getCleanISBN();
		if ($isbn){
			$bookCoverUrl .= "&amp;isn={$isbn}";
		}
		$upc = $this->getCleanUPC();
		if ($upc){
			$bookCoverUrl .= "&amp;upc={$upc}";
		}
		$issn = $this->getCleanISSN();
		if ($issn){
			$bookCoverUrl .= "&amp;issn={$issn}";
		}
		return $bookCoverUrl;
	}

	function getNumHolds(){
		return 0;
	}

	public function getStaffView()
	{
		parent::getStaffView();
		$hooplaExtract = new HooplaExtract();
//		$hooplaId = preg_replace('/^MWT/', '', $this->id);
		$hooplaId = HooplaDriver::recordIDtoHooplaID($this->id);
		if ($hooplaExtract->get('hooplaId', $hooplaId) == 1) {
			$hooplaData = array();
			foreach ($hooplaExtract->table() as $fieldName => $value_ignored) {
				$hooplaData[$fieldName] = $hooplaExtract->$fieldName;
			}
			global $interface;
			$interface->assign('hooplaExtract', $hooplaData);
		}
		return 'RecordDrivers/Hoopla/staff-view.tpl';

	}


}