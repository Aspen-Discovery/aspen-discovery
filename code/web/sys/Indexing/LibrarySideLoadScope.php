<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LibrarySideLoadScope extends DataObject {
	public $__table = 'library_sideload_scopes';
	public $__displayNameColumn = 'scope_name';
	public $scope_name;
	public $id;
	public $libraryId;
	public $sideLoadScopeId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Side Loads'));
		$allLibraryList = Library::getLibraryList(false);

		$sideLoadScopes = [];
		require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->joinAdd(new SideLoad(), 'INNER', 'scope', 'sideLoadId', 'id');
		$sideLoadScope->selectAdd();
		$sideLoadScope->selectAdd('sideload_scopes.*');
		$sideLoadScope->selectAdd('scope.name AS scope_name');
		$sideLoadScope->orderBy('scope.name, sideload_scopes.name');
		$sideLoadScope->find();
		$sideLoadScopeData = $sideLoadScope->fetchAssoc();
		while ($sideLoadScopeData) {
			$sideLoadScopes[$sideLoadScopeData['id']] = $sideLoadScopeData['scope_name'] . ' - ' . $sideLoadScopeData['name'];
			$sideLoadScopeData = $sideLoadScope->fetchAssoc();
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
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'allValues' => $allLibraryList,
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
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
			$this->scope_name = '';
		}
		return $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->sideLoadScopeId;
	}
}