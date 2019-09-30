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
	function getAllObjects(){
		$list = array();

		$object = new Module();
		$object->orderBy('name asc');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return Module::getObjectStructure();
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
		return false;
	}
	function canDelete(){
		return false;
	}

}