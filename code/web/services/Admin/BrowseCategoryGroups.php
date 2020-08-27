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
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAllObjects(){
		$browseCategory = new BrowseCategoryGroup();
		$browseCategory->orderBy('name');
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
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'locationManager', 'contentEditor');
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
}