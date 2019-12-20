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
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new LayoutSetting();
		$object->orderBy('name');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
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
