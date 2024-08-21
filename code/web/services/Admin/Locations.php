<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Locations extends ObjectEditor {

	function getObjectType(): string {
		return 'Location';
	}

	function getToolName(): string {
		return 'Locations';
	}

	function getPageTitle(): string {
		return 'Locations (Branches)';
	}

	function getAllObjects($page, $recordsPerPage): array {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new Location();
		$object->orderBy($this->getSort());
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			if (!UserAccount::userHasPermission('Administer Home Library Locations')) {
				$object->locationId = $user->homeLocationId;
			} else {
				//Scope to just locations for the user based on home library
				$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
				$object->libraryId = $patronLibrary->libraryId;
			}
		}
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$locationList = [];
		while ($object->fetch()) {
			$locationList[$object->locationId] = clone $object;
		}
		return $locationList;
	}

	function getDefaultSort(): string {
		return 'displayName asc';
	}

	function getObjectStructure($context = ''): array {
		return Location::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'code';
	}

	function getIdKeyColumn(): string {
		return 'locationId';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/systemslocations';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof Location) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/Locations', 'Locations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Locations',
			'Administer Home Library Locations',
			'Administer Home Location',
		]);
	}

	function canAddNew() {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	function canDelete() {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	protected function getDefaultRecordsPerPage() {
		return 250;
	}

	protected function showQuickFilterOnPropertiesList() {
		return true;
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateLocationFields();';
	}

	public function canCopy() {
		return $this->canAddNew();
	}

	public function hasCopyOptions() {
		return true;
	}
	public function getCopyOptionsFormStructure($activeObject) {
		$settings = [
			'aspenLida' => [
				'property' => 'aspenLida',
				'type' => 'checkbox',
				'label' => 'Aspen LiDA Settings',
				'description' => 'Whether or not to copy Aspen LiDA settings',
				'hideInLists' => false,
				'default' => true,
			],
			'combinedResults' => [
				'property' => 'combinedResults',
				'type' => 'checkbox',
				'label' => 'Combined Results Settings',
				'description' => 'Whether or not to copy Combined Results settings',
				'hideInLists' => false,
				'default' => true,
			],
			'eContent' => [
				'property' => 'eContent',
				'type' => 'checkbox',
				'label' => 'eContent',
				'description' => 'Whether or not to copy eContent settings',
				'hideInLists' => false,
				'default' => true,
			],
			'moreDetails' => [
				'property' => 'moreDetails',
				'type' => 'checkbox',
				'label' => 'Full Record Options',
				'description' => 'Whether or not to copy Full Record Options',
				'hideInLists' => false,
				'default' => true,
			],
			'hours' => [
				'property' => 'hours',
				'type' => 'checkbox',
				'label' => 'Hours',
				'description' => 'Whether or not to copy Hours',
				'hideInLists' => false,
				'default' => true,
			],
			'recordsToInclude' => [
				'property' => 'recordsToInclude',
				'type' => 'checkbox',
				'label' => 'Records To Include',
				'description' => 'Whether or not to copy Records To Include',
				'hideInLists' => false,
				'default' => true,
			],
			'themes' => [
				'property' => 'themes',
				'type' => 'checkbox',
				'label' => 'Themes',
				'description' => 'Whether or not to copy themes',
				'hideInLists' => false,
				'default' => true,
			],
		];
		if ($activeObject instanceof Location) {
			if ($activeObject->lidaLocationSettingId == -1 && $activeObject->lidaSelfCheckSettingId == -1) {
				unset($settings['aspenLida']);
			}
			if (!$activeObject->useLibraryCombinedResultsSettings || !$activeObject->enableCombinedResults || empty($activeObject->getCombinedResultSections())) {
				unset($settings['combinedResults']);
			}
			if ($activeObject->axis360ScopeId <= -1 && empty($activeObject->getCloudLibraryScope()) && $activeObject->hooplaScopeId <= -1 && $activeObject->overDriveScopeId <= -1 && $activeObject->palaceProjectScopeId <= -1 && empty($activeObject->getSideLoadScopes())) {
				unset($settings['eContent']);
			}
			if (empty($activeObject->getMoreDetailsOptions())) {
				unset($settings['moreDetails']);
			}
			if (empty($activeObject->getHours())) {
				unset($settings['hours']);
			}
			if (empty($activeObject->getRecordsToInclude())) {
				unset($settings['recordsToInclude']);
			}
			if ($activeObject->useLibraryThemes || empty($activeObject->getThemes())) {
				unset($settings['themes']);
			}
		}
		return $settings;
	}
}