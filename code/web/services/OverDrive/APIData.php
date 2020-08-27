<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class OverDrive_APIData extends Admin_Admin
{
	function launch()
	{
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$driver = new OverDriveDriver();

		$libraryInfo = $driver->getLibraryAccountInformation();
		$contents = "<h1>Main - {$libraryInfo->name}</h1>";
		$contents .= $this->easy_printr('Library Account Information', 'libraryAccountInfo', $libraryInfo);

		$advantageAccounts = null;
		try {
			$advantageAccounts = $driver->getAdvantageAccountInformation();
			if ($advantageAccounts && !empty($advantageAccounts->advantageAccounts)) {
				$contents .= "<h1>Advantage Accounts</h1>";
				$contents .= $this->easy_printr('Advantage Account Information', 'advantageAccountInfo', $advantageAccounts);
				$contents .= "<br/>";
				foreach ($advantageAccounts->advantageAccounts as $accountInfo) {
					$contents .= $accountInfo->name . ' - ' . $accountInfo->collectionToken . '<br/>';
				}
			} else {
				$contents .= "<div>No advantage accounts for this collection</div>";
			}
		} catch (Exception $e) {
			$contents .= 'Error retrieving Advantage Info';
		}

		$productKey = $libraryInfo->collectionToken;

		if (!empty($_REQUEST['id'])) {
			$overDriveId = $_REQUEST['id'];
			$contents .= "<h2>Metadata</h2>";
			$contents .= "<h3>Metadata for $overDriveId</h3>";
			$metadata = $driver->getProductMetadata($overDriveId, $productKey);
			if ($metadata) {
				$contents .= $this->easy_printr("Metadata for $overDriveId in shared collection", "metadata_{$overDriveId}_{$productKey}", $metadata);
			} else {
				$contents .= ("No metadata<br/>");
			}

			$contents .= "<h2>Availability</h2>";
			$contents .= ("<h3>Availability - Main collection: {$libraryInfo->name}</h3>");
			$availability = $driver->getProductAvailability($overDriveId, $productKey);
			if ($availability && !isset($availability->errorCode)) {
				$contents .= ("Copies Owned: {$availability->copiesOwned} <br/>");
				$contents .= ("Available Copies: {$availability->copiesAvailable }<br/>");
				$contents .= ("Num Holds (entire collection): {$availability->numberOfHolds }<br/>");
				$contents .= $this->easy_printr("Availability response", "availability_{$overDriveId}_{$productKey}", $availability);
			} else {
				$contents .= ("Not owned<br/>");
				if ($availability) {
					$contents .= $this->easy_printr("Availability response", "availability_{$overDriveId}_{$productKey}", $availability);
				}
			}

			if ($advantageAccounts) {
				foreach ($advantageAccounts->advantageAccounts as $accountInfo) {
					$contents .= ("<h3>Availability - {$accountInfo->name}</h3>");
					$availability = $driver->getProductAvailability($overDriveId, $accountInfo->collectionToken);
					if ($availability && !isset($availability->errorCode)) {
						$contents .= ("Copies Owned (Shared Plus advantage): {$availability->copiesOwned }<br/>");
						$contents .= ("Available Copies (Shared Plus advantage): {$availability->copiesAvailable }<br/>");
						$contents .= ("Num Holds (Shared Plus advantage): {$availability->numberOfHolds }<br/>");
						$contents .= $this->easy_printr("Availability response", "availability_{$overDriveId}_{$accountInfo->collectionToken}", $availability);
					} else {
						$contents .= ("Not owned<br/>");
						if ($availability) {
							$contents .= $this->easy_printr("Availability response", "availability_{$overDriveId}_{$accountInfo->collectionToken}", $availability);
						}
					}
				}
			}
		}

		global $interface;
		$interface->assign('overDriveAPIData', $contents);
		$this->display('overdriveApiData.tpl', 'OverDrive API Data');
	}

	function easy_printr($title, $section, &$var)
	{
		$contents = "<a onclick='$(\"#{$section}\").toggle();return false;' href='#'>{$title}</a>";
		$contents .= "<pre style='display:none' id='{$section}'>";
		$contents .= print_r($var, true);
		$contents .= '</pre>';
		return $contents;
	}

	function getAllowableRoles()
	{
		return array('opacAdmin', 'cataloging', 'superCataloger');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', 'OverDrive');
		$breadcrumbs[] = new Breadcrumb('/OverDrive/APIData', 'API Information');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'overdrive';
	}
}