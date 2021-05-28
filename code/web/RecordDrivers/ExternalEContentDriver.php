<?php

require_once ROOT_DIR . '/RecordDrivers/BaseEContentDriver.php';
class ExternalEContentDriver extends BaseEContentDriver{
	function isItemAvailable($itemId, $totalCopies){
		return true;
	}
	function isEContentHoldable($locationCode, $eContentFieldData){
		return false;
	}
	function isLocalItem($locationCode, $eContentFieldData){
		return $this->isLibraryItem($locationCode, $eContentFieldData);
	}
	function isLibraryItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else if ($sharing == 'library'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->econtentLocationsToInclude == 'all' || strlen($searchLibrary->econtentLocationsToInclude) == 0  || $searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				// TODO: econtentLocationsToInclude setting no longer in use. plb 5-17-2016
				return true;
			}else{
				return false;
			}
		}else{
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if ($searchLibrary == null || $searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks || strpos($locationCode, $searchLocation->code) === 0){
				return true;
			}else{
				return false;
			}
		}
	}

	function isValidForUser($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->econtentLocationsToInclude == 'all' || strlen($searchLibrary->econtentLocationsToInclude) == 0 || (strpos($searchLibrary->econtentLocationsToInclude, $locationCode) !== FALSE)){
				return true;
			}else{
				return false;
			}
		}else if ($sharing == 'library'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}else{
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if ($searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks || strpos($locationCode, $searchLocation->code) === 0){
				return true;
			}else{
				return false;
			}
		}
	}

	function getSharing($locationCode, $eContentFieldData){
		if (strpos($locationCode, 'mdl') === 0){
			return 'shared';
		}else{
			$sharing = 'library';
			if (count($eContentFieldData) >= 3){
				$sharing = trim(strtolower($eContentFieldData[2]));
			}
			return $sharing;
		}
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Get Related Records to make sure we initialize items
		$recordInfo = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());

		$interface->assign('items', $recordInfo->getItemSummary());

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		$moreDetailsOptions['copies'] = array(
			'label' => 'Copies',
			'body' => $interface->fetch('ExternalEContent/view-items.tpl'),
			'openByDefault' => true
		);

		$notes = $this->getNotes();
		if (count($notes) > 0){
			$interface->assign('notes', $notes);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('ExternalEContent/view-more-details.tpl'),
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

	function getModule() : string{
		return 'ExternalEContent';
	}

	function getFormats(){
		global $configArray;
		$formats = array();
		//Get the format based on the iType
		$itemFields = $this->getMarcRecord()->getFields('989');
		/** @var File_MARC_Data_Field[] $itemFields */
		foreach ($itemFields as $itemField){
			$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
			$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
			if ($eContentData && strpos($eContentData, ':') > 0){
				$eContentFieldData = explode(':', $eContentData);
				if ($this->isValidForUser($locationCode, $eContentFieldData)){
					$iTypeField = $itemField->getSubfield($configArray['Reindex']['iTypeSubfield'])->getData();
					$format = mapValue('econtent_itype_format', $iTypeField);
					$formats[$format] = $format;
				}
			}
		}
		return $formats;
	}

	function getEContentFormat($fileOrUrl, $iType)
	{
		return mapValue('econtent_itype_format', $iType);
	}

	function getRecordUrl()
	{
		$recordId = $this->getUniqueID();

		return "/{$this->getModule()}/$recordId";
	}

	public function getIdWithSource()
	{
		return 'external_econtent:' . $this->profileType . ':' . $this->id;
	}
}
