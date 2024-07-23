<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/ILSMessageType.php';

class AspenLiDA_ILSMessageTypes extends ObjectEditor {
	function getObjectType(): string {
		return 'ILSMessageType';
	}

	function getToolName(): string {
		return 'ILSMessageTypes';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'ILS Message Types';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new ILSMessageType();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'module asc';
	}

	function getObjectStructure($context = ''): array {
		return ILSMessageType::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/ILSNotificationSettings', 'ILS Notification Settings');
		if (!empty($this->activeObject) && $this->activeObject instanceof ILSMessageType) {
			$breadcrumbs[] = new Breadcrumb('/AspenLiDA/ILSNotificationSettings?objectAction=edit&id=' . $this->activeObject->ilsNotificationSettingId, 'ILS Notification Setting');
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

	function canDelete() {
		return false;
	}
}