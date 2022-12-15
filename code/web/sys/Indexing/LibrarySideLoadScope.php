<?php

class LibrarySideLoadScope extends DataObject {
	public $__table = 'library_sideload_scopes';

	public $id;
	public $libraryId;
	public $sideLoadScopeId;

	static function getObjectStructure($context = ''): array {
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}

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
		return [
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
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
			],
		];
	}

	function getEditLink($context): string {
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->sideLoadScopeId;
	}
}