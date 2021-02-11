<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_NonGroupedRecords extends ObjectEditor
{
	function getObjectType(){
		return 'NonGroupedRecord';
	}
	function getToolName(){
		return 'NonGroupedRecords';
	}
	function getPageTitle(){
		return 'Records to Not Group';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new NonGroupedRecord();
		$object->orderBy($this->getSort() . ', recordId');
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
		return 'source asc';
	}
	function getObjectStructure(){
		return NonGroupedRecord::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/NonGroupedRecords', 'Records To Not Group');
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
}