<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SystemLogging/UserAgent.php';

class Admin_UserAgents extends ObjectEditor {
	function getObjectType(): string {
		return 'UserAgent';
	}

	function getToolName(): string {
		return 'UserAgents';
	}

	function getPageTitle(): string {
		return 'User Agents';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new UserAgent();
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
		return 'userAgent asc';
	}

	function getObjectStructure($context = ''): array {
		return UserAgent::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'userAgent';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/useragents';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/UserAgents', 'User Agents');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer User Agents');
	}

	function canDelete() {
		return false;
	}
}