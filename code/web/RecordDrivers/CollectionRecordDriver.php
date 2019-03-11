<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class CollectionRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return "Exhibit";
	}

	private $anonymousMasterDownload = null;
	private $verifiedMasterDownload = null;
	private $anonymousLcDownload = null;
	private $verifiedLcDownload = null;
	public function canAnonymousDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->anonymousMasterDownload;
	}
	public function canVerifiedDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->verifiedMasterDownload;
	}

	public function canAnonymousDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->anonymousLcDownload;
	}
	public function canVerifiedDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->verifiedLcDownload;
	}

	public function loadDownloadRestrictions(){
		if ($this->anonymousMasterDownload != null){
			return;
		}
		$this->anonymousMasterDownload = $this->getModsValue('anonymousMasterDownload', 'marmot') != 'no';
		$this->verifiedMasterDownload = $this->getModsValue('verifiedMasterDownload', 'marmot') != 'no';
		$this->anonymousLcDownload = $this->getModsValue('anonymousLcDownload', 'marmot') != 'no';
		$this->verifiedLcDownload = $this->getModsValue('verifiedLcDownload', 'marmot') != 'no';
	}

	public function getFormat(){
		return 'Collection';
	}

	public function getNextPrevLinks($currentCollectionItemPID){
		global $interface;

		$collectionChildren = $this->getChildren();
		$currentCollectionItemIndex = array_search($currentCollectionItemPID, $collectionChildren);
		if ($currentCollectionItemIndex !== false) {
			$interface->assign('collectionPid', $this->pid);
			$interface->assign('page', 1); // Value ignored for collections at this time

			// Previous Collection Item
			if ($currentCollectionItemIndex > 0) {
				$previousIndex = $currentCollectionItemIndex - 1;
				$fedoraUtils = FedoraUtils::getInstance();
				$previousCollectionItemPid = $collectionChildren[$previousIndex];
				/** @var IslandoraRecordDriver $previousRecord */
				$previousRecord = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($previousCollectionItemPid));
				if (!empty($previousRecord)) {
					$interface->assign('previousIndex', $previousIndex);
					$interface->assign('previousType', 'Archive');
					$interface->assign('previousUrl', $previousRecord->getLinkUrl());
					$interface->assign('previousTitle', $previousRecord->getTitle());
				}
			}

			// Next Collection Item
			$nextIndex = $currentCollectionItemIndex + 1;
			if ($nextIndex < count($collectionChildren) ) {
				if (!isset($fedoraUtils)) { $fedoraUtils = FedoraUtils::getInstance(); }
				$nextCollectionItemPid = $collectionChildren[$nextIndex];
				$nextRecord = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($nextCollectionItemPid));
				if (!empty($nextRecord)) {
						$interface->assign('nextIndex', $nextIndex);
						$interface->assign('nextType', 'Archive');
						$interface->assign('nextUrl', $nextRecord->getLinkUrl());
						$interface->assign('nextTitle', $nextRecord->getTitle());
						$interface->assign('nextPage', 1); // Value ignored for collections at this time
				}
			}

		}
	}


}