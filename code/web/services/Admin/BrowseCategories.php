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
		$user = UserAccount::getLoggedInUser();
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
		return 'For more information on how to create browse categories, see the <a href="https://docs.google.com/document/d/11biGMw6UDKx9UBiDCCj_GBmatx93UlJBLMESNf_RtDU">online documentation</a>.';
	}

	function getListInstructions(){
		return $this->getInstructions();
	}
}