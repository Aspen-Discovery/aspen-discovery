<?php

require_once ROOT_DIR . '/services/AspenLiDA/BrandedAppSettings.php';

class PrivacyPolicy_App extends Action {
	function launch() {
		global $interface;
		global $library;

		$appName = 'Aspen LiDA';
		$appOwner = 'ByWater Solutions, LLC';
		$appSettings = new BrandedAppSetting();
		if($appSettings->find(true)) {
			if($appSettings->appName) {
				$appName = $appSettings->appName;
			}
		}

		if($appName !== 'Aspen LiDA') {
			$appOwner = $library->displayName;
		}

		$address = '';
		$tel = '';
		$email = '';
		$location = new Location();
		$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
		$location->libraryId = $library->libraryId;
		if ($location->find(true)) {
			$address = preg_replace('/\r\n|\r|\n/', '<br>', $location->address);
			$tel = $location->phone;
			$email = $location->contactEmail;
		}

		$interface->assign('appName', $appName);
		$interface->assign('appOwner', $appOwner);
		$interface->assign('address', $address);
		$interface->assign('tel', $tel);
		$interface->assign('email', $email);
		$this->display('app.tpl', $appName . ' Privacy Policy', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Privacy Policy');
		return $breadcrumbs;
	}
}