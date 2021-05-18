<?php


class LocationCloudLibraryScope extends DataObject
{
	public $__table = 'location_cloud_library_scope';
	public $id;
	public $scopeId;
	public $locationId;

	public function getNumericColumnNames()
	{
		return ['locationId', 'scopeId'];
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
			'locationId' => array('property' => 'locationId', 'type' => 'label', 'label' => 'Location Id', 'description' => 'The unique id of the location within the database'),
			'scopeId' =>array('property'=>'scopeId', 'type'=>'enum','values'=>$cloudLibraryScopes,  'label'=>'Cloud Library Scope', 'description'=>'The Cloud Library scope to use', 'hideInLists' => true, 'default'=>-1, 'forcesReindex' => true),
		];
	}
}