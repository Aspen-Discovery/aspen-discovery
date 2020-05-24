<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';

class Admin_Placards extends ObjectEditor
{

	function getObjectType(){
		return 'Placard';
	}
	function getToolName(){
		return 'Placards';
	}
	function getPageTitle(){
		return 'Placards';
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin', 'libraryAdmin', 'libraryManager', 'locationManager', 'contentEditor');
	}
	function getAllObjects(){
		$placard = new Placard();
		$placard->orderBy('title');
		$placard->find();
		$list = array();
		while ($placard->fetch()){
			$list[$placard->id] = clone $placard;
		}
		return $list;
	}
	function getObjectStructure(){
		return Placard::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'locationManager', 'contentEditor');
	}
	function getInstructions()
	{
		return '/Admin/HelpManual?page=Placards';
	}
}