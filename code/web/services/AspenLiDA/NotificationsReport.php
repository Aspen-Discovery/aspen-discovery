<?php

require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class AspenLiDA_NotificationsReport extends ObjectEditor {
	function getObjectType(): string {
		return 'UserNotification';
	}

	function getToolName(): string {
		return 'NotificationsReport';
	}

	function getPageTitle(): string {
		return 'Notifications Report';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new UserNotification();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id desc';
	}

	function getObjectStructure($context = ''): array {
		return UserNotification::getObjectStructure($context);
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/NotificationsReport', 'Notifications Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View Notifications Reports');
	}

	function canBatchEdit() : bool {
		return false;
	}

	function canCompare() : bool {
		return false;
	}

	public function canEdit() : bool {
		return false;
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter) : void {
		if ($fieldName == 'user') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'user_notifications',
				'sourceField' => 'userId',
				'targetClass' => 'User',
				'targetField' => 'id',
				'getCompareValueMethod' => 'getDisplayName',
				'compareFormat' => 'nameWithBarcode',
			]);
		} elseif ($fieldName == 'library') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'user_notifications',
				'sourceField' => 'userId',
				'targetClass' => 'User',
				'targetField' => 'id',
				'getCompareValueMethod' => 'getHomeLibrarySystemName',
			]);
		} elseif ($fieldName == 'device') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'user_notifications',
				'sourceField' => 'pushToken',
				'targetClass' => 'UserNotificationToken',
				'targetField' => 'pushToken',
				'getCompareValueMethod' => 'deviceModel',
				'compareFormat' => 'property',
			]);
		} else {
			parent::applyFilter($object, $fieldName, $filter);
		}
	}
}