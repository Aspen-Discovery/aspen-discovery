<?php


class LibraryCloudLibraryScope extends DataObject
{
	public $__table = 'library_cloud_library_scope';
	public $id;
	public $scopeId;
	public $libraryId;

	public function getNumericColumnNames() : array
	{
		return ['libraryId', 'scopeId'];
	}

	static function getObjectStructure() : array
	{
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->orderBy('name');
		$cloudLibraryScopes = [];
		$cloudLibraryScope->find();
		$cloudLibraryScopes[-1] = translate(['text' => 'Select a value', 'isPublicFacing'=>true]);
		while ($cloudLibraryScope->fetch()) {
			$cloudLibraryScopes[$cloudLibraryScope->id] = (string)$cloudLibraryScope;
		}

		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library'),
			'scopeId' =>array('property'=>'scopeId', 'type'=>'enum','values'=>$cloudLibraryScopes,  'label'=>'cloudLibrary Scope', 'description'=>'The cloudLibrary scope to use', 'hideInLists' => true, 'default'=>-1, 'forcesReindex' => true),
		];
	}

	function getEditLink($context) : string{
		return '/CloudLibrary/Scopes?objectAction=edit&id=' . $this->scopeId;
	}
}