<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Administration/BackgroundProcess.php';

class Admin_BackgroundProcesses extends ObjectEditor {

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Background Processes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View System Reports');
	}

	function getObjectType(): string {
		return 'BackgroundProcess';
	}

	function getToolName(): string {
		return 'BackgroundProcesses';
	}

	function getPageTitle(): string {
		return 'Background Processes';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$list = [];

		$object = new BackgroundProcess();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getObjectStructure($context = ''): array {
		return BackgroundProcess::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getDefaultSort(): string {
		return 'startTime desc';
	}

	function canAddNew() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	function canBatchEdit() : bool {
		return false;
	}

	function canBatchDelete() : bool {
		return false;
	}

	function showHistory() : void {}

	function showHistoryLinks() : bool {
		return false;
	}

	function viewIndividualObject($structure) : void {
		//Check to see if the user has a message for this object and if so dismiss it automatically.
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$userMessage = new UserMessage();
			$userMessage->userId = UserAccount::getActiveUserId();
			$userMessage->messageType = 'backgroundProcessCompletion';
			$userMessage->relatedObjectId = $id;
			$userMessage->isDismissed = 0;
			if ($userMessage->find(true)) {
				$userMessage->isDismissed = 1;
				$userMessage->update();
			}
		}

		parent::viewIndividualObject($structure);
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter) : void {
		if ($fieldName == 'owningUser') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'background_processes',
				'sourceField' => 'owningUserId',
				'targetClass' => 'User',
				'targetField' => 'id',
				'getCompareValueMethod' => 'getDisplayName',
				'compareFormat' => 'nameWithBarcode',
			]);
		} else {
			parent::applyFilter($object, $fieldName, $filter);
		}
	}
}
