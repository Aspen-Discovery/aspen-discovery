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
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAllObjects(){
		$browseCategory = new BrowseCategory();
		$browseCategory->orderBy('label');
		$browseCategory->find();
		$list = array();
		while ($browseCategory->fetch()){
			$list[$browseCategory->id] = clone $browseCategory;
		}
		return $list;
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
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'locationManager', 'contentEditor');
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
}