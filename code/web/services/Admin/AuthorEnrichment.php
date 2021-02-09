<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/AuthorEnrichment.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorEnrichment extends ObjectEditor
{
	function getObjectType(){
		return 'AuthorEnrichment';
	}
	function getToolName(){
		return 'AuthorEnrichment';
	}
	function getPageTitle(){
		return 'Author Enrichment';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new AuthorEnrichment();
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
	function getDefaultSort()
	{
		return 'authorName asc';
	}

	function getObjectStructure(){
		return AuthorEnrichment::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getInstructions(){
		return '/Admin/HelpManual?page=Wikipedia';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorEnrichment', 'Wikipedia Integration');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'third_party_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Wikipedia Integration');
	}
}