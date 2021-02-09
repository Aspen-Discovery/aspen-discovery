<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';

class SideLoads_Scopes extends ObjectEditor
{
	function launch()
	{
		if (isset($_REQUEST['id'])){
			$sideLoadScope = new SideLoadScope();
			$sideLoadScope->id = $_REQUEST['id'];
			if ($sideLoadScope->find(true)){
				$sideLoadConfiguration = new SideLoad();
				$sideLoadConfiguration->id = $sideLoadScope->sideLoadId;
				if ($sideLoadConfiguration->find(true)){
					global $interface;
					$interface->assign('sideload', $sideLoadConfiguration);
				}
			}
		}

		parent::launch();
	}

	function getObjectType(){
		return 'SideLoadScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'SideLoads';
	}
	function getPageTitle(){
		return 'Side Loaded eContent Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new SideLoadScope();
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
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
		return SideLoadScope::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
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
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)){
			$existingLibrariesSideLoadScopes = $sideLoadScope->getLibraries();
			$library = new Library();
			$library->find();
			while ($library->fetch()){
				$alreadyAdded = false;
				foreach($existingLibrariesSideLoadScopes as $librarySideLoadScope){
					if ($librarySideLoadScope->libraryId == $library->libraryId){
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded){
					$newLibrarySideLoadScope = new LibrarySideLoadScope();
					$newLibrarySideLoadScope->libraryId = $library->libraryId;
					$newLibrarySideLoadScope->sideLoadScopeId = $scopeId;
					$existingLibrariesSideLoadScopes[] = $newLibrarySideLoadScope;
				}
			}
			$sideLoadScope->setLibraries($existingLibrariesSideLoadScopes);
			$sideLoadScope->update();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries()
	{
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)){
			$sideLoadScope->clearLibraries();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations(){
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)){
			$existingLocationSideLoadScopes = $sideLoadScope->getLocations();
			$location = new Location();
			$location->find();
			while ($location->fetch()){
				$alreadyAdded = false;
				foreach($existingLocationSideLoadScopes as $locationSideLoadScope){
					if ($locationSideLoadScope->locationId == $location->locationId){
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded){
					$newLocationSideLoadScope = new LocationSideLoadScope();
					$newLocationSideLoadScope->locationId = $location->locationId;
					$newLocationSideLoadScope->sideLoadScopeId = $scopeId;
					$existingLocationSideLoadScopes[] = $newLocationSideLoadScope;
				}
			}
			$sideLoadScope->setLocations($existingLocationSideLoadScopes);
			$sideLoadScope->update();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations()
	{
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)){
			$sideLoadScope->clearLocations();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		if (!empty($this->activeObject) && $this->activeObject instanceof SideLoadScope){
			$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads?objectAction=edit&id=' . $this->activeObject->sideLoadId , 'Side Load Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/SideLoads/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'side_loads';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Side Loads');
	}
}