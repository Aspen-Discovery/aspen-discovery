<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';

class Admin_PTypes extends ObjectEditor
{

	function getObjectType(){
		return 'PType';
	}
	function getToolName(){
		return 'PTypes';
	}
	function getPageTitle(){
		return 'Patron Types';
	}
	function getAllObjects(){
		$libraryList = array();

		if (UserAccount::userHasRole('opacAdmin')){
			$library = new PType();
			$library->orderBy('pType');
			$library->find();
			while ($library->fetch()){
				$libraryList[$library->id] = clone $library;
			}
		}

		return $libraryList;
	}
	function getObjectStructure(){
		return PType::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'pType';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function canAddNew(){
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/PTypes', 'Patron Types');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}
}