<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Module.php';
class Admin_Modules extends ObjectEditor {
	function getObjectType(){
		return 'Module';
	}
	function getToolName(){
		return 'Modules';
	}
	function getPageTitle(){
		return 'Aspen Discovery Modules';
	}
	function getAllObjects($page, $recordsPerPage){
		$list = array();

		$object = new Module();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
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
		return Module::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canView(){
		return UserAccount::userHasPermission('Administer Modules');
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return false;
	}
	function canCompare()
	{
		return false;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Modules');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}
}