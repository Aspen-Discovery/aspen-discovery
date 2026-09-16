<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LibraryOmekaScope extends DataObject {
	public $__table = 'library_omeka_scopes';
	public $__displayNameColumn = 'scope_name';
	public $scope_name;
	public $id;
	public $libraryId;
	public $omekaScopeId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$allLibraryList = Library::getLibraryList(false);

		require_once ROOT_DIR . '/sys/Omeka/OmekaScope.php';
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
				'values' => OmekaScope::getScopeListWithSettingNames(),
				'label' => 'Omeka Scope',
				'description' => 'The scope to add to the library',
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
