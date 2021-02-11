<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';

class Websites_Settings extends ObjectEditor
{
	function getObjectType(){
		return 'WebsiteIndexSetting';
	}
	function getToolName(){
		return 'Settings';
	}
	function getModule(){
		return 'Websites';
	}
	function getPageTitle(){
		return 'Website Indexing Settings';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new WebsiteIndexSetting();
		$object->deleted = 0;
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'id asc';
	}
	function getObjectStructure(){
		return WebsiteIndexSetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAdditionalObjectActions($existingObject){
		return [];
	}

	function getInstructions(){
		return '';
	}
	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
		$breadcrumbs[] = new Breadcrumb('/Websites/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'web_indexer';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Website Indexing Settings');
	}
}