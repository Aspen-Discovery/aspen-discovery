<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Libraries extends ObjectEditor {
	function getObjectType(): string {
		return 'Library';
	}

	function getToolName(): string {
		return 'Libraries';
	}

	function getPageTitle(): string {
		return 'Library Systems';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$libraryList = [];

		$user = UserAccount::getLoggedInUser();
		/** @noinspection PhpIfWithCommonPartsInspection */
		if (UserAccount::userHasPermission('Administer All Libraries')) {
			$object = new Library();
			$object->orderBy($this->getSort());
			$this->applyFilters($object);
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()) {
				$libraryList[$object->libraryId] = clone $object;
			}
		} else {
			//This doesn't need pagination since there should only be one
			$object = new Library();
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary != null) {
				$object->whereAdd("libraryId = $homeLibrary->libraryId");
			}
			$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
			if (!empty($additionalAdministrationLocations)) {
				$locationsForUser = Location::getLocationListAsObjects(true);
				$additionalAdministrationLibraries = [];
				foreach ($locationsForUser as $location) {
					$additionalAdministrationLibraries[$location->libraryId] = $location->libraryId;
				}

				$object->whereAddIn('libraryId', $additionalAdministrationLibraries, false, 'OR');
			}

			$object->orderBy($this->getSort());
			$this->applyFilters($object);
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()) {
				$libraryList[$object->libraryId] = clone $object;
			}

		}

		return $libraryList;
	}

	function getDefaultSort(): string {
		return 'subdomain asc';
	}

	function getObjectStructure($context = ''): array {
		$objectStructure = Library::getObjectStructure($context);
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			unset($objectStructure['isDefault']);
		}
		global $library;
		$accountProfile = $library->getAccountProfile();
		if (!$accountProfile || empty($accountProfile) || !isset($accountProfile->driver)) {
			return $objectStructure;
		}

		// if 'Enable ILS-issued consents' is not already toggled on, prevent administrators from seeing it (and toggling it on) if no consent plugins are installed and enabled in Koha
		$catalogDriver = CatalogFactory::getCatalogConnectionInstance(trim($accountProfile->driver), $accountProfile);
		$consentPluginNames = $catalogDriver->getPluginNamesByMethodName('patron_consent_type');
		$anyConsentPluginsEnabled = false;
		if ($consentPluginNames != false) {
			foreach ($consentPluginNames as $pluginName) {
				$pluginStatus = $catalogDriver->getPluginStatus($pluginName);
				if ($pluginStatus['enabled']) {
					$anyConsentPluginsEnabled = true;
					break;
				}
			}
		}

		if($catalogDriver->hasIlsConsentSupport() && !$anyConsentPluginsEnabled && $library->ilsConsentEnabled == 0) {
			unset($objectStructure['dataProtectionRegulations']['properties']['ilsConsentEnabled']);
		}
		
		return $objectStructure;
	}

	function getPrimaryKeyColumn(): string {
		return 'subdomain';
	}

	function getIdKeyColumn(): string {
		return 'libraryId';
	}

	function canAddNew() : bool {
		return UserAccount::userHasPermission('Administer All Libraries');
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission('Administer All Libraries');
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	/** @noinspection PhpUnused
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	function defaultMaterialsRequestForm() : void {
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)) {
			$library->clearMaterialsRequestFormFields();

			$defaultFieldsToDisplay = MaterialsRequestFormFields::getDefaultFormFields($libraryId);
			$library->setMaterialsRequestFormFields($defaultFieldsToDisplay);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();

	}

	/** @noinspection PhpUnused
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	function defaultMaterialsRequestFormats() : void {
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)) {
			$library->clearMaterialsRequestFormats();

			$defaultMaterialsRequestFormats = MaterialsRequestFormat::getDefaultMaterialRequestFormats($libraryId);
			$library->setMaterialsRequestFormats($defaultMaterialsRequestFormats);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/systemslocations';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateMaterialsRequestFields(); return AspenDiscovery.Admin.updateHoldCancellationDateFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Libraries', 'Library Systems');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Libraries',
			'Administer Home Library',
		]);
	}

	protected function getDefaultRecordsPerPage() : int {
		return 250;
	}

	protected function showQuickFilterOnPropertiesList() : bool {
		return true;
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasCopyOptions() : bool {
		return true;
	}

	public function getCopyNotes() : string {
		return '/admin_instructions/library_copy.MD';
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
			'holidays' => [
				'property' => 'holidays',
				'type' => 'checkbox',
				'label' => 'Holidays',
				'description' => 'Whether or not to copy Holidays',
				'hideInLists' => false,
				'default' => true,
			],
			'illItemTypes' => [
				'property' => 'illItemTypes',
				'type' => 'checkbox',
				'label' => 'eContent',
				'description' => 'Whether or not to copy ILL Item Type settings',
				'hideInLists' => false,
				'default' => true,
			],
			'materialsRequest' => [
				'property' => 'materialsRequest',
				'type' => 'checkbox',
				'label' => 'Materials Request Formats and Form Settings',
				'description' => 'Whether or not to copy Materials Request settings',
				'hideInLists' => false,
				'default' => true,
			],
			'menuLinks' => [
				'property' => 'menuLinks',
				'type' => 'checkbox',
				'label' => 'Menu Links',
				'description' => 'Whether or not to copy Menu Link settings',
				'hideInLists' => false,
				'default' => true,
			],
			'messagingSettings' => [
				'property' => 'messagingSettings',
				'type' => 'checkbox',
				'label' => 'Messaging Settings',
				'description' => 'Whether or not to copy Messaging settings',
				'hideInLists' => false,
				'default' => true,
			],
			'novelist' => [
				'property' => 'novelist',
				'type' => 'checkbox',
				'label' => 'NoveList Settings',
				'description' => 'Whether or not to copy NoveList settings',
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
			'singleSignOn' => [
				'property' => 'singleSignOn',
				'type' => 'checkbox',
				'label' => 'Single Sign On',
				'description' => 'Whether or not to copy Single Sign On settings',
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

		if ($activeObject instanceof Library) {
			if ($activeObject->lidaGeneralSettingId == -1 && $activeObject->lidaNotificationSettingId == -1) {
				unset($settings['aspenLida']);
			}
			if (!$activeObject->enableCombinedResults || empty($activeObject->getCombinedResultSections())) {
				unset($settings['combinedResults']);
			}
			if ($activeObject->axis360ScopeId == -1 && empty($activeObject->getCloudLibraryScope()) && $activeObject->hooplaScopeId == -1 && empty($activeObject->getLibraryOverdriveScopes()) && $activeObject->palaceProjectScopeId == -1 && empty($activeObject->getSideLoadScopes())) {
				unset($settings['eContent']);
			}
			if (empty($activeObject->getHolidays())) {
				unset($settings['holidays']);
			}
			if (empty($activeObject->getILLItemTypes())) {
				unset($settings['illItemTypes']);
			}
			if (empty($activeObject->getMaterialsRequestFieldsToDisplay()) && empty($activeObject->getMaterialsRequestFormats() && empty($activeObject->getMaterialsRequestFormFields()))) {
				unset($settings['materialsRequest']);
			}
			if (empty($activeObject->getLibraryLinks())) {
				unset($settings['menuLinks']);
			}
			if ($activeObject->twilioSettingId == -1) {
				unset($settings['messagingSettings']);
			}
			if ($activeObject->novelistSettingId == -1) {
				unset($settings['novelist']);
			}
			if (empty($activeObject->getRecordsToInclude())) {
				unset($settings['recordsToInclude']);
			}
			if ($activeObject->ssoSettingId == -1) {
				unset($settings['singleSignOn']);
			}
			if (empty($activeObject->getThemes())) {
				unset($settings['themes']);
			}
		}

		return $settings;
	}

	function customListActions() : array {
		$actions = [];
		$sierraActive = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'sierra') {
				$sierraActive = true;
			}
		}
		if ($sierraActive) {
			$actions[] = [
				'label' => 'Update From ILS',
				'action' => 'loadLibrariesFromILS',
			];
		}
		return $actions;
	}

	/** @noinspection PhpUnused */
	function loadLibrariesFromILS() : void {
		global $library;
		$user = UserAccount::getActiveUserObj();
		$accountProfile = $library->getAccountProfile();
		$catalogDriverName = trim($accountProfile->driver);
		$catalogDriver = null;
		if (!empty($catalogDriverName)) {
			$catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriverName, $accountProfile);
		}
		if ($catalogDriver->driver instanceof Sierra) {
			$result = $catalogDriver->driver->loadLibraries();
			$user->__set('updateMessage', $result['message']);
			$user->__set('updateMessageIsError', !$result['success']);
		}else{
			$user->__set('updateMessage', translate(['text'=>'This instance is not connected to an ILS where libraries can be loaded.', 'isAdminFacing' => true]));
		}
		$user->update();
		header("Location: /Admin/Libraries");
	}
}
