<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

class Admin_BrowseCategories extends ObjectEditor
{

	function getObjectType(){
		return 'BrowseCategory';
	}
	function getToolName(){
		return 'BrowseCategories';
	}
	function getPageTitle(){
		return 'Browse Categories';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new BrowseCategory();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Browse Categories')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$libraryId = $library == null ? -1 : $library->libraryId;
			$object->whereAdd("sharing = 'everyone'");
			$object->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
		}
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getDefaultSort()
	{
		return 'label asc';
	}

	function getObjectStructure(){
		return BrowseCategory::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getInstructions(){
		return '';
	}

	function getInitializationJs(){
		return 'return AspenDiscovery.Admin.updateBrowseSearchForSource();';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategories', 'Browse Categories');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Browse Categories','Administer Library Browse Categories']);
	}
}