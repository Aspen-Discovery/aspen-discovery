<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';

class Admin_LayoutSettings extends ObjectEditor {

	function getObjectType(): string {
		return 'LayoutSetting';
	}

	function getToolName(): string {
		return 'LayoutSettings';
	}

	function getPageTitle(): string {
		return 'Layout Settings';
	}

	function canDelete() {
		return UserAccount::userHasPermission('Administer All Layout Settings');
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new LayoutSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Layout Settings')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->id = $library->layoutSettingId;
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure(): array {
		return LayoutSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/theme';
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateLayoutSettingsFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#theme_and_layout', 'Configuration Templates');
		$breadcrumbs[] = new Breadcrumb('/Admin/LayoutSettings', 'Layout Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'theme_and_layout';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Layout Settings',
			'Administer Library Layout Settings',
		]);
	}
}
