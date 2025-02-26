<?php

class WebBuilder_ResourcesAtoZ extends Action
{
	function launch()
	{
		global $interface;
		global $activeLanguage;
		global $activeLibrary;

		$filter = '';
		if (isset($_REQUEST['startsWith']) && ctype_alpha($_REQUEST['startsWith']) && (strlen($_REQUEST['startsWith']) == 1 || $_REQUEST['startsWith'] == "num")) {
			$filter = $_REQUEST['startsWith'];
		}

		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
		require_once ROOT_DIR . '/sys/WebBuilder/WebResourcesSetting.php';

		if ($activeLibrary == null && UserAccount::isLoggedIn()) {
			$activeLibrary = UserAccount::getLoggedInUser()->getHomeLibrary();
		}elseif ($activeLibrary == null) {
			global $library;
			$activeLibrary = $library;
		}

		$webResourceSetting = new WebResourcesSetting();
		$webResourceSetting->webResourceSettingId = $activeLibrary->webResourceSettingId;
		$setting = $webResourceSetting->find(true);

		//get valid starting letters
		$startingLetters = new WebResource();
		$startingLetters->selectAdd();
		$startingLetters->selectAdd("DISTINCT LEFT(name, 1) AS first_letter");
		$startingLetters->orderBy('first_letter');
		$startingLetters->find();
		$validLetters = [];
		while ($startingLetters->fetch()) {
			if (is_numeric($startingLetters->first_letter)){
				$validLetters[] = "num";
			} else {
				$validLetters[] = $startingLetters->first_letter;
			}
		}

		//get web resources to show based off current filter
		$resourcesForAtoZ = new WebResource();
		if (!empty($filter)) {
			if ($filter == "num") {
				$filter = "^[0-9]";
				$escapedFilter = $resourcesForAtoZ->escape($filter);
				$resourcesForAtoZ->whereAdd("name regexp $escapedFilter");
			} else {
				$escapedFilter = $resourcesForAtoZ->escape($filter . '%');
				$resourcesForAtoZ->whereAdd("name LIKE $escapedFilter");
			}
		}
		$resourcesForAtoZ->orderBy('name');
		$resourcesForAtoZ->find();
		$webResources = [];
		$webResourceIds = [];

		while ($resourcesForAtoZ->fetch()) {
			if (!in_array("WebResource:" . $resourcesForAtoZ->id, $webResourceIds)) {
				$webResourceIds[] = "WebResource:" . $resourcesForAtoZ->id;
			}
		}

		foreach ($webResourceIds as $curResource) {
			$webResourceRecordDriver = new WebResourceRecordDriver($curResource);

			if ($webResourceRecordDriver->isValid()) {
				$webResources[$webResourceRecordDriver->getId()] = [
					'id' => $webResourceRecordDriver->getId(),
					'title' => $webResourceRecordDriver->getTitle(),
					'description' => $webResourceRecordDriver->getDescription(),
					'link' => $webResourceRecordDriver->getLinkUrl(),
					'bookCoverUrl' => $webResourceRecordDriver->getBookCoverUrl('medium'),
				];
			}
		}

		$filterArray = range('A', 'Z');
		$filterArray = ['num', ...$filterArray];

		$interface->assign('validLetters', $validLetters);
		$interface->assign('filterArray', $filterArray);
		$interface->assign('webResources', $webResources);
		$interface->assign('description', $webResourceSetting->getTextBlockTranslation('descriptionAtoZ', $activeLanguage->code));
		$this->display('resourcesAtoZ.tpl', '', '', false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Home', 'Home');
		return $breadcrumbs;
	}
}
