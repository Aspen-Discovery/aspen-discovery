<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_LargeImage extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;

		$hasLargeImage = false;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		$hasImage = false;
		if ($this->archiveObject->getDatastream('JP2') != null) {
			$interface->assign('large_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/JP2/view");
			$hasImage = true;
		}
		if ($this->archiveObject->getDatastream('JPG') != null){
			if ($hasImage == false) {
				$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/JPG/view");
				$hasImage = true;
			}
		}
		if ($this->archiveObject->getDatastream('LC') != null){
			if ($hasImage == false) {
				$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/LC/view");
				$hasImage = true;
			}
			$hasLargeImage = true;
		}
		if ($this->archiveObject->getDatastream('MC') != null){
			if ($hasImage == false) {
				$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/MC/view");
				$hasImage = true;
			}
		}
		if (!$hasImage){
			$interface->assign('noImage', true);
		}
		$interface->assign('hasLargeImage', $hasLargeImage);

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('largeImage.tpl');
	}
}