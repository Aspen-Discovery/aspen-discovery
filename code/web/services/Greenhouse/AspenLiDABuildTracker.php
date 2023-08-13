<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenLiDABuild.php';

class Greenhouse_AspenLiDABuildTracker extends Admin_Admin {
	function launch() {
		global $interface;

		$builds = new AspenLiDABuild();

		$showUnsupportedOnly = false;
		if(isset($_REQUEST['showUnsupportedOnly'])) {
			$showUnsupportedOnly = true;
		}
		$interface->assign('showUnsupportedOnly', $showUnsupportedOnly);

		$showSubmittedOnly = false;
		if(isset($_REQUEST['showSubmittedOnly'])) {
			$showSubmittedOnly = true;
		}
		$interface->assign('showSubmittedOnly', $showSubmittedOnly);

		$appToShow = 1;
		if (isset($_REQUEST['appToShow'])) {
			$appToShow = $_REQUEST['appToShow'];
		}
		$interface->assign('appToShow', $appToShow);

		$versionToShow = '';
		if (isset($_REQUEST['versionToShow'])) {
			$versionToShow = $_REQUEST['versionToShow'];
		}
		$interface->assign('versionToShow', $versionToShow);

		$channelToShow = 1;
		$channelToShowOptions = [
			1 => 'All',
			2 => 'Production',
			3 => 'Beta',
			4 => 'Alpha',
			5 => 'Development'
		];
		if (isset($_REQUEST['channelToShow'])) {
			$channelToShow = $_REQUEST['channelToShow'];
		}
		$interface->assign('channelToShowOptions', $channelToShowOptions);
		$interface->assign('channelToShow', $channelToShow);

		$platformToShow = 1;
		$platformToShowOptions = [
			1 => 'All',
			2 => 'iOS',
			3 => 'Android'
		];
		if (isset($_REQUEST['platformToShow'])) {
			$platformToShow = $_REQUEST['platformToShow'];
		}
		$interface->assign('platformToShowOptions', $platformToShowOptions);
		$interface->assign('platformToShow', $platformToShow);

		if ($appToShow != 1) {
			$builds->whereAdd("appId = '$appToShow'");
		}

		if ($platformToShow == 2) {
			$builds->whereAdd("platform = 'ios'");
		} elseif ($platformToShow == 3) {
			$builds->whereAdd("platform = 'android'");
		}

		if ($channelToShow == 2) {
			$builds->whereAdd("channel = 'production'");
		} elseif ($channelToShow == 3) {
			$builds->whereAdd("channel = 'beta'");
		} elseif ($channelToShow == 4) {
			$builds->whereAdd("channel = 'alpha'");
		} elseif ($channelToShow == 5) {
			$builds->whereAdd("channel = 'development'");
		}

		if(!empty($versionToShow)) {
			$builds->whereAdd("version LIKE '$versionToShow%");
		}

		if($showUnsupportedOnly) {
			$builds->whereAdd("isSupported = 0");
		}

		if($showSubmittedOnly) {
			$builds->whereAdd('isSubmitted = 1');
		}

		$builds->orderBy(['name ASC', 'version DESC', 'buildVersion DESC', 'patch DESC']);
		$builds->find();

		$latestRelease = false;

		$appToShowOptions = [
			1 => 'All'
		];
		$allBuilds = [];
		while ($builds->fetch()) {
			$allBuilds[] = $builds->getBuildInformation();
			$appToShowOptions[$builds->appId] = $builds->name;
		}

		$interface->assign('allBuilds', $allBuilds);
		$interface->assign('appToShowOptions', $appToShowOptions);

		function getDownloadExtension($params) {
			return pathinfo($params['url'], PATHINFO_EXTENSION);
		}

		$interface->registerPlugin('function', 'file_ext', 'getDownloadExtension');

		$this->display('aspenLiDABuildTracker.tpl', 'Aspen LiDA Build Tracker', false);
	}

	function canAddNew() {
		return false;
	}

	function canDelete() {
		return false;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/AspenLiDABuildTracker', 'Aspen LiDA Build Tracker');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}
}