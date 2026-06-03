<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/RateLimiter/OAuth2RateLimit.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/RateLimiter/OAuth2RateLimiter.php';

class Admin_OAuth2RateLimits extends ObjectEditor {
	function getObjectType(): string {
		return 'OAuth2RateLimit';
	}

	function getToolName(): string {
		return 'OAuth2RateLimits';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'OAuth2 Rate Limits';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new OAuth2RateLimit();
		$object->orderBy('last_request DESC');
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
		return 'last_request desc';
	}

	function getObjectStructure($context = ''): array {
		return OAuth2RateLimit::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew(): bool {
		return false; // Rate limit records are auto-generated
	}

	function canEdit(): bool {
		return UserAccount::userHasPermission('Administer OAuth2');
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer OAuth2');
	}

	function customListActions(): array {
		$actions = [];

		if (UserAccount::userHasPermission('Administer OAuth2')) {
			$actions[] = [
				'label' => 'Configure Rate Limits',
				'action' => 'configureRateLimits',
				'onClick' => 'return AspenDiscovery.Admin.configureRateLimits();'
			];
		}

		return $actions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'OAuth2 Rate Limits');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'admin';
	}

	function getViewPermissions(): array {
		return ['Administer OAuth2'];
	}

	function canBatchEdit(): bool {
		return false;
	}

	function canExportToCSV(): bool {
		return false;
	}

	function canCompare(): bool {
		return false;
	}
}
