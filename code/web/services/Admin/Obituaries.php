<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class Admin_Obituaries extends ObjectEditor
{
	function getObjectType() : string{
		return 'Obituary';
	}
	function getToolName() : string{
		return 'Obituaries';
	}
	function getPageTitle() : string{
		return 'Obituaries';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new Obituary();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->obituaryId] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'date asc';
	}
	function getObjectStructure() : array{
		return Obituary::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return array('personId', 'source', 'date');
	}
	function getIdKeyColumn() : string{
		return 'obituaryId';
	}
	function getRedirectLocation($objectAction, $curObject){
		if ($curObject instanceof Obituary) {
			return '/Person/' . $curObject->personId;
		}else{
			return '/Union/Search?searchSource=genealogy&lookfor=&searchIndex=GenealogyName&submit=Find';
		}
	}
	function showReturnToList(){
		return false;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (!empty($this->activeObject) && $this->activeObject instanceof Obituary){
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->activeObject->personId;
			if ($person->find(true)){
				$breadcrumbs[] = new Breadcrumb('/Person/' . $person->personId, $person->displayName());
			}
		}
		$breadcrumbs[] = new Breadcrumb('', 'Obituary');
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