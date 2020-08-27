<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
class Admin_AccountProfiles extends ObjectEditor {
	function getObjectType(){
		return 'AccountProfile';
	}
	function getToolName(){
		return 'AccountProfiles';
	}
	function getPageTitle(){
		return 'Account Profiles';
	}
	function getAllObjects(){
		$list = array();

		$object = new AccountProfile();
		$object->orderBy('weight, name');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return AccountProfile::getObjectStructure();
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
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
		$breadcrumbs[] = new Breadcrumb('', 'Account Profiles');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}
}