<?php /** @noinspection PhpMissingFieldTypeInspection */

class LocationBorrowBoxScope extends DataObject {
	public $__table = 'location_borrowbox_scope';
	public $id;
	public $scopeId;
	public $locationId;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'scopeId',
			'weight',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxScope.php';
		$borrowBoxScopes = [];
		$borrowBoxScopes[-1] = translate([
			'text' => 'Select a value',
			'isPublicFacing' => true,
		]);
		$borrowBoxScope = new BorrowBoxScope();
		$borrowBoxScope->orderBy('name');
		$borrowBoxScopes = $borrowBoxScopes + $borrowBoxScope->fetchAll('id', 'name');

		$locationsList = [];
		$location = new Location();
		$location->selectAdd();
		$location->selectAdd('locationId');
		$location->selectAdd('displayName');
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if (!empty($homeLibrary)) {
				$location->libraryId = $homeLibrary->libraryId;
			}
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
				'values' => $borrowBoxScopes,
				'label' => 'BorrowBox Scope',
				'description' => 'The BorrowBox scope to use',
				'hideInLists' => false,
				'default' => -1,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getEditLink(string $context): string {
		if ($context == 'locations') {
			return '/Admin/Locations?objectAction=edit&id=' . $this->locationId . '#propertyRowborrowBoxScopes';
		}else {
			return '/BorrowBox/Scopes?objectAction=edit&id=' . $this->scopeId;
		}
	}
}
