<?php
/**
 * Displays Information about Places stored in the Digital Repository
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Entity.php';
class Archive_Place extends Archive_Entity{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->recordDriver->loadLinkedData();
		$this->loadRelatedContentForEntity();

		$interface->assign('showExploreMore', true);

		/** @var PlaceDriver $placeDriver */
		$placeDriver = $this->recordDriver;
		$geoData = $placeDriver->getGeoData();
		$addressInfo = $interface->getVariable('addressInfo');
		if ($addressInfo == null || count($addressInfo) == 0 && $geoData != null){

			$addressInfo['latitude'] = $geoData['latitude'];
			$addressInfo['longitude'] = $geoData['longitude'];

			$interface->assign('addressInfo', $addressInfo);
		}


		// Display Page
		$this->display('baseArchiveObject.tpl');
	}
}