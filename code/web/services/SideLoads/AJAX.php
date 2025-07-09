<?php
require_once ROOT_DIR . '/JSON_Action.php';

class SideLoads_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	public function deleteMarc() {
		if (UserAccount::userHasPermission(['Administer All Side Loads', 'Administer Side Loads for Home Library'])) {
			$id = $_REQUEST['id'];
			$sideLoadConfiguration = new SideLoad();
			$sideLoadConfiguration->id = $id;
			if ($sideLoadConfiguration->find(true) && !empty($sideLoadConfiguration->marcPath)) {
				if (!UserAccount::userHasPermission(['Administer All Side Loads'])) {
					$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
					$libraryId = $library == null ? -1 : $library->libraryId;
					if (($sideLoadConfiguration->owningLibrary != -1 && $sideLoadConfiguration->owningLibrary != $libraryId) || ($sideLoadConfiguration->owningLibrary == -1 && $sideLoadConfiguration->sharing != 1)) {
						return [
							'success' => false,
							'message' => 'You do not have permissions to perform this action.',
						];
					}
				}
				$marcPath = $sideLoadConfiguration->marcPath;
				$file = $_REQUEST['file'];
				$fullName = $marcPath . DIRECTORY_SEPARATOR . $file;
				if (file_exists($fullName)) {
					if (unlink($fullName)) {
						$sideLoadConfiguration->runFullUpdate = true;
						$sideLoadConfiguration->update();
						return [
							'success' => true,
							'message' => 'The file was deleted.',
						];
					} else {
						return [
							'success' => false,
							'message' => 'The file could not be deleted.',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Could not find the file to download.',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Could not find the Side Load for this file.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'You do not have permissions to perform this action.',
			];
		}
	}

	public function exportUsageData() {
		require_once ROOT_DIR . '/services/SideLoads/UsageGraphs.php';
		$aspenUsageGraph = new SideLoads_UsageGraphs();
		$aspenUsageGraph->buildCSV('SideLoads');
	}
}