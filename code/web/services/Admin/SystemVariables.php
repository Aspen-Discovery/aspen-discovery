<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_SystemVariables extends ObjectEditor{

	function getObjectType() : string{
		return 'SystemVariables';
	}
	function getToolName() : string{
		return 'SystemVariables';
	}
	function getPageTitle() : string{
		return 'System Variables';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$variableList = array();

		$variable = new SystemVariables();
		$variable->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$variable->find();
		while ($variable->fetch()){
			$variableList[$variable->id] = clone $variable;
		}
		return $variableList;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}
	function canSort() : bool
	{
		return false;
	}

	function getObjectStructure() : array {
		return SystemVariables::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'name';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function canAddNew(){
		return $this->getNumObjects() == 0;
	}
	function canDelete(){
		return false;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SystemVariables', 'System Variables');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_admin';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer System Variables');
	}
}