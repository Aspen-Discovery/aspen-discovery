<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';

class Admin_IPAddresses extends ObjectEditor {
	function getObjectType(): string {
		return 'IPAddress';
	}

	function getToolName(): string {
		return 'IPAddresses';
	}

	function getPageTitle(): string {
		return 'Location IP Addresses';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new IPAddress();
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
		return 'ip asc';
	}

	function getObjectStructure(): array {
		return IPAddress::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'ip';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/ipaddresses';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/IPAddresses', 'IP Addresses');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer IP Addresses');
	}

	protected function getDefaultRecordsPerPage() {
		return 100;
	}
}