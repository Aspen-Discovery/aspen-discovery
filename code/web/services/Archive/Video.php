<?php
/**
 * Allows display of a Video from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:44 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Video  extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$this->loadArchiveObjectData();

		if ($this->archiveObject->getDatastream('MP4') != null) {
			$interface->assign('videoLink', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/MP4/view");
		}else if ($this->archiveObject->getDatastream('OBJ') != null) {
			$interface->assign('videoLink', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/OBJ/view");
		}

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('video.tpl');
	}
}