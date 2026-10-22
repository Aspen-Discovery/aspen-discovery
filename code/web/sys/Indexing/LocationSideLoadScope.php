<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LocationSideLoadScope extends DataObject {
	public $__table = 'location_sideload_scopes';
	public $__displayNameColumn = 'scope_name';
	public $scope_name;
	public $id;
	public $locationId;
	public $sideLoadScopeId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$sideLoadScopes = [];
		require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->orderBy('name');
		$sideLoadScope->find();
		$sideLoadScopes[-1] = 'All Side Loaded Content for parent library';
		while ($sideLoadScope->fetch()) {
			$sideLoadScopes[$sideLoadScope->id] = $sideLoadScope->name;
		}
		$allLocationsList = Location::getLocationList(false);
		if (!UserAccount::userHasPermission('Administer All Side Loads')) {
			$locationsList = Location::getLocationList(true);
		}else{
			$locationsList = $allLocationsList;
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'sideLoadScopeId' => [
				'property' => 'sideLoadScopeId',
				'type' => 'enum',
				'values' => $sideLoadScopes,
				'label' => 'Side Load Scope',
				'description' => 'The Scope to add to the library',
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
		require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';
		$scope = new SideLoadScope();
		$scope->id = $this->sideLoadScopeId;
		if ($scope->find(true)) {
			$this->scope_name = $scope->name;
		} else {
			$this->scope_name = (string)$this->sideLoadScopeId;
		}
		return $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->sideLoadScopeId;
	}
}