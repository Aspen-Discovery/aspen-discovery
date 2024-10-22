<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_SymphonyApiTester extends Admin_Admin {
	function launch() {
		global $interface;

		if (!empty($_REQUEST['pathToDescribe'])) {
			$interface->assign('pathToDescribe', $_REQUEST['pathToDescribe']);
			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
			if ($catalogConnection->driver instanceof SirsiDynixROA) {
				$symphonyDriver = $catalogConnection->driver;
				$describeResults = $symphonyDriver->describePath($_REQUEST['pathToDescribe']);
				if (is_null($describeResults)) {
					$describeResults = "Path not found";
				}
				$interface->assign('describeResults', $describeResults);
			} else{
				$interface->assign('describeResults', 'Could not describe path, this instance is not connected to Symphony.');
			}
		}else if (!empty($_REQUEST['getRequest'])) {
			$interface->assign('getRequest', $_REQUEST['getRequest']);
			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
			if ($catalogConnection->driver instanceof SirsiDynixROA) {
				$symphonyDriver = $catalogConnection->driver;
				$getRequestResults = $symphonyDriver->getRequest($_REQUEST['getRequest']);
				if (is_null($getRequestResults)) {
					$getRequestResults = "Request failed";
				}
				$interface->assign('getRequestResults', $getRequestResults);
			} else{
				$interface->assign('getRequestResults', 'Could get request, this instance is not connected to Symphony.');
			}
		}

		$this->display('symphonyApiTester.tpl', 'Symphony API Tester');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && !empty(UserAccount::getActivePermissions())) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Symphony API Tester');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Account Profiles');
	}
}