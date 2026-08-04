<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';

class Admin_OAuth2Clients extends ObjectEditor {
	function getObjectType(): string {
		return 'OAuth2Client';
	}

	function getToolName(): string {
		return 'OAuth2Clients';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'OAuth2 Clients';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new OAuth2Client();
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
		return OAuth2Client::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/OAuth2Clients', 'OAuth2 Clients');
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

	function updateFromUI($object, $structure, $fieldLocks): array {
		$clientSecretBackup = null;
		if ($this->objectAction == 'addNew' && isset($object->client_secret)) {
			$clientSecretBackup = $object->client_secret;
			unset($object->client_secret);
		}

		$result = parent::updateFromUI($object, $structure, $fieldLocks);

		if ($clientSecretBackup !== null) {
			$object->client_secret = $clientSecretBackup;
		}

		return $result;
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateOAuth2GrantType(); AspenDiscovery.Admin.maskOAuth2ClientSecret(); return false;';
	}
}
