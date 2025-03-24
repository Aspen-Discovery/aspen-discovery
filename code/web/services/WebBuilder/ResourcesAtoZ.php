<?php

class WebBuilder_ResourcesAtoZ extends Action
{
	function launch() : void
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

		//For display of web resources, we can use the active library rather than the patron's home library.
		//The resources may get restricted later if they need to log in or be in the library, but that is handled later.
		global $library;

		$webResourceSetting = new WebResourcesSetting();
		$webResourceSetting->id = $library->webResourcesSettingId;

		//get valid starting letters
		$startingLetters = new WebResource();
		$startingLetters->selectAdd();
		$startingLetters->selectAdd("DISTINCT LEFT(name, 1) AS first_letter");
		$startingLetterLibrary = new LibraryWebResource();
		$startingLetterLibrary->libraryId = $library->libraryId;
		$startingLetters->joinAdd($startingLetterLibrary, 'INNER', 'resourceLibrary', 'id', 'webResourceId');
		$startingLetters->orderBy('first_letter');
		$startingLetters->find();
		$validLetters = [];
		while ($startingLetters->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			if (is_numeric($startingLetters->first_letter)){
				$validLetters[] = "num";
			} else {
				/** @noinspection PhpUndefinedFieldInspection */
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
		$resourcesForAtoZ->joinAdd($startingLetterLibrary, 'INNER', 'resourceLibrary', 'id', 'webResourceId');
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
		$this->display('resourcesAtoZ.tpl', 'Resources A-Z', '', false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Home', 'Home');
		return $breadcrumbs;
	}
}
