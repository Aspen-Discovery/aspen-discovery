<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class ILS_SelfCheckTester extends Admin_Admin {
	function launch() : void {
		global $interface;
		if (isset($_REQUEST['submitCheckout']) || isset($_REQUEST['submitCheckin'])) {
			require_once ROOT_DIR . "/services/API/UserAPI.php";
			$userAPI = new UserAPI('internal');
			//Pass the active location or the main location for the active library if there is no active location
			/** @var Location $locationSingleton */
			global $locationSingleton;
			global $library;
			$location = $locationSingleton->getActiveLocation();
			if (empty($location)) {
				$location = $library->getMainLocation();
			}
			if ($location != null) {
				$locationId = $location->locationId;
			}else{
				$locationId = null;
			}
			if (isset($_REQUEST['submitCheckout'])){
				$checkoutResult = $userAPI->checkoutILSItem($_REQUEST['patronBarcode'], $_REQUEST['patronPassword'], $_REQUEST['itemBarcode'], $locationId);
				$interface->assign('checkoutResult', $checkoutResult);
			}else{
				$checkinResult = $userAPI->checkinILSItem($_REQUEST['patronBarcode'], $_REQUEST['patronPassword'], $_REQUEST['itemBarcode'], $locationId);
				$interface->assign('checkinResult', $checkinResult);
			}
		}

		//Redisplay barcodes for testing
		if (!empty($_REQUEST['patronBarcode'])) {
			$interface->assign('patronBarcode', strip_tags($_REQUEST['patronBarcode']));
		}
		if (!empty($_REQUEST['itemBarcode'])) {
			$interface->assign('itemBarcode', strip_tags($_REQUEST['itemBarcode']));
		}

		$this->display('selfCheckTester.tpl', 'Self Check Tester');
	}
	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/SelfCheckTester', 'Self Check Tester');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Test Self Check');
	}
}