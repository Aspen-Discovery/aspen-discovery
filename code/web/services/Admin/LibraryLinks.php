<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LibraryLink.php';

class Admin_LibraryLinks extends ObjectEditor
{

	function getObjectType(){
		return 'LibraryLink';
	}
	function getToolName(){
		return 'LibraryLinks';
	}
	function getPageTitle(){
		return 'Library Links';
	}
	function getAllObjects(){
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new LibraryLink();
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasRole('opacAdmin')){
			//Scope to just locations for the user based on home library
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$object->libraryId = $patronLibrary->libraryId;
		}

		$object->orderBy('weight');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return LibraryLink::getObjectStructure();
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

}