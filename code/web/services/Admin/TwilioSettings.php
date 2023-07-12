<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SMS/TwilioSetting.php';

class Admin_TwilioSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'TwilioSetting';
	}

	function getToolName(): string {
		return 'TwilioSettings';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'Twilio Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new TwilioSetting();
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
		return TwilioSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/TwilioSettings', 'Twilio Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Twilio');
	}
}