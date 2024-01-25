<?php

require_once ROOT_DIR . '/services/AspenLiDA/BrandedAppSettings.php';

class PrivacyPolicy_App extends Action {
	function launch() {
		global $interface;
		global $library;

		$appName = 'Aspen LiDA';
		$address = '';
		$tel = '';
		$email = '';
		$appSettings = new BrandedAppSetting();
		if($appSettings->find(true)) {
			if($appSettings->appName) {
				$appName = $appSettings->appName;
				$address = preg_replace('/\r\n|\r|\n/', '<br>', $appSettings->privacyPolicyContactAddress);
				$tel = $appSettings->privacyPolicyContactPhone;
				$email = $appSettings->privacyPolicyContactEmail;
			}
		}

		$appOwner = 'ByWater Solutions, LLC';
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables) && !empty($systemVariables->supportingCompany)) {
			$appOwner = $systemVariables->supportingCompany;
		}

		if($appName !== 'Aspen LiDA') {
			$appOwner = $library->displayName;
		}

		$location = new Location();
		$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
		$location->libraryId = $library->libraryId;
		if ($location->find(true)) {
			if($address == '' && !is_null($address)) {
				$address = preg_replace('/\r\n|\r|\n/', '<br>', $location->address);
			}
			if($tel == '' && !is_null($tel)) {
				$tel = $location->phone;
			}

			if($email == '' && !is_null($email)) {
				$email = $location->contactEmail;
			}
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