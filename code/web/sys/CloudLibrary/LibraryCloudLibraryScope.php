<?php


class LibraryCloudLibraryScope extends DataObject
{
	public $__table = 'library_cloud_library_scope';
	public $id;
	public $scopeId;
	public $libraryId;

	public function getNumericColumnNames()
	{
		return ['libraryId', 'scopeId'];
	}

	static function getObjectStructure()
	{
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->orderBy('name');
		$cloudLibraryScopes = [];
		$cloudLibraryScope->find();
		$cloudLibraryScopes[-1] = translate('Select a value');
		while ($cloudLibraryScope->fetch()) {
			$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
		}

		return [
			'libraryId' => array('property' => 'libraryId', 'type' => 'label', 'label' => 'Library Id', 'description' => 'The unique id of the library within the database'),
			'scopeId' =>array('property'=>'scopeId', 'type'=>'enum','values'=>$cloudLibraryScopes,  'label'=>'Cloud Library Scope', 'description'=>'The Cloud Library scope to use', 'hideInLists' => true, 'default'=>-1, 'forcesReindex' => true),
		];
	}
}