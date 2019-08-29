<?php

class LibrarySideLoadScope extends DataObject
{
	public $__table = 'library_sideload_scopes';

	public $id;
	public $libraryId;
	public $sideLoadScopeId;

	static function getObjectStructure(){
		$library = new Library();
		$library->orderBy('displayName');
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$sideLoadScopes = array();
		require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->orderBy('name');
		$sideLoadScope->find();
		while ($sideLoadScope->fetch()){
			$sideLoadScopes[$sideLoadScope->id] = $sideLoadScope->name;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'sideLoadScopeId' => array('property' => 'sideLoadScopeId', 'type' => 'enum', 'values' => $sideLoadScopes, 'label' => 'Side Load Scope', 'description' => 'The Scope to add to the library', 'required' => true),
			'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library'),
		);
		return $structure;
	}
}