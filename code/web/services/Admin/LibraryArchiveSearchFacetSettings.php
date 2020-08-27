<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class LibraryArchiveSearchFacetSettings extends ObjectEditor
{

	function getObjectType(){
		return 'LibraryArchiveSearchFacetSetting';
	}
	function getToolName(){
		return 'LibraryArchiveSearchFacetSettings';
	}
	function getPageTitle(){
		return 'Library Archive Search Facets';
	}
	function getAllObjects(){
		$facetsList = array();
		$library = new LibraryArchiveSearchFacetSetting();
		if (isset($_REQUEST['libraryId'])){
			$libraryId = $_REQUEST['libraryId'];
			$library->libraryId = $libraryId;
		}
		$library->orderBy('weight');
		$library->find();
		while ($library->fetch()){
			$facetsList[$library->id] = clone $library;
		}

		return $facetsList;
	}
	function getObjectStructure(){
		return LibraryArchiveSearchFacetSetting::getObjectStructure();
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
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if (isset($existingObject) && $existingObject != null){
			$objectActions[] = array(
				'text' => 'Return to Library',
				'url' => '/Admin/Libraries?objectAction=edit&id=' . $existingObject->libraryId,
			);
		}
		return $objectActions;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof LibraryArchiveSearchFacetSetting){
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Archive Facet Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}
}