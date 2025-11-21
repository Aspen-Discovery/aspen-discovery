<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SierraRegistration.php';

class ILS_ReviewLibraryRegistrations extends ObjectEditor {
	function getObjectType(): string {
		return 'SierraRegistration';
	}

	function getModule(): string {
		return "ILS";
	}

	function getToolName(): string {
		return 'ReviewLibraryRegistrations';
	}

	function getPageTitle(): string {
		return 'Review Library Registrations';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new SierraRegistration();
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasPermission('Review Self Registrations for All Libraries')) {
			$includedLocations = Library::getLibraryList(true);
			$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
			$includedLocations = $includedLocations + $additionalAdministrationLocations;
			$object->whereAddIn("libraryId", array_keys($includedLocations), false);
		}

		$list = [];
		$object->approved = 0;
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		if (!empty($list)) {
			$list = self::getPatronData($list);
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'dateRegistered desc';
	}

	function getObjectStructure($context = ''): array {
		return SierraRegistration::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/ReviewLibraryRegistrations', 'Review Library Registrations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Review Self Registrations for All Libraries') || UserAccount::userHasPermission('Review Self Registrations for Home Library Only');
	}

	function canAddNew(): bool {
		return false;
	}

	function canCopy(): bool {
		return false;
	}

	function viewIndividualObject($structure): void {
		global $interface;
		$interface->assign('saveButtonText', translate(['text' => 'Approve Registration', 'isAdminFacing' => true]));
		parent::viewIndividualObject($structure);
	}

	public function getSortableFields($structure): array {
		$fields = parent::getSortableFields($structure);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Review Self Registrations for All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Review Self Registrations for All Libraries') || UserAccount::userHasPermission('Review Self Registrations for Home Library Only'));
		$fields['libraryId'] = [
			'type' => 'enum',
			'values' => $libraryList,
			'property' => 'libraryId',
			'label' => 'Library'
		];
		$fields['locationId'] = [
			'type' => 'enum',
			'values' => $locationList,
			'property' => 'locationId',
			'label' => 'Location'
		];
		unset($fields['Library']);
		unset($fields['Location']);
		unset($fields['Name']);
		foreach ($fields as $label => $field) {
			if (!empty($field['hideInLists'])) {
				unset($fields[$label]);
			}
		}
		return $fields;
	}

	public function getFilterFields($structure): array {
		$fields = parent::getFilterFields($structure);
		$allValues = ['all_values' => 'All Values'];
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Review Self Registrations for All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Review Self Registrations for All Libraries') || UserAccount::userHasPermission('Review Self Registrations for Home Library Only'));
		$fields['libraryId'] = [
			'type' => 'enum',
			'values' => $allValues + $libraryList,
			'property' => 'libraryId',
			'label' => 'Library'
		];
		$fields['locationId'] = [
			'type' => 'enum',
			'values' => $allValues + $locationList,
			'property' => 'locationId',
			'label' => 'Location'
		];
		unset($fields['libraryName']);
		unset($fields['locationName']);
		unset($fields['name']);
		foreach ($fields as $prop => $field) {
			if (!empty($field['hideInLists'])) {
				unset($fields[$prop]);
			}
		}
		return $fields;
	}

	function getDefaultFilters(array $filterFields): array {
		return [
			'libraryId' => [
				'fieldName' => 'libraryId',
				'filterType' => 'enum',
				'filterValue' => 'all_values',
				'field' => $filterFields['libraryId'],
			],
			'locationId' => [
				'fieldName' => 'locationId',
				'filterType' => 'enum',
				'filterValue' => 'all_values',
				'field' => $filterFields['locationId'],
			],
		];
	}

	function getPatronData($list): array {
		global $library;
		$accountProfile = $library->getAccountProfile();
		$catalogDriverName = trim($accountProfile->driver);
		$catalogDriver = null;
		if (!empty($catalogDriverName)) {
			$catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriverName, $accountProfile);
		}
		if ($catalogDriver->driver instanceof Sierra) {
			$patronIds = array_column($list, 'patronId');
			$regIds = array_column($list, 'id');
			$lookupIdTable = array_combine($patronIds, $regIds);
			$patrons = $catalogDriver->driver->getPatronsByIdList($patronIds);
			foreach ($patrons->entries as $patron) {
				$list[$lookupIdTable[$patron->id]]->_sierraData = $patron;
			}
			return $list;
		} else {
			return [];
		}
	}

}