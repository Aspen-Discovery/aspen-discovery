<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class Obituaries extends ObjectEditor
{
	function getObjectType(){
		return 'Obituary';
	}
	function getToolName(){
		return 'Obituaries';
	}
	function getPageTitle(){
		return 'Obituaries';
	}
	function getAllObjects(){
		$object = new Obituary();
		$object->orderBy('date');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->obituaryId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return Obituary::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('personId', 'source', 'date');
	}
	function getIdKeyColumn(){
		return 'obituaryId';
	}
	function getAllowableRoles(){
		return array('genealogyContributor');
	}
	function getRedirectLocation($objectAction, $curObject){
		global $configArray;
		return $configArray['Site']['path'] . '/Person/' . $curObject->personId;
	}
	function showReturnToList(){
		return false;
	}
}