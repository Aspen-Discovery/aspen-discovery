<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_SystemVariables extends ObjectEditor{

	function getObjectType(){
		return 'SystemVariables';
	}
	function getToolName(){
		return 'SystemVariables';
	}
	function getPageTitle(){
		return 'System Variables';
	}
	function getAllObjects($page, $recordsPerPage){
		$variableList = array();

		$variable = new SystemVariables();
		$variable->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$variable->find();
		while ($variable->fetch()){
			$variableList[$variable->id] = clone $variable;
		}
		return $variableList;
	}
	function getDefaultSort()
	{
		return 'id asc';
	}
	function canSort()
	{
		return false;
	}

	function getObjectStructure(){
		return SystemVariables::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'name';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return $this->getNumObjects() == 0;
	}
	function canDelete(){
		return false;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SystemVariables', 'System Variables');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer System Variables');
	}
}