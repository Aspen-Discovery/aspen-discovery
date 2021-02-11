<?php

require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
class Admin_AlternateTitles extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkAlternateTitle';
	}
	function getToolName(){
		return 'AlternateTitles';
	}
	function getPageTitle(){
		return 'Title / Author Authorities';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new GroupedWorkAlternateTitle();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'alternateTitle asc';
	}

	function getObjectStructure(){
		return GroupedWorkAlternateTitle::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getInstructions(){
//		global $interface;
//		return $interface->fetch('Admin/ungrouping_work_instructions.tpl');
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/AlternateTitles', 'Alternate Titles');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cataloging';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}

	function canAddNew()
	{
		return false;
	}
}