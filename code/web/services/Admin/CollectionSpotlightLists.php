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
	function getAllObjects($page, $recordsPerPage){
		$object = new CollectionSpotlightList();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getDefaultSort()
	{
		return 'weight asc';
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

	function getInstructions(){
		return '';
	}

	function getInitializationJs(){
		return 'return AspenDiscovery.Admin.updateBrowseSearchForSource();';
	}

	function showReturnToList(){
		return false;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('', 'Collection Spotlight List');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Collection Spotlights','Administer Library Collection Spotlights']);
	}
}