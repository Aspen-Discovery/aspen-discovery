<?php

/**
 * Allows display of Audio files
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/17/2016
 * Time: 9:52 AM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Audio  extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

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