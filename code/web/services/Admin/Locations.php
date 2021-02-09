<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Locations extends ObjectEditor
{

	function getObjectType(){
		return 'Location';
	}
	function getToolName(){
		return 'Locations';
	}
	function getPageTitle(){
		return 'Locations (Branches)';
	}
	function getAllObjects($page, $recordsPerPage){
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new Location();
		$object->orderBy($this->getSort());
		if (!UserAccount::userHasPermission('Administer All Locations')){
			if (!UserAccount::userHasPermission('Administer Home Library Locations')){
				//Scope to just locations for the user based on home library
				$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
				$object->libraryId = $patronLibrary->libraryId;
			}else{
				$object->locationId = $user->homeLocationId;
			}
		}
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$locationList = array();
		while ($object->fetch()){
			$locationList[$object->locationId] = clone $object;
		}
		return $locationList;
	}

	function getDefaultSort()
	{
		return 'displayName asc';
	}

	function getObjectStructure(){
		return Location::getObjectStructure();
	}

	function getPrimaryKeyColumn(){
		return 'code';
	}

	function getIdKeyColumn(){
		return 'locationId';
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if ($existingObject != null && $existingObject instanceof Location){
			$objectActions[] = array(
				'text' => 'Reset Facets To Default',
				'url' => '/Admin/Locations?objectAction=resetFacetsToDefault&amp;id=' . $existingObject->locationId,
			);
		}else{
			echo("Existing object is null");
		}
		return $objectActions;
	}

	function getInstructions(){
		return '/Admin/HelpManual?page=Library-Systems-Locations';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof Location){
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/Locations', 'Locations');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Locations', 'Administer Home Library Locations', 'Administer Home Location']);
	}
}