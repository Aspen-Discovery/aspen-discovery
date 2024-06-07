<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Email/SMTPSetting.php';

class Admin_SMTPSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'SMTPSetting';
	}

	function getToolName(): string {
		return 'SMTPSettings';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'SMTP Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new SMTPSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure($context = ''): array {
		return SMTPSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SMTPSettings', 'SMTP Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'email';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer SMTP');
	}


}