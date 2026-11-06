<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Hoopla_AspenData extends Admin_Admin {
	function launch() : void {
		global $interface;
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$hooplaVersion2 = ($systemVariables !== false && !empty($systemVariables->hooplaVersion) && (int)$systemVariables->hooplaVersion == 2);
		$interface->assign('hooplaVersion2', $hooplaVersion2);

		$canForceReindex = $hooplaVersion2 && UserAccount::userHasPermission('Force Reindexing of Records');
		$interface->assign('canForceReindex', $canForceReindex);
		if ($canForceReindex && !empty($_POST['recordsToReindex'])) {
			$this->queueRecordsToReindex($_POST['recordsToReindex']);
		}

		$this->display('hooplaAspenData.tpl', 'Hoopla Aspen Information');
	}
	 
	private function queueRecordsToReindex(string $recordsToReindex) : void {
		global $interface;
		require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';

		$hooplaIds = preg_split('/[\s,]+/', $recordsToReindex, -1, PREG_SPLIT_NO_EMPTY);
		$hooplaIds = array_unique(array_map('trim', $hooplaIds));

		$invalidIds = [];
		$alreadyInQueueIds = [];
		$numQueued = 0;

		foreach ($hooplaIds as $hooplaId) {
			if (!ctype_digit($hooplaId)) {
				$invalidIds[] = $hooplaId;
				continue;
			}
			$recordToReload = new RecordIdentifiersToReload();
			$recordToReload->type = 'hoopla';
			$recordToReload->identifier = $hooplaId;
			$recordToReload->processed = 0;
			if ($recordToReload->count() > 0) {
				$alreadyInQueueIds[] = $hooplaId;
				continue;
			}
			$recordToReload->insert();
			$numQueued++;
		}
		$interface->assign('invalidIds', $invalidIds);
		$interface->assign('alreadyInQueueIds', $alreadyInQueueIds);
		$interface->assign('numQueued', $numQueued);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/AspenData', 'Aspen Information');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'hoopla';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Force Reindexing of Records');
	}
}