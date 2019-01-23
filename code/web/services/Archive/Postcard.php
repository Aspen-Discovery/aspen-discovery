<?php
/**
 * Allows display of a single image from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:43 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Postcard extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		//Get the front of the object
		$fedoraUtils = FedoraUtils::getInstance();
		$postCardSides = $fedoraUtils->getCompoundObjectParts($this->pid);

		$front = $fedoraUtils->getObject($postCardSides[1]['pid']);
		$back = $fedoraUtils->getObject($postCardSides[2]['pid']);
		if ($front->getDatastream('JP2') != null) {
			$interface->assign('front_image', $configArray['Islandora']['objectUrl'] . "/{$front->id}/datastream/JP2/view");
		}
		if ($front->getDatastream('MC') != null){
			$interface->assign('front_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$front->id}/datastream/MC/view");
		}elseif ($front->getDatastream('SC') != null){
			$interface->assign('front_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$front->id}/datastream/SC/view");
		}elseif ($front->getDatastream('TN') != null){
			$interface->assign('front_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$front->id}/datastream/TN/view");
		}

		if ($back->getDatastream('JP2') != null) {
			$interface->assign('back_image', $configArray['Islandora']['objectUrl'] . "/{$back->id}/datastream/JP2/view");
		}
		if ($back->getDatastream('MC') != null){
			$interface->assign('back_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$back->id}/datastream/MC/view");
		}elseif ($back->getDatastream('SC') != null){
			$interface->assign('back_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$back->id}/datastream/SC/view");
		}elseif ($back->getDatastream('TN') != null){
			$interface->assign('back_thumbnail', $configArray['Islandora']['objectUrl'] . "/{$back->id}/datastream/TN/view");
		}

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('postcard.tpl');
	}


}