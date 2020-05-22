<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/MergedGroupedWork.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_MergedGroupedWorks extends ObjectEditor
{
	function getObjectType(){
		return 'MergedGroupedWork';
	}
	function getToolName(){
		return 'MergedGroupedWorks';
	}
	function getPageTitle(){
		return 'Merged Grouped Works';
	}
	function getAllObjects(){
		$object = new MergedGroupedWork();
		$object->orderBy('sourceGroupedWorkId');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return MergedGroupedWork::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging');
	}
	function getInstructions(){
		global $interface;
		return $interface->fetch('Admin/merge_grouped_work_instructions.tpl');
	}

}