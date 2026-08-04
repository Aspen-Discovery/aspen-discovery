<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';

class Admin_SquareSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'SquareSetting';
	}

	function getToolName(): string {
		return 'SquareSettings';
	}

	function getPageTitle(): string {
		return 'Square Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$list = [];

		$object = new SquareSetting();
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return SquareSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/305102858/eCommerce';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('', 'Square Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	public function getViewPermissions() : array {
		return ['Administer Square'];
	}
}