<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class MapBiblioNumbers extends Admin_Admin {
	function launch() {
		global $interface;

		if (isset($_REQUEST['submit'])) {
			$results = $this->doBiblioMapping();
			$interface->assign('mappingResults', $results);
		}

		$this->display('mapBiblioNumbers.tpl', 'Map Biblio Numbers', false);
	}

	function doBiblioMapping(): array {
		set_time_limit(0);
		ini_set('memory_limit', '4G');
		$result = [
			'success' => false,
			'message' => 'Unknown error mapping biblios',
		];
		if (isset($_FILES['mappingFile'])) {
			$mappingFile = $_FILES['mappingFile'];
			if (isset($mappingFile["error"]) && $mappingFile["error"] == 4) {
				$result['message'] = translate([
					'text' => "No Mapping file was uploaded",
					'isAdminFacing' => true,
				]);
			} elseif (isset($mappingFile["error"]) && $mappingFile["error"] > 0) {
				$result['message'] = translate([
					'text' => "Error in file upload for mapping file %1%",
					1 => $mappingFile["error"],
					"isAdminFacing" => true,
				]);
			} else {
				$mappingFileHnd = fopen($mappingFile['tmp_name'], 'r');
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$numMapped = 0;
				while ($mapping = fgetcsv($mappingFileHnd)) {
					if (count($mapping) >= 2) {
						$readingHistory = new ReadingHistoryEntry();
						$readingHistory->sourceId = $mapping[0];
						/** @var ReadingHistoryEntry[] $readingHistories */
						$readingHistories = $readingHistory->fetchAll();
						foreach ($readingHistories as $readingHistoryEntry) {
							$readingHistoryEntry->sourceId = $mapping[1];
							$readingHistoryEntry->update();
							$numMapped++;
						}
						$readingHistory->__destruct();
						$readingHistory = null;
					}
				}
				fclose($mappingFileHnd);
				unlink($mappingFile['tmp_name']);
				$result['success'] = true;
				$result['message'] = "Mapped $numMapped reading history entries.";
			}
		}
		return $result;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Map Biblio Numbers');

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