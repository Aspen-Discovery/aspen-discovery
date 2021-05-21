<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';

class CloudLibrary_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'CloudLibraryScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'CloudLibrary';
	}
	function getPageTitle(){
		return 'Cloud Library Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new CloudLibraryScope();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}
	function getObjectStructure(){
		return CloudLibraryScope::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return true;
	}
	function canDelete(){
		return true;
	}
	function getAdditionalObjectActions($existingObject){
		return [];
	}

	function getInstructions(){
		return '';
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries(){
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)){
			$existingLibrariesCloudLibraryScopes = $cloudLibraryScope->getLibraries();
			$library = new Library();
			$library->find();
			while ($library->fetch()){
				$alreadyAdded = false;
				foreach($existingLibrariesCloudLibraryScopes as $libraryCloudLibraryScope){
					if ($libraryCloudLibraryScope->libraryId == $library->libraryId){
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded){
					$newLibraryCloudLibraryScope = new LibraryCloudLibraryScope();
					$newLibraryCloudLibraryScope->libraryId = $library->libraryId;
					$newLibraryCloudLibraryScope->scopeId = $scopeId;
					$existingLibrariesCloudLibraryScopes[] = $newLibraryCloudLibraryScope;
				}
			}
			$cloudLibraryScope->setLibraries($existingLibrariesCloudLibraryScopes);
			$cloudLibraryScope->update();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries()
	{
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)){
			$cloudLibraryScope->clearLibraries();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations(){
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)){
			$existingLocationCloudLibraryScopes = $cloudLibraryScope->getLocations();
			$location = new Location();
			$location->find();
			while ($location->fetch()){
				$alreadyAdded = false;
				foreach($existingLocationCloudLibraryScopes as $locationCloudLibraryScope){
					if ($locationCloudLibraryScope->locationId == $location->locationId){
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded){
					$newLocationCloudLibraryScope = new LocationCloudLibraryScope();
					$newLocationCloudLibraryScope->locationId = $location->locationId;
					$newLocationCloudLibraryScope->scopeId = $scopeId;
					$existingLocationCloudLibraryScopes[] = $newLocationCloudLibraryScope;
				}
			}
			$cloudLibraryScope->setLocations($existingLocationCloudLibraryScopes);
			$cloudLibraryScope->update();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations()
	{
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)){
			$cloudLibraryScope->clearLocations();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'Cloud Library');
		$breadcrumbs[] = new Breadcrumb('/CloudLibrary/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cloud_library';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Cloud Library');
	}
}