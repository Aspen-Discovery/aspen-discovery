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
	function getAllObjects(){
		$object = new NonGroupedRecord();
		$object->orderBy('source, recordId');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
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
	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging', 'superCataloger');
	}
	function getInstructions(){
//		global $interface;
//		return $interface->fetch('Admin/ungrouping_work_instructions.tpl');
		return '';
	}

}