<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectScope.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitleAvailability.php';

class PalaceProject_CollectionReport extends Admin_Admin {
	function launch() {
		global $interface;
		$palaceProjectScope = new PalaceProjectScope();
		$palaceProjectScopes = $palaceProjectScope->fetchAll(null, null, false, true);
		$palaceProjectSetting = new PalaceProjectSetting();
		$palaceProjectSettings = $palaceProjectSetting->fetchAll(null, null, false, true);

		//Create a report of the number of active titles by library and collection within the library
		$library = new Library();
		$library->whereAdd('palaceProjectScopeId > 0');
		$library->orderBy('displayName');
		$library->find();
		$allLibraries = [];
		while ($library->fetch()) {
			$libraryInfo = [
				'libraryId' => $library->libraryId,
				'displayName' => $library->displayName,
				'palaceProjectScopeId' => $library->palaceProjectScopeId
			];
			$activeScope = $palaceProjectScopes[$library->palaceProjectScopeId];
			$activeSetting = $palaceProjectSettings[$activeScope->settingId];
			$allCollectionObjects = $activeSetting->collections;
			$allCollections = [];
			foreach ($allCollectionObjects as $collectionObject) {
				$titleAvailability = new PalaceProjectTitleAvailability();
				$titleAvailability->collectionId = $collectionObject->id;
				$titleAvailability->deleted = 0;
				$numTitles = $titleAvailability->count();
				$titleAvailability->deleted = 1;
				$numDeletedTitles = $titleAvailability->count();
				$allCollections[] = [
					'palaceProjectName' => $collectionObject->palaceProjectName,
					'displayName' => $collectionObject->displayName,
					'numTitles' => $numTitles,
					'numDeletedTitles' => $numDeletedTitles
				];
			}
			$libraryInfo['collections'] = $allCollections;
			$allLibraries[] = $libraryInfo;
		}
		$interface->assign('allLibraries', $allLibraries);

		$this->display('collectionReport.tpl', 'Collection Report');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#palace_project', 'Palace Project');
		$breadcrumbs[] = new Breadcrumb('/PalaceProject/CollectionReport', 'Collection Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'palace_project';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Palace Project', 'View System Reports']);
	}
}