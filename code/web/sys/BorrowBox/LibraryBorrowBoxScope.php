<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibraryBorrowBoxScope extends DataObject {
	public $__table = 'library_borrowbox_scope';
	public $id;
	public $scopeId;
	public $libraryId;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'libraryId',
			'scopeId',
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

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
			],
			'scopeId' => [
				'property' => 'scopeId',
				'type' => 'enum',
				'values' => $borrowBoxScopes,
				'label' => 'BorrowBox Scope',
				'description' => 'The BorrowBox scope to use',
				'default' => -1,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getEditLink(string $context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId . '#propertyRowborrowBoxScopes';
		}else{
			return '/BorrowBox/Scopes?objectAction=edit&id=' . $this->scopeId;
		}
	}
}
