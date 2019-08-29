<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';

/** @noinspection PhpUnused */
class SideLoads_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'SideLoadScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'SideLoads';
	}
	function getPageTitle(){
		return 'Side Loaded eContent Scopes';
	}
	function getAllObjects(){
		$object = new SideLoadScope();
		$object->orderBy('name');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return SideLoadScope::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'cataloging');
	}
	function canAddNew(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('cataloging');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('cataloging');
	}
	function getAdditionalObjectActions($existingObject){
		return [];
	}

	function getInstructions(){
		return '';
	}
}