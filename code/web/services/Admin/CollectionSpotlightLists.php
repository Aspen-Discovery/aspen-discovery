<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';

class Admin_CollectionSpotlightLists extends ObjectEditor
{

	function getObjectType(){
		return 'CollectionSpotlightList';
	}
	function getToolName(){
		return 'CollectionSpotlightLists';
	}
	function getPageTitle(){
		return 'Collection Spotlight Lists';
	}
	function canAddNew(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new CollectionSpotlightList();
		$object->orderBy('weight');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return CollectionSpotlightList::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'contentEditor', 'libraryManager', 'locationManager');
	}

	function getInstructions(){
		return '';
	}

	function getInitializationJs(){
		return 'return AspenDiscovery.Admin.updateSpotlightSearchForSource();';
	}

	function showReturnToList(){
		return false;
	}
}