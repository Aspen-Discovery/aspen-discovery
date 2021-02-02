<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Page extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		$fedoraUtils = FedoraUtils::getInstance();

		$this->loadArchiveObjectData();

		//Get the contents of the book
		$interface->assign('showExploreMore', true);


		if (isset($_REQUEST['viewer'])){
			$interface->assign('activeViewer', $_REQUEST['viewer']);
		}else{
			$interface->assign('activeViewer', 'image');
		}

		$page = array(
			'pid' => $this->pid
		);
		$pageObject = $fedoraUtils->getObject($page['pid']);
		if ($pageObject->getDataStream('JP2') != null){
			$page['jp2'] = $objectUrl . '/' . $page['pid'] . '/datastream/JP2/view';
		}
		if ($pageObject->getDataStream('PDF') != null){
			$page['pdf'] = $objectUrl . '/' . $page['pid'] . '/datastream/PDF/view';
		}
		$mods = $fedoraUtils->getModsData($pageObject);
		$transcript = $fedoraUtils->getModsValue('transcriptionText', 'marmot', $mods);

		$parentObject = $this->recordDriver->getParentObject();
		if (strlen($transcript) > 0) {
			$page['transcript'] = 'mods:' . $page['pid'];
		}else {
			$hasTranscript = false;
			if ($parentObject != null){
				$modsForParent = $fedoraUtils->getModsData($parentObject);
				$transcript = $fedoraUtils->getModsValue('transcriptionText', 'marmot', $modsForParent);
				if (strlen($transcript) > 0) {
					$page['transcript'] = 'mods:' . $parentObject->id;
					$hasTranscript = true;
				}
			}
			if (!$hasTranscript){
				if ($pageObject->getDataStream('HOCR') != null && $pageObject->getDataStream('HOCR')->size > 1) {
					$page['transcript'] = $objectUrl . '/' . $page['pid'] . '/datastream/HOCR/view';
				} elseif ($pageObject->getDataStream('OCR') != null && $pageObject->getDataStream('OCR')->size > 1) {
					$page['transcript'] = $objectUrl . '/' . $page['pid'] . '/datastream/OCR/view';
				}
			}
		}
		$page['cover'] = $fedoraUtils->getObjectImageUrl($pageObject, 'thumbnail');
		$interface->assign('page', $page);


		//Check download settings for the parent object
		if ($parentObject != null){

			/** @var CollectionRecordDriver $collection */
			$anonymousOriginalDownload = true;
			$verifiedOriginalDownload = true;
			$anonymousLcDownload = true;
			$verifiedLcDownload = true;

			/** @var BookDriver $parentDriver */
			$parentDriver = RecordDriverFactory::initRecordDriver($parentObject);
			foreach ($parentDriver->getRelatedCollections() as $collection) {
				/** @var CollectionRecordDriver $collectionDriver */
				$collectionDriver = $collection['driver'];
				if (!$collectionDriver->canAnonymousDownloadOriginal()) {
					$anonymousOriginalDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadOriginal()) {
					$verifiedOriginalDownload = false;
				}
				if (!$collectionDriver->canAnonymousDownloadLC()) {
					$anonymousLcDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadLC()) {
					$verifiedLcDownload = false;
				}
			}
			$viewingRestrictions = $this->recordDriver->getViewingRestrictions();
			foreach ($viewingRestrictions as $viewingRestriction){
				$restrictionLower = str_replace(' ', '', strtolower($viewingRestriction));
				if ($restrictionLower == 'preventanonymousmasterdownload'){
					$anonymousOriginalDownload = false;
				}
				if ($restrictionLower == 'preventverifiedmasterdownload'){
					$verifiedOriginalDownload = false;
					$anonymousOriginalDownload = false;
				}
				if ($restrictionLower == 'anonymousoriginaldownload'){
					$anonymousOriginalDownload = true;
					$verifiedOriginalDownload = true;
				}
				if ($restrictionLower == 'verifiedoriginaldownload'){
					$anonymousOriginalDownload = true;
				}
			}
			if (!$anonymousOriginalDownload){
				$interface->assign('anonymousOriginalDownload', $anonymousOriginalDownload);
			}
			if ($anonymousOriginalDownload){
				$verifiedOriginalDownload = true;
			}
			if (!$verifiedOriginalDownload) {
				$interface->assign('verifiedOriginalDownload', $verifiedOriginalDownload);
			}
			if (!$anonymousLcDownload){
				$interface->assign('anonymousLcDownload', $anonymousLcDownload);
			}
			if ($anonymousLcDownload){
				$verifiedLcDownload = true;
			}
			if (!$verifiedLcDownload) {
				$interface->assign('verifiedLcDownload', $verifiedLcDownload);
			}
		}

		// Display Page
		$this->display('page.tpl');
	}

}