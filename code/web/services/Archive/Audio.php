<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Audio  extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$this->loadArchiveObjectData();

		if ($this->archiveObject->getDatastream('PROXY_MP3') != null) {
			$interface->assign('audioLink', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/PROXY_MP3/view");
		}elseif ($this->archiveObject->getDatastream('OBJ') != null) {
			$interface->assign('audioLink', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/OBJ/view");
		}

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('audio.tpl');
	}
}