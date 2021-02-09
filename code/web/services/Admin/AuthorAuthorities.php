<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/AuthorAuthority.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorAuthorities extends ObjectEditor
{
	function getObjectType(){
		return 'AuthorAuthority';
	}
	function getToolName(){
		return 'AuthorAuthorities';
	}
	function getPageTitle(){
		return 'Author Authorities';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new AuthorAuthority();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(){
		return 'author asc';
	}
	function getObjectStructure(){
		return AuthorAuthority::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getInstructions(){
//		global $interface;
//		return $interface->fetch('Admin/ungrouping_work_instructions.tpl');
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorAuthorities', 'Author Authorities');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cataloging';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}
}