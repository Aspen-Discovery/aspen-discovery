<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class People extends ObjectEditor
{
	function getObjectType(){
		return 'Person';
	}
	function getToolName(){
		return 'People';
	}
	function getPageTitle(){
		return 'People';
	}
	function getAllObjects(){
		$object = new Person();
		$object->orderBy('lastName, firstName');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->personId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		$person = new Person();
		return $person->getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('lastName', 'firstName', 'middleName', 'birthDate');
	}
	function getIdKeyColumn(){
		return 'personId';
	}
	function getAllowableRoles(){
		return array('genealogyContributor');
	}
	function getRedirectLocation($objectAction, $curObject){
		global $configArray;
		if ($objectAction == 'delete'){
			return $configArray['Site']['path'] . '/Union/Search?searchSource=genealogy&lookfor=&searchIndex=GenealogyName&submit=Find';
		}else{
			return $configArray['Site']['path'] . '/Person/' . $curObject->personId;
		}
	}
	function showReturnToList(){
		return false;
	}
}