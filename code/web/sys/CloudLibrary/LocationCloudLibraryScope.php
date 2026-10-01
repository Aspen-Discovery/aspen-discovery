<?php /** @noinspection PhpMissingFieldTypeInspection */


class LocationCloudLibraryScope extends DataObject {
	public $__table = 'location_cloud_library_scope';
	public $id;
	public $scopeId;
	public $locationId;

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'scopeId',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->orderBy('name');
		$cloudLibraryScopes = [];
		$cloudLibraryScope->find();
		$cloudLibraryScopes[-1] = translate([
			'text' => 'Select a value',
			'isPublicFacing' => true,
		]);
		while ($cloudLibraryScope->fetch()) {
			$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
		}
		$locationsList = [];
		$location = new Location();
		$location->selectAdd();
		$location->selectAdd('locationId');
		$location->selectAdd('displayName');
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()) {
			$locationsList[$location->locationId] = $location->displayName;
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'values' => $locationsList,
				'label' => 'Location',
				'description' => 'The Location to associate the scope to',
				'required' => true,
			],
			'scopeId' => [
				'property' => 'scopeId',
				'type' => 'enum',
				'values' => $cloudLibraryScopes,
				'label' => 'cloudLibrary Scope',
				'description' => 'The cloudLibrary scope to use',
				'hideInLists' => true,
				'default' => -1,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}