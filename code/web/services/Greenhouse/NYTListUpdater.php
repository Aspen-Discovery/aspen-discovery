<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';

class Greenhouse_NYTListUpdater extends Admin_Admin
{
	function launch(): void
	{
		global $interface;

		// Check if the NYT API is configured
		$nytSettings = new NewYorkTimesSetting();
		$hasSettings = $nytSettings->find(true);
		$interface->assign('hasSettings', $hasSettings);

		if ($hasSettings) {
			$interface->assign('apiKey', substr($nytSettings->booksApiKey, 0, 4) . '...');
			$interface->assign('forceFullUpdate', $nytSettings->runFullUpdate);
			$interface->assign('enableExtensiveLogging', $nytSettings->enableExtensiveLogging);
		}

		// Get the site's name
		$siteUrl = $_SERVER['SERVER_NAME'];
		$interface->assign('siteUrl', $siteUrl);

		$this->display('nytListUpdater.tpl', 'New York Times List Updater');
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'New York Times List Updater');
		return $breadcrumbs;
	}

	function canView(): bool
	{
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}