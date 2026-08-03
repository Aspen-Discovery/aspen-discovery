<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SearchObject/SearchSetting.php';

class Admin_SearchSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'SearchSetting';
	}

	function getToolName(): string {
		return 'SearchSettings';
	}

	function getPageTitle(): string {
		return 'Search Settings';
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer All Search Settings');
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new SearchSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Search Settings')) {
			$libraryList = Library::getLibraryListAsObjects(true);
			$validIds = [];
			foreach ($libraryList as $tmpLibrary) {
				$validIds[] = $tmpLibrary->SearchSettingId;
			}
			$object->whereAddIn('id', $validIds, false);
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

	function getObjectStructure($context = ''): array {
		return SearchSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/347373607/Grouped+Works+and+Record+Display';
	}

	/** @noinspection PhpUnused */
	function resetMoreDetailsToDefault() : void {
		$groupedWorkSetting = new SearchSetting();
		$groupedWorkSettingId = $_REQUEST['id'];
		$groupedWorkSetting->id = $groupedWorkSettingId;
		if ($groupedWorkSetting->find(true)) {
			$groupedWorkSetting->clearMoreDetailsOptions();

			$defaultOptions = [];
			require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
			$defaultMoreDetailsOptions = RecordInterface::getDefaultMoreDetailsOptions();
			$i = 0;
			foreach ($defaultMoreDetailsOptions as $source => $defaultState) {
				$optionObj = new GroupedWorkMoreDetails();
				$optionObj->groupedWorkSettingsId = $groupedWorkSettingId;
				$optionObj->collapseByDefault = $defaultState == 'closed';
				$optionObj->source = $source;
				$optionObj->weight = $i++;
				$defaultOptions[] = $optionObj;
			}

			$groupedWorkSetting->setMoreDetailsOptions($defaultOptions);
			$groupedWorkSetting->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		header("Location: /Admin/SearchSettings?objectAction=edit&id=" . $groupedWorkSettingId);
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateSearchSettingsFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Searching');
		$breadcrumbs[] = new Breadcrumb('/Admin/SearchSettings', 'Search Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'searching';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Search Settings',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Search Settings',
		]);
	}
}