<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/API/GreenhouseAPI.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class Greenhouse_AspenLiDASiteListingCache extends Admin_Admin {
	function launch() {
		global $interface;

		$lastUpdated = '';
		$aspenSite = new AspenSiteCache();
		if($aspenSite->find(true)) {
			$lastUpdated = date('Y-m-d\TH:i:s\Z', $aspenSite->lastUpdated);
		}

		$cache = '';
		$curlWrapper = new CurlWrapper();
		$curlWrapper->setTimeout(120);
		$curlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
		$curlWrapper->setOption(CURLOPT_SSL_VERIFYPEER, false);
		$curlWrapper->setOption(CURLOPT_SSL_VERIFYHOST, false);
		$curlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			$url = $systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=getLibraries';
			if (!empty($_REQUEST['refreshData'])) {
				$url .= '&reload=true';
			}

			$cache = json_decode($curlWrapper->curlGetPage($url), true);
			$curlWrapper->close_curl();
		}
		$contents = $this->easy_printr("cache", $cache);

		$interface->assign('siteListingCache', $contents);
		$interface->assign('lastUpdatedCache', $lastUpdated);
		$this->display('aspenLiDASiteListingCache.tpl', 'Aspen LiDA Site Listing Cache');
	}

	function easy_printr($section, &$var) {
		$contents = "<pre id='{$section}'>";
		$formattedContents = print_r($var, true);
		if ($formattedContents !== false) {
			$contents .= $formattedContents;
		}
		$contents .= '</pre>';
		return $contents;
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Greenhouse/greenhouse-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/AspenLiDASiteListingCache', 'Aspen LiDA Site Listing Cache');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}
}