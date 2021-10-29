<?php


class LocationCloudLibraryScope extends DataObject
{
	public $__table = 'location_cloud_library_scope';
	public $id;
	public $scopeId;
	public $locationId;

	public function getNumericColumnNames() : array
	{
		return ['locationId', 'scopeId'];
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
			$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
		}
		$locationsList = [];
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()){
			$locationsList[$location->locationId] = $location->displayName;
		}

		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'locationId' => array('property'=>'locationId', 'type'=>'enum','values'=>$locationsList, 'label'=>'Location', 'description'=>'The Location to associate the scope to', 'required' => true),
			'scopeId' =>array('property'=>'scopeId', 'type'=>'enum','values'=>$cloudLibraryScopes,  'label'=>'Cloud Library Scope', 'description'=>'The Cloud Library scope to use', 'hideInLists' => true, 'default'=>-1, 'forcesReindex' => true),
		];
	}
}