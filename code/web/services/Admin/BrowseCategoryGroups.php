<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

class Admin_BrowseCategoryGroups extends ObjectEditor
{

	function getObjectType(){
		return 'BrowseCategoryGroup';
	}
	function getToolName(){
		return 'BrowseCategoryGroups';
	}
	function getPageTitle(){
		return 'Browse Category Groups';
	}
	function canDelete(){
		return UserAccount::userHasPermission('Administer All Browse Categories');
	}
	function getAllObjects($page, $recordsPerPage){
		$browseCategory = new BrowseCategoryGroup();
		$browseCategory->orderBy('name');
		$browseCategory->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Browse Categories')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$browseCategory->id = $library->browseCategoryGroupId;
		}
		$browseCategory->find();
		$list = array();
		while ($browseCategory->fetch()){
			$list[$browseCategory->id] = clone $browseCategory;
		}
		return $list;
	}
	function getObjectStructure(){
		return BrowseCategoryGroup::getObjectStructure();
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategoryGroups', 'Browse Category Groups');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Browse Categories', 'Administer Library Browse Categories']);
	}
}