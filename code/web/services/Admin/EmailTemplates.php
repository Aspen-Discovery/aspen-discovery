<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';

class Admin_EmailTemplates extends ObjectEditor {
	function getObjectType(): string {
		return 'EmailTemplate';
	}

	function getToolName(): string {
		return 'EmailTemplates';
	}

	function getPageTitle(): string {
		return 'Email Templates';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new EmailTemplate();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		if (!UserAccount::userHasPermission('Administer All Email Templates')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->facetGroupId;
		}
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return EmailTemplate::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#email', 'Email');
		$breadcrumbs[] = new Breadcrumb('', 'Email Templates');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer All Email Templates') || UserAccount::userHasPermission('Administer Library Email Templates');
	}
}