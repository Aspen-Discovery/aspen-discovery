<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Marriage.php';

class Marriages extends ObjectEditor
{
	function getObjectType(){
		return 'Marriage';
	}
	function getToolName(){
		return 'Marriages';
	}
	function getPageTitle(){
		return 'Marriages';
	}
	function getAllObjects(){
		$object = new Marriage();
		$object->orderBy('marriageDate');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->marriageId] = clone $object;
		}
		return $objectList;
	}
    function getObjectStructure(){
		return Marriage::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('personId', 'spouseName', 'date');
	}
	function getIdKeyColumn(){
		return 'marriageId';
	}
	function getAllowableRoles(){
		return array('genealogyContributor');
	}
	function getRedirectLocation($objectAction, $curObject){
		return '/Person/' . $curObject->personId;
	}
	function showReturnToList(){
		return false;
	}
}