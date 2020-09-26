<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');

class Report_HoldsReport extends Admin_Admin {
	function launch(){
		global $interface;
		global $configArray;
		$user = UserAccount::getLoggedInUser();

// LOCATION DROPDOWN ARRAY
		$locationList = $this->getAllowedReportLocations();
		$locationLookupList = array();
		foreach ($locationList as $location){
			$locationLookupList[$location->code] = $location->subdomain . " " . $location->displayName;
		}
		asort($locationLookupList);
		$interface->assign('locationLookupList', $locationLookupList);
		if (isset($_REQUEST['location'])) {
			$selectedLocation = $_REQUEST['location'];
		} elseif (count($locationLookupList) === 1){
			$selectedLocation = array_key_first($locationLookupList);
		} else {
				$selectedLocation = null;
		}
		$interface->assign('selectedLocation', $selectedLocation);
		if (!is_null($selectedLocation)) {
			$data = CatalogFactory::getCatalogConnectionInstance()->getHoldsReportData($selectedLocation);
		} else {
			$data = null;
		}
		$interface->assign('reportData', $data);

		/*
		// TODO : MAKE DOWNLOAD AVAILABLE
		if (isset($_REQUEST['download'])){
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename=' . $selectedReport);
			header('Content-Length:' . filesize($reportDir . '/' . $selectedReport));
			foreach ($fileData as $row){
				foreach ($row as $index => $cell){
					if ($index != 0){
						echo(",");
					}
					if (strpos($cell, ',') != false){
						echo('"' . $cell . '"');
					}else{
						echo($cell);
					}

				}
				echo("\r\n");
			}
			exit;
		}
		*/

		$this->display('holdsReport.tpl', 'School Fill List');
	}

	function getAllowedReportLocations(){
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();
		$location = new Location();
		$location->orderBy('code');
		if (UserAccount::userHasPermission('View Location Holds Reports')){
			//Scope to just locations for the user based on home branch
			$location->locationId = $user->homeLocationId;
		}
		$location->find();
		$locationList = array();
		while ($location->fetch()){
			$locationList[$location->locationId] = clone $location;
		}
		return $locationList;
	}

	function getBreadcrumbs()
	{
// TODO : is this the right section?
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#circulation_reports', 'Circulation Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Holds Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'circulation_reports';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View All Holds Reports', 'View Location Holds Reports');
	}
}
