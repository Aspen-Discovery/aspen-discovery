<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');

class Admin_CollectionReports extends Admin_Admin {
	function launch() : void {
		global $enabledModules;
		global $interface;
		$user = UserAccount::getLoggedInUser();
		$tableData = [];

		//Get Source Table Data
		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsData = new IlsRecord();
		$ilsData->whereAdd('source = "ils"');
		$ilsData->selectAdd();
		$ilsData->selectAdd('SUM(deleted = 0 AND suppressedNoMarcAvailable = 0) AS activeCount');
		$ilsData->selectAdd('SUM(deleted) AS deletedCount');
		$ilsData->selectAdd('SUM(suppressedNoMarcAvailable) AS suppressedCount');
		if ($ilsData->find(true)) {
			$tableData['ilsData']['rowName'] = "ILS";
			$tableData['ilsData']['activeCount'] = $ilsData->__get('activeCount');
			$tableData['ilsData']['deletedCount'] = $ilsData->__get('deletedCount');
			$tableData['ilsData']['suppressedCount'] = $ilsData->__get('suppressedCount');
		}

		if (array_key_exists('Side Loads', $enabledModules)) {
			global $sideLoadSettings;
			foreach ($sideLoadSettings as $sideLoadSetting) {
				$sideloadData = new IlsRecord();
				$sideloadData->whereAdd("source = '$sideLoadSetting->name'");
				$sideloadData->selectAdd();
				$sideloadData->selectAdd('SUM(deleted = 0 AND suppressedNoMarcAvailable = 0) AS activeCount');
				$sideloadData->selectAdd('SUM(deleted) AS deletedCount');
				$sideloadData->selectAdd('SUM(suppressedNoMarcAvailable) AS suppressedCount');
				if ($sideloadData->find(true)) {
					$tableData['sideloadData' . $sideLoadSetting->id]['rowName'] = $sideLoadSetting->name;
					$tableData['sideloadData' . $sideLoadSetting->id]['activeCount'] = $sideloadData->__get('activeCount');
					$tableData['sideloadData' . $sideLoadSetting->id]['deletedCount'] = $sideloadData->__get('deletedCount');
					$tableData['sideloadData' . $sideLoadSetting->id]['suppressedCount'] = $sideloadData->__get('suppressedCount');
				}
			}
		}
		if (array_key_exists('CloudLibrary', $enabledModules)) {
			require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
			$cloudLibraryData = new CloudLibraryProduct();
			$cloudLibraryData->selectAdd();
			$cloudLibraryData->selectAdd('SUM(deleted = 0) AS activeCount');
			$cloudLibraryData->selectAdd('SUM(deleted) AS deletedCount');
			if ($cloudLibraryData->find(true)) {
				$tableData['cloudLibraryData']['rowName'] = "CloudLibrary";
				$tableData['cloudLibraryData']['activeCount'] = $cloudLibraryData->__get('activeCount');
				$tableData['cloudLibraryData']['deletedCount'] = $cloudLibraryData->__get('deletedCount');
			}
		}
		if (array_key_exists('Hoopla', $enabledModules)) {
			require_once ROOT_DIR . '/sys/Hoopla/HooplaExtract.php';
			$hooplaData = new HooplaExtract();
			$hooplaData->selectAdd();
			$hooplaData->selectAdd('COUNT(*) AS activeCount');
			if ($hooplaData->find(true, false, true)) {
				$tableData['hooplaData']['rowName'] = "Hoopla";
				$tableData['hooplaData']['activeCount'] = $hooplaData->__get('activeCount');
			}
		}
		if (array_key_exists('OverDrive', $enabledModules)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$overdriveData = new OverDriveAPIProduct();
			$overdriveData->selectAdd();
			$overdriveData->selectAdd('SUM(deleted = 0) AS activeCount');
			$overdriveData->selectAdd('SUM(deleted) AS deletedCount');
			if ($overdriveData->find(true)) {
				$tableData['overDriveData']['rowName'] = "OverDrive";
				$tableData['overDriveData']['activeCount'] = $overdriveData->__get('activeCount');
				$tableData['overDriveData']['deletedCount'] = $overdriveData->__get('deletedCount');
			}
		}
		if (array_key_exists('Palace Project', $enabledModules)) {
			require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
			$palaceData = new PalaceProjectTitle();
			$palaceData->selectAdd();
			$palaceData->selectAdd('COUNT(id) AS activeCount');
			if ($palaceData->find(true)) {
				$tableData['palaceProjectData']['rowName'] = "Palace Project";
				$tableData['palaceProjectData']['activeCount'] = $palaceData->__get('activeCount');
			}
		}
		uasort($tableData, function ($a, $b) {return strcasecmp( $a['rowName'], $b['rowName']);});

		//Get Format Table Data
		global $aspen_db;
		$query = $aspen_db->query("SELECT format, source, count(*) AS numRecords FROM grouped_work_records 
				INNER JOIN indexed_record_source on sourceId = indexed_record_source.id 
				INNER JOIN indexed_format on formatId = indexed_format.id 
				GROUP BY formatId, format, sourceId, source ORDER BY lower(format), lower(source);", PDO::FETCH_ASSOC);
		$formatTableData = $query->fetchAll();


		$interface->assign('tableData', $tableData);
		$interface->assign('formatTableData', $formatTableData);
		$this->display('collectionReports.tpl', 'Collection Report');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'Collection Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Collection Reports');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
		]);
	}
}
