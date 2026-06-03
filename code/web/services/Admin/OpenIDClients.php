<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OpenIDClient.php';

class Admin_OpenIDClients extends ObjectEditor {
	function getObjectType(): string {
		return 'OpenIDClient';
	}

	function getToolName(): string {
		return 'OpenIDClients';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'OpenID Connect (OIDC) Clients';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new OpenIDClient();
		$object->orderBy('name');
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return OpenIDClient::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/OpenIDClients', 'OpenID Connect (OIDC) Clients');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
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

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.maskOAuth2ClientSecret(); return false;';
	}
}
