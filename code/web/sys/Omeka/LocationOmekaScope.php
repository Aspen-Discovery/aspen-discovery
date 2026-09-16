<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LocationOmekaScope extends DataObject {
	public $__table = 'location_omeka_scopes';
	public $__displayNameColumn = 'scope_name';
	public $scope_name;
	public $id;
	public $locationId;
	public $omekaScopeId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/Omeka/OmekaScope.php';
		$omekaScopes = [-1 => 'All Omeka Content for parent library'] + OmekaScope::getScopeListWithSettingNames();

		$allLocationsList = Location::getLocationList(false);
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$locationsList = Location::getLocationList(true);
		} else {
			$locationsList = $allLocationsList;
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'omekaScopeId' => [
				'property' => 'omekaScopeId',
				'type' => 'enum',
				'values' => $omekaScopes,
				'label' => 'Omeka Scope',
				'description' => 'The scope to add to the location',
				'required' => true,
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'allValues' => $allLocationsList,
				'values' => $locationsList,
				'label' => 'Location',
				'description' => 'The Location to associate the scope to',
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function fetch(): bool|DataObject|null {
		$result = parent::fetch();
		require_once ROOT_DIR . '/sys/Omeka/OmekaScope.php';
		$scope = new OmekaScope();
		$scope->id = $this->omekaScopeId;
		if ($scope->find(true)) {
			$this->scope_name = $scope->name;
		} else {
			$this->scope_name = (string)$this->omekaScopeId;
		}
		return $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/Omeka/Scopes?objectAction=edit&id=' . $this->omekaScopeId;
	}
}
