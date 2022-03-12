<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';

class Greenhouse_SitesByLocation extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$siteMarkers = [];
		$unlocatedSites = [];
		$siteCache = new AspenSiteCache();
		$siteCache->find();
		$numMarkers = 0;
		$sumLatitude = 0;
		$sumLongitude = 0;
		while ($siteCache->fetch()) {
			if (!empty($siteCache->latitude) && !empty($siteCache->longitude)) {
				$siteMarkers[] = clone $siteCache;
				$sumLatitude += $siteCache->latitude;
				$sumLongitude += $siteCache->longitude;
				$numMarkers++;
			}else{
				$unlocatedSites[] = clone $siteCache;
			}
		}
		$center = [
			'latitude' => $sumLatitude / $numMarkers,
			'longitude' => $sumLongitude / $numMarkers
		];
		$interface->assign('siteMarkers' ,$siteMarkers);
		$interface->assign('unlocatedSites' ,$unlocatedSites);
		$interface->assign('center' ,$center);

		require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
		$googleSettings = new GoogleApiSetting();
		if ($googleSettings->find(true)){
			$mapsKey = $googleSettings->googleMapsKey;
		}else{
			$mapsKey = null;
		}
		$interface->assign('mapsKey', $mapsKey);

		$this->display('sitesByLocation.tpl', 'Sites By Location');
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Status');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse-stats-reports';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}
}