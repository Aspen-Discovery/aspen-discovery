<?php

class LocationSideLoadScope extends DataObject
{
	public $__table = 'location_sideload_scopes';

	public $id;
	public $locationId;
	public $sideLoadScopeId;

	static function getObjectStructure() : array {
		$sideLoadScopes = array();
		require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->orderBy('name');
		$sideLoadScope->find();
		$sideLoadScopes[-1] = 'All Side Loaded eContent for parent library';
		while ($sideLoadScope->fetch()){
			$sideLoadScopes[$sideLoadScope->id] = $sideLoadScope->name;
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
		return array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'sideLoadScopeId' => array('property' => 'sideLoadScopeId', 'type' => 'enum', 'values' => $sideLoadScopes, 'label' => 'Side Load Scope', 'description' => 'The Scope to add to the library', 'required' => true),
			'locationId' => array('property'=>'locationId', 'type'=>'enum','values'=>$locationsList, 'label'=>'Location', 'description'=>'The Location to associate the scope to', 'required' => true),
		);
	}

	function getEditLink(){
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->sideLoadScopeId;
	}
}