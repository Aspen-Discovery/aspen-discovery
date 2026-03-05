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

	function getAllObjects(int $page, int $recordsPerPage): array {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new Location();
		$object->orderBy($this->getSort());
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			if (!UserAccount::userHasPermission('Administer Home Library Locations')) {
				//Need to use where add here so the where add in below works properly
				$object->whereAdd("locationId = $user->homeLocationId");
			} else {
				//Scope to just locations for the user based on their home library
				$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
				$object->whereAdd("libraryId = $patronLibrary->libraryId");
			}
			$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
			if (!empty($additionalAdministrationLocations)) {
				$object->whereAddIn('locationId', array_keys($additionalAdministrationLocations), false, 'OR');
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

	public function getViewPermissions() : array {
		return [
			'Administer All Locations',
			'Administer Home Library Locations',
			'Administer Home Location',
		];
	}

	function canAddNew() : bool {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	protected function getDefaultRecordsPerPage() : int {
		return 250;
	}

	protected function showQuickFilterOnPropertiesList() : bool {
		return true;
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateLocationFields();';
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasCopyOptions() : bool {
		return true;
	}
	public function getCopyOptionsFormStructure($activeObject) : array {
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
			if ($activeObject->axis360ScopeId <= -1 && empty($activeObject->getCloudLibraryScope()) && $activeObject->hooplaScopeId <= -1 && empty($activeObject->getLocationOverdriveScopes()) && $activeObject->palaceProjectScopeId <= -1 && empty($activeObject->getSideLoadScopes())) {
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

	function customListActions() : array {
		$actions = [];
		$symphonyActive = false;
		$sierraActive = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'symphony') {
				$symphonyActive = true;
			}elseif ($accountProfile->ils == 'sierra') {
				$sierraActive = true;
			}
		}
		if ($symphonyActive || $sierraActive) {
			$actions[] = [
				'label' => 'Update From ILS',
				'action' => 'loadLocationsFromILS',
			];
		}
		if ($sierraActive) {
			$actions[] = [
				'label' => 'Batch Update Holidays',
				'action' => 'return AspenDiscovery.Admin.getBatchUpdateHolidayForm("location")',
			];
		}
		return $actions;
	}

	/** @noinspection PhpUnused */
	function loadLocationsFromILS() : void {
		global $library;
		$user = UserAccount::getActiveUserObj();
		$accountProfile = $library->getAccountProfile();
		$catalogDriverName = trim($accountProfile->driver);
		$catalogDriver = null;
		if (!empty($catalogDriverName)) {
			$catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriverName, $accountProfile);
		}
		if ($catalogDriver->driver instanceof SirsiDynixROA || $catalogDriver->driver instanceof Sierra) {
			$result = $catalogDriver->driver->loadLocations();
			$user->__set('updateMessage', $result['message']);
			$user->__set('updateMessageIsError', !$result['success']);
		}else{
			$user->__set('updateMessage', translate(['text'=>'This instance is not connected to an ILS where locations can be loaded.', 'isAdminFacing' => true]));
		}
		$user->update();
		header("Location: /Admin/Locations");
	}
}
