<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/ILSNotificationSetting.php';

class AspenLiDA_ILSNotificationSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'ILSNotificationSetting';
	}

	function getToolName(): string {
		return 'ILSNotificationSettings';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'ILS Notification Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new ILSNotificationSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return ILSNotificationSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/ILSNotificationSettings', 'ILS Notification Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

}