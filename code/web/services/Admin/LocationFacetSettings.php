<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class LocationFacetSettings extends ObjectEditor
{

	function getObjectType(){
		return 'LocationFacetSetting';
	}
	function getToolName(){
		return 'LocationFacetSettings';
	}
	function getPageTitle(){
		return 'Location Facets';
	}
	function getAllObjects(){
		$facetsList = array();
		$locationId = $_REQUEST['locationId'];

		$library = new LocationFacetSetting();
		$library->locationId = $locationId;
		$library->orderBy('weight');
		$library->find();
		while ($library->fetch()){
			$facetsList[$library->id] = clone $library;
		}

		return $facetsList;
	}
	function getObjectStructure(){
		return LocationFacetSetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if (isset($existingObject) && $existingObject != null){
			$objectActions[] = array(
				'text' => 'Return to Location',
				'url' => '/Admin/Locations?objectAction=edit&id=' . $existingObject->locationId,
			);
		}
		return $objectActions;
	}
}