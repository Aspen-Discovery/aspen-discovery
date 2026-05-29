<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Plugins/Plugin.php';
class Admin_PluginManager extends ObjectEditor {

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Plugin Manager');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function getObjectType(): string {
		return 'Plugin';
	}

	function getToolName(): string {
		return 'PluginManager';
	}

	function getPageTitle(): string {
		return 'Plugin Manager';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		global $configArray;
		$list = [];
		if (!empty($configArray['Plugins']) && !empty($configArray['Plugins']['enabled'])) {
			$object = new Plugin();
			$object->orderBy($this->getSort());
			$this->applyFilters($object);
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}else{
			global $interface;
			$interface->assign('propertiesListWarningMessage', 'Plugins are disabled on this system. To enable plugins please contact your support vendor or review the plugin documentation.');
		}

		return $list;
	}

	function getObjectStructure($context = ''): array {
		return Plugin::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function canAddNew() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	function canBatchEdit() : bool {
		return false;
	}

	function canBatchDelete() : bool {
		return false;
	}

	function showHistory() : void {}

	function showHistoryLinks() : bool {
		return false;
	}

	function getViewPermissions(): array {
		return ['Administer Plugins'];
	}
}