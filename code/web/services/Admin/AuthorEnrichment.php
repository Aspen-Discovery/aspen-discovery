<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/AuthorEnrichment.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorEnrichment extends ObjectEditor
{
	function getObjectType() : string{
		return 'AuthorEnrichment';
	}
	function getToolName() : string{
		return 'AuthorEnrichment';
	}
	function getPageTitle() : string{
		return 'Author Enrichment';
	}
	function getAllObjects($page, $recordsPerPage) : array{
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
	function getDefaultSort() : string
	{
		return 'authorName asc';
	}

	function getObjectStructure() : array{
		return AuthorEnrichment::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function getInstructions() : string{
		return 'https://help.aspendiscovery.org/help/integration/enrichment';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorEnrichment', 'Wikipedia Integration');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'third_party_enrichment';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Wikipedia Integration');
	}
}