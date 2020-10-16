<?php
/**
 * Displays Student Barcodes in sheets by classroom to facilitate school library work
 *
 * @category Aspen
  * @author James Staub <james.staub@nashville.gov>
 * Date: 2020 09 28
 */

require_once(ROOT_DIR . '/services/Admin/Admin.php');

class Report_StudentBarcodes extends Admin_Admin {
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
		$selectedLocation = isset($_REQUEST['location']) ? $_REQUEST['location'] : reset($locationLookupList);
		$selectedHomeroom = !empty($_REQUEST['homeroom']) ? $_REQUEST['homeroom'] : null;
		$homeroomList = CatalogFactory::getCatalogConnectionInstance()->getStudentBarcodeDataHomerooms($selectedLocation);
		$homeroomLookupList = array();
		foreach ($homeroomList as $homeroom){
			$homeroomLookupList[$homeroom['HOMEROOMID']] = $homeroom['GRADE'] . " " . $homeroom['HOMEROOMNAME'];
		}
		asort($homeroomLookupList);
		$interface->assign('homeroomLookupList', $homeroomLookupList);
		if (!empty($selectedLocation) && empty($selectedHomeroom)) {
			$interface->assign('selectedLocation', $selectedLocation);
			$selectedHomeroom = reset($homeroomLookupList);
			$data = null;
		}
		if (!empty($selectedLocation) && !empty($selectedHomeroom)) {
			$interface->assign('selectedLocation', $selectedLocation);			$interface->assign('selectedHomeroom', $selectedHomeroom);
			$data = CatalogFactory::getCatalogConnectionInstance()->getStudentBarcodeData($selectedLocation, $selectedHomeroom);
		} else {
			$data = null;
		}

		$interface->assign('reportData', $data);

		if (isset($_REQUEST['download'])){
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename=' . $selectedLocation . '.csv');
			$fp = fopen('php://output', 'w');
			//add BOM to fix UTF-8 in Excel
			fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
			$count_row = 0;
			foreach ($data as $row){
				if ($count_row == 0) {
					fputcsv($fp, array_keys($row));
				}
				fputcsv($fp, $row);
				$count_row++;
			}
			exit;
		}

		$this->display('studentBarcodes.tpl', 'Student Barcodes');
	}

	function getAllowedReportLocations(){
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();
		$location = new Location();
		$location->orderBy('code');
		if (UserAccount::userHasPermission('View Location Student Reports')){
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
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#circulation_reports', 'Circulation Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Student Barcodes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'circulation_reports';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View All Student Reports', 'View Location Student Reports']);
	}
}