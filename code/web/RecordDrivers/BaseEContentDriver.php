<?php

require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

abstract class BaseEContentDriver  extends MarcRecordDriver {
	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
     * @param  GroupedWork $groupedWork ;
     * @access  public
	 */
	public function __construct($recordData, $groupedWork = null){
		parent::__construct($recordData, $groupedWork);
	}


	abstract function isEContentHoldable($locationCode, $eContentFieldData);
	abstract function isLocalItem($locationCode, $eContentFieldData);
	abstract function isLibraryItem($locationCode, $eContentFieldData);
	abstract function isItemAvailable($itemId, $totalCopies);

	abstract function isValidForUser($locationCode, $eContentFieldData);

	abstract function getSharing($locationCode, $eContentFieldData);

	abstract function getEContentFormat($fileOrUrl, $iType);

	protected function isHoldable(){
		return false;
	}

	public function getItemActions($itemInfo){
		if ($itemInfo instanceof Grouping_Item){
			return $this->createActionsFromUrls($itemInfo->getRelatedUrls());
		}else{
			return $this->createActionsFromUrls($itemInfo['relatedUrls']);
		}
	}

	public function getRecordActions($relatedRecord, $isAvailable, $isHoldable, $volumeData = null){
		return [];
	}

	function createActionsFromUrls($relatedUrls){
		global $configArray;
		$actions = array();
		$i = 0;
		foreach ($relatedUrls as $urlInfo){
			//Revert to access online per Karen at CCU.  If people want to switch it back, we can add a per library switch
			$title = 'Access Online';
			$alt = 'Available online from ' . $urlInfo['source'];
			$action = $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $this->id . "/AccessOnline?index=$i";
			$fileOrUrl = isset($urlInfo['url']) ? $urlInfo['url'] : $urlInfo['file'];
			if (strlen($fileOrUrl) > 0){
				if (strlen($fileOrUrl) >= 3){
					$extension =strtolower(substr($fileOrUrl, strlen($fileOrUrl), 3));
					if ($extension == 'pdf'){
						$title = 'Access PDF';
					}
				}
				$actions[] = array(
					'url' => $action,
					'redirectUrl' => $fileOrUrl,
					'title' => $title,
					'requireLogin' => false,
					'alt' => $alt,
					'target' => '_blank',
				);
				$i++;
			}
		}

		return $actions;
	}

	function getRelatedRecord() {
		return $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
	}

	public function getRecordType(){
		return $this->profileType;
	}
}
