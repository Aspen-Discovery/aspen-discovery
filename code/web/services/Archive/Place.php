<?php

require_once ROOT_DIR . '/services/Archive/Entity.php';
class Archive_Place extends Archive_Entity{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->recordDriver->loadLinkedData();
		$this->loadRelatedContentForEntity();

		$interface->assign('showExploreMore', true);

		/** @var PlaceRecordDriver $placeDriver */
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