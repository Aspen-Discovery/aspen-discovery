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
		return UserAccount::userHasPermission('Administer All Layout Settings');
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new LayoutSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Layout Settings')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->id = $library->layoutSettingId;
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
		return 'name asc';
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#theme_and_layout', 'Configuration Templates');
		$breadcrumbs[] = new Breadcrumb('/Admin/LayoutSettings', 'Layout Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'theme_and_layout';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Layout Settings','Administer Library Layout Settings']);
	}
}
