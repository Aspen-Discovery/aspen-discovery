<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ECommerce/Pay360Setting.php';

class Admin_Pay360Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'Pay360Setting';
	}

	function getToolName(): string {
		return 'Pay360Settings';
	}

	function getPageTitle(): string {
		return 'Pay360 Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new Pay360Setting();
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
		return Pay360Setting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('', 'Pay360 Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	public function getViewPermissions() : array {
		return ['Administer Pay360'];
	}
}