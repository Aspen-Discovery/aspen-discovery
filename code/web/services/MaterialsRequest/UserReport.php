<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');

class MaterialsRequest_UserReport extends Admin_Admin {

	function launch() {
		global $configArray;
		global $interface;
		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;

		$materialsRequestStatus->find();
		$availableStatuses = [];
		$defaultStatusesToShow = [];
		while ($materialsRequestStatus->fetch()) {
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			if ($materialsRequestStatus->isOpen == 1 || $materialsRequestStatus->isDefault == 1) {
				$defaultStatusesToShow[] = $materialsRequestStatus->id;
			}
		}
		$interface->assign('availableStatuses', $availableStatuses);

		if (isset($_REQUEST['statusFilter'])) {
			$statusesToShow = $_REQUEST['statusFilter'];
		} else {
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);

		//Get a list of users that have requests open
		$materialsRequest = new MaterialsRequest();
		$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy', 'id');
		$materialsRequest->joinAdd(new MaterialsRequestStatus(), 'INNER', 'status', 'status', 'id');
		$materialsRequest->selectAdd();
		$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests');
		$materialsRequest->selectAdd('user.id as userId, createdBy, status, description as description');
		if (UserAccount::userHasPermission('View Materials Requests Reports')) {
			//Need to limit to only requests submitted for the user's home location
			$locations = new Location();
			$locations->libraryId = $homeLibrary->libraryId;
			$locations->find();
			$locationsForLibrary = [];
			while ($locations->fetch()) {
				$locationsForLibrary[] = $locations->locationId;
			}

			$materialsRequest->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
		}
		$statusSql = "";
		foreach ($statusesToShow as $status) {
			if (strlen($statusSql) > 0) {
				$statusSql .= ",";
			}
			$statusSql .= $materialsRequest->escape($status);
		}
		$materialsRequest->whereAdd("status in ($statusSql)");
		$materialsRequest->groupBy('createdBy, status');
		$materialsRequest->find();

		$userData = [];
		while ($materialsRequest->fetch()) {
			if (!array_key_exists($materialsRequest->createdBy, $userData)) {
				$userData[$materialsRequest->createdBy] = [];
				$userData[$materialsRequest->createdBy]['firstName'] = $materialsRequest->getCreatedByFirstName();
				$userData[$materialsRequest->createdBy]['lastName'] = $materialsRequest->getCreatedByLastName();
				$userData[$materialsRequest->createdBy]['barcode'] = $materialsRequest->getCreatedByUserBarcode();
				$userData[$materialsRequest->createdBy]['totalRequests'] = 0;
				$userData[$materialsRequest->createdBy]['requestsByStatus'] = [];
			}
			$userData[$materialsRequest->createdBy]['requestsByStatus'][$materialsRequest->description] = $materialsRequest->numRequests;
			$userData[$materialsRequest->createdBy]['totalRequests'] += $materialsRequest->numRequests;
		}
		$interface->assign('userData', $userData);

		//Get a list of all of the statuses that will be shown
		$statuses = [];
		foreach ($userData as $userInfo) {
			foreach ($userInfo['requestsByStatus'] as $status => $numRequests) {
				$statuses[$status] = translate([
					'text' => $status,
					'isAdminFacing' => true,
				]);
			}
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($userData, $statuses);
		}

		$this->display('userReport.tpl', 'Materials Request User Report');
	}

	function exportToExcel($userData, $statuses) {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="MaterialsRequestUserReport.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');

		$header = ['Last Name', 'First Name', 'Barcode'];
		foreach ($statuses as $statusLabel) {
			$header[] = $statusLabel;
		}
		$header[] = 'Total';
		fputcsv($fp, $header);

		//Loop Through The Report Data
		foreach ($userData as $userInfo) {
			$lastName = $userInfo['lastName'];
			$firstName = $userInfo['firstName'];
			$barcode = $userInfo['barcode'];
			$row = [$lastName, $firstName, $barcode];

			foreach ($statuses as $status => $statusLabel) {
				$stat = $userInfo['requestsByStatus'][$status] ?? 0;
				$row[] = $stat;
			}

			$total = $userInfo['totalRequests'];
			$row[] = $total;

			fputcsv($fp, $row);
		}
		exit;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'User Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View Materials Requests Reports');
	}
}
