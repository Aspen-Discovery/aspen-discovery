<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Development/AspenRelease.php';

class Greenhouse_UpdateCenter extends Admin_Admin {

	function launch() {
		global $interface;

		$implementationStatuses = AspenSite::$_implementationStatuses;
		$interface->assign('implementationStatuses', $implementationStatuses);
		$implementationStatusToShow = '3';
		if (isset($_REQUEST['implementationStatusToShow'])) {
			$implementationStatusToShow = $_REQUEST['implementationStatusToShow'];
		}
		$interface->assign('implementationStatusToShow', $implementationStatusToShow);

		$siteTypes = AspenSite::$_siteTypes;
		$interface->assign('siteTypes', $siteTypes);
		$siteTypeToShow = '1';
		if (isset($_REQUEST['siteTypeToShow'])) {
			$siteTypeToShow = $_REQUEST['siteTypeToShow'];
		}
		$interface->assign('siteTypeToShow', $siteTypeToShow);

		$releases = AspenRelease::getReleasesList();
		$interface->assign('releases', $releases);
		$releaseToShow = 'any';
		if (isset($_REQUEST['releaseToShow'])) {
			$releaseToShow = $_REQUEST['releaseToShow'];
		}
		$interface->assign('releaseToShow', $releaseToShow);

		$timezones = AspenSite::$_timezones;
		$interface->assign('timezones', $timezones);
		$timezoneToShow = 'any';
		if (isset($_REQUEST['timezoneToShow'])) {
			$timezoneToShow = $_REQUEST['timezoneToShow'];
		}
		$interface->assign('timezoneToShow', $timezoneToShow);

		$sites = new AspenSite();
		if($implementationStatusToShow !== 'any') {
			$sites->whereAdd('implementationStatus = ' . $implementationStatusToShow);
		} else {
			$sites->whereAdd('implementationStatus != 4');
		}
		if($siteTypeToShow !== 'any') {
			$sites->whereAdd('siteType = ' . $siteTypeToShow);
		}
		if($releaseToShow !== 'any') {
			$escapedRelease = $sites->escape('%' . $releaseToShow . '%');
			$sites->whereAdd('version LIKE ' . $escapedRelease);
		}
		if($timezoneToShow !== 'any') {
			$sites->whereAdd('timezone = ' . $timezoneToShow);
		}
		$sites->orderBy('implementationStatus ASC, timezone, name ASC');
		$sites->find();
		$allSites = [];
		while ($sites->fetch()) {
			$allSites[] = clone $sites;
		}
		$interface->assign('allSites', $allSites);

		$this->display('updateCenter.tpl', 'Aspen Update Center', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Update Center');
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