<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class Admin_People extends ObjectEditor
{
	function getObjectType() : string{
		return 'Person';
	}
	function getToolName() : string{
		return 'People';
	}
	function getPageTitle() : string{
		return 'People';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new Person();
		$object->orderBy($this->getSort() . ', lastName asc, firstName asc');
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->personId] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'lastName asc';
	}
	function getObjectStructure() : array{
		$person = new Person();
		return $person->getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return array('lastName', 'firstName', 'middleName', 'birthDate');
	}
	function getIdKeyColumn() : string{
		return 'personId';
	}
	function getRedirectLocation($objectAction, $curObject){
		if ($objectAction == 'delete'){
			return '/Union/Search?searchSource=genealogy&lookfor=&searchIndex=GenealogyName&submit=Find';
		}else{
			if ($curObject instanceof Person){
				return '/Person/' . $curObject->personId;
			}else{
				return '/Union/Search?searchSource=genealogy&lookfor=&searchIndex=GenealogyName&submit=Find';
			}
		}
	}
	function showReturnToList(){
		return false;
	}
	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Person');
		return $breadcrumbs;
	}

	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Admin/admin-sidebar.tpl', $translateTitle = true)
	{
		parent::display($mainContentTemplate, $pageTitle, '', false);
	}

	function getActiveAdminSection() : string
	{
		return '';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer Genealogy']);
	}
}