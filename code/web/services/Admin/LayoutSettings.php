<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';

class Admin_LayoutSettings extends ObjectEditor
{

	function getObjectType(){
		return 'LayoutSetting';
	}
	function getToolName(){
		return 'LayoutSettings';
	}
	function getPageTitle(){
		return 'Layout Settings';
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new LayoutSetting();
		$object->orderBy('name');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return LayoutSetting::getObjectStructure();
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#configuration_templates', 'Configuration Templates');
		$breadcrumbs[] = new Breadcrumb('/Admin/LayoutSettings', 'Layout Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'configuration_templates';
	}
}
