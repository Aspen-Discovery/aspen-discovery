<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';

class Admin_GroupedWorkDisplay extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkDisplaySetting';
	}
	function getToolName(){
		return 'GroupedWorkDisplay';
	}
	function getPageTitle(){
		return 'Grouped Work Display Settings';
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new GroupedWorkDisplaySetting();
		$object->orderBy('name');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return GroupedWorkDisplaySetting::getObjectStructure();
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

	function getInstructions(){
		//return 'For more information on themes see TBD';
		return '';
	}

	function getListInstructions(){
		return $this->getInstructions();
	}
}