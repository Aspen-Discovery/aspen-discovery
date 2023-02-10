<?php
/**
 * Displays Student Checkout/Overdues as slips or sheets by classroom to facilitate school library work
 *
 * @author James Staub <james.staub@nashville.gov>
 * Date: 2020 09 28
 * @category Aspen
 */

require_once(ROOT_DIR . '/services/Admin/Admin.php');

class Report_StudentReport extends Admin_Admin {
	function launch() {
		global $interface;
		global $configArray;
		$user = UserAccount::getLoggedInUser();

// LOCATION DROPDOWN ARRAY
		$locationList = $this->getAllowedReportLocations();
		$locationLookupList = [];
		foreach ($locationList as $location) {
			$locationLookupList[$location->code] = $location->subdomain . " " . $location->displayName;
		}
		asort($locationLookupList);
		$interface->assign('locationLookupList', $locationLookupList);
		$selectedLocation = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
		$interface->assign('selectedLocation', $selectedLocation);
// OTHER FORM VARIABLES
		$showOverdueOnly = isset($_REQUEST['showOverdueOnly']) ? $_REQUEST['showOverdueOnly'] : 'overdue';
		$interface->assign('showOverdueOnly', $showOverdueOnly);
		$now = time();
		$data = CatalogFactory::getCatalogConnectionInstance()->getStudentReportData($selectedLocation, $showOverdueOnly, $now);
		$interface->assign('reportData', $data);
		$interface->assign('reportDateTime', date("Y-m-d\TH:i:sO", $now));

		if (isset($_REQUEST['download'])) {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename=' . $selectedLocation . '.csv');
			$fp = fopen('php://output', 'w');
			//add BOM to fix UTF-8 in Excel
			fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
			$count_row = 0;
			foreach ($data as $row) {
				if ($count_row == 0) {
					fputcsv($fp, array_keys($row));
				}
				fputcsv($fp, $row);
				$count_row++;
			}
			exit;
		}

		$this->display('studentReport.tpl', 'Student Report');
	}

	function getAllowedReportLocations() {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();
		$location = new Location();
		$location->orderBy('code');
		if (!UserAccount::userHasPermission('View All Student Reports')) {
			//Scope to just locations for the user based on home branch
			$location->locationId = $user->homeLocationId;
		}
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->locationId] = clone $location;
		}
		return $locationList;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#circulation_reports', 'Circulation Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Student Checkout Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'circulation_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View All Student Reports',
			'View Location Student Reports',
		]);
	}
}
