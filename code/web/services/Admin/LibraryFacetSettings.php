<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_LibraryFacetSettings extends ObjectEditor
{

	function getObjectType(){
		return 'LibraryFacetSetting';
	}
	function getToolName(){
		return 'LibraryFacetSettings';
	}
	function getPageTitle(){
		return 'Library Facets';
	}
	function getAllObjects($page, $recordsPerPage){
		$facetsList = array();
		$object = new LibraryFacetSetting();
		if (isset($_REQUEST['libraryId'])){
			$libraryId = $_REQUEST['libraryId'];
			$object->libraryId = $libraryId;
		}
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$facetsList[$object->id] = clone $object;
		}

		return $facetsList;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}
	function getObjectStructure(){
		return LibraryFacetSetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
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
		if (!empty($this->activeObject) && $this->activeObject instanceof LibraryFacetSetting){
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Archive Facet Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Libraries', 'Administer Home Library']);
	}
}