<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';

class Admin_GroupedWorkFacets extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkFacetGroup';
	}
	function getToolName(){
		return 'GroupedWorkFacets';
	}
	function getPageTitle(){
		return 'Grouped Work Facets';
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new GroupedWorkFacetGroup();
		$object->orderBy('name');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return GroupedWorkFacetGroup::getObjectStructure();
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